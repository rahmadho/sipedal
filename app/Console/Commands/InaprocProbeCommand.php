<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Endpoint;
use App\Services\Inaproc\ApiClient;
use App\Services\Inaproc\EndpointRegistry;
use App\Services\Pull\KlpdCredentials;
use Illuminate\Console\Command;

/**
 * inaproc:probe — uji akses tiap endpoint, 1 request per endpoint (F12).
 */
class InaprocProbeCommand extends Command
{
    protected $signature = 'inaproc:probe
        {--klpd= : Kode KLPD}
        {--tahun= : Tahun anggaran}
        {--grup= : Batasi ke satu grup}';

    protected $description = 'Menguji akses tiap endpoint (1 request per endpoint)';

    public function handle(
        EndpointRegistry $registry,
        KlpdCredentials $credentials,
        ApiClient $client,
    ): int {
        $klpd = (string) ($this->option('klpd') ?? '');

        if ($klpd === '') {
            $registered = array_keys($credentials->all());

            if ($registered === []) {
                $this->error('Tidak ada KLPD terdaftar.');

                return self::FAILURE;
            }

            $klpd = (string) $this->choice('Pilih KLPD', $registered);
        }

        $entry = $credentials->for($klpd);

        if ($entry === null || $entry['token'] === '') {
            $this->error(sprintf('Token untuk KLPD %s belum tersedia.', $klpd));

            return self::FAILURE;
        }

        $tahun = (int) ($this->option('tahun') ?? now()->year);
        $credentialKey = $credentials->credentialKey($klpd, $entry['token']);

        $endpoints = $registry->all(true);

        if (($group = $this->option('grup')) !== null && $group !== '') {
            $endpoints = $endpoints->filter(fn (Endpoint $e) => $e->grup === $group)->values();
        }

        $rows = [];

        foreach ($endpoints as $endpoint) {
            $params = $registry->buildParams($endpoint, $klpd, $tahun, [], null, 1);

            $response = $client->get($endpoint->path, $params, $endpoint->family(), $entry['token'], $credentialKey);

            $rows[] = [
                $endpoint->name,
                $endpoint->api,
                $response->status === 0 ? 'ERR' : (string) $response->status,
                $response->ok() ? 'OK' : 'X',
                mb_substr((string) ($response->error ?? ''), 0, 60),
            ];
        }

        $this->table(['Endpoint', 'API', 'HTTP', 'Akses', 'Pesan'], $rows);

        $forbidden = count(array_filter($rows, static fn (array $r): bool => $r[2] === '403'));

        if ($forbidden > 0) {
            $this->warn(sprintf('%d endpoint membalas 403 — bukan kegagalan proses.', $forbidden));
        }

        return self::SUCCESS;
    }
}
