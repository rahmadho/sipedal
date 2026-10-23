<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Endpoint;
use App\Services\Inaproc\ApiClient;
use App\Services\Inaproc\EndpointRegistry;
use App\Services\Pull\KlpdCredentials;
use Illuminate\Console\Command;

/**
 * inaproc:validate-tokens — periksa validitas seluruh token terdaftar (F12).
 *
 * Menguji satu endpoint ringan per KLPD; token tidak pernah dicetak mentah.
 */
class InaprocValidateTokensCommand extends Command
{
    protected $signature = 'inaproc:validate-tokens
        {--tahun= : Tahun anggaran untuk uji}
        {--endpoint=master-program : Endpoint yang dipakai untuk menguji}';

    protected $description = 'Memeriksa validitas seluruh token KLPD terdaftar';

    public function handle(
        EndpointRegistry $registry,
        KlpdCredentials $credentials,
        ApiClient $client,
    ): int {
        $all = $credentials->all();

        if ($all === []) {
            $this->error('Berkas kredensial kosong atau tidak ditemukan:');
            $this->line('  '.config('inaproc.klpd_file'));

            return self::FAILURE;
        }

        $tahun = (int) ($this->option('tahun') ?? now()->year);
        $endpointName = (string) $this->option('endpoint');
        $endpoint = $registry->find($endpointName);

        if ($endpoint === null) {
            $this->error('Endpoint tidak ditemukan: '.$endpointName);

            return self::FAILURE;
        }

        $rows = [];
        $invalid = 0;

        foreach ($all as $kode => $entry) {
            if ($entry['token'] === '') {
                $rows[] = [$kode, $entry['label'] ?: '-', '(kosong)', '-', 'Belum ada token'];
                $invalid++;

                continue;
            }

            $credentialKey = $credentials->credentialKey($kode, $entry['token']);
            $params = $registry->buildParams($endpoint, $kode, $tahun);

            $response = $client->get($endpoint->path, $params, $endpoint->family(), $entry['token'], $credentialKey);

            $verdict = match (true) {
                $response->ok() => 'VALID',
                $response->status === 403 => 'DITOLAK',
                $response->status === 429 => 'DIBATASI',
                default => 'GAGAL',
            };

            if ($verdict !== 'VALID') {
                $invalid++;
            }

            $rows[] = [
                $kode,
                $entry['label'] ?: '-',
                $credentials->maskedToken($kode),
                $response->status === 0 ? 'ERR' : (string) $response->status,
                $verdict,
            ];
        }

        $this->table(['KLPD', 'Label', 'Token', 'HTTP', 'Status'], $rows);

        $this->line(sprintf('%d KLPD terdaftar, %d bermasalah.', count($all), $invalid));

        return $invalid > 0 ? self::FAILURE : self::SUCCESS;
    }
}
