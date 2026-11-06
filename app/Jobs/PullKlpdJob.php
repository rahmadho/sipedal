<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\PullStatus;
use App\Models\Endpoint;
use App\Services\Inaproc\EndpointRegistry;
use App\Services\Pull\KlpdCredentials;
use App\Services\Pull\PullService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tarik seluruh endpoint aktif untuk 1 KLPD (F8).
 *
 * Kegagalan satu KLPD tidak menggagalkan KLPD lain: exception ditangkap dan
 * dilaporkan sebagai ringkasan, bukan dibiarkan naik ke queue worker.
 */
class PullKlpdJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 7200;

    public int $tries = 1;

    /**
     * @param  array<int, string>|null  $endpointNames
     */
    public function __construct(
        public readonly string $kodeKlpd,
        public readonly int $tahun,
        public readonly ?array $endpointNames = null,
        public readonly bool $refreshDerived = false,
    ) {}

    /**
     * @return array<string, int>
     */
    public function handle(
        EndpointRegistry $registry,
        KlpdCredentials $credentials,
        PullService $pullService,
    ): array {
        $entry = $credentials->for($this->kodeKlpd);
        $token = $entry['token'] ?? null;

        if ($token === null || $token === '') {
            // KLPD tanpa token tetap terdaftar namun dilewati (F8).
            Log::info('KLPD dilewati: token belum tersedia', [
                'kode_klpd' => $this->kodeKlpd,
            ]);

            return ['OK' => 0, 'KOSONG' => 0, 'GAGAL' => 0, 'FORBIDDEN' => 0, 'NONAKTIF' => 0];
        }

        $credentialKey = $credentials->credentialKey($this->kodeKlpd, $token);

        $endpoints = $this->endpointNames === null
            ? $registry->all(true)
            : $registry->all()->filter(fn (Endpoint $e) => in_array($e->name, $this->endpointNames, true))->values();

        $summary = ['OK' => 0, 'KOSONG' => 0, 'GAGAL' => 0, 'FORBIDDEN' => 0, 'NONAKTIF' => 0];

        foreach ($endpoints as $endpoint) {
            try {
                $log = $pullService->pullEndpoint(
                    endpoint: $endpoint,
                    kodeKlpd: $this->kodeKlpd,
                    tahun: $this->tahun,
                    token: $token,
                    credentialKey: $credentialKey,
                );

                $summary[$log->status] = ($summary[$log->status] ?? 0) + 1;
            } catch (Throwable $e) {
                $summary[PullStatus::GAGAL->value]++;

                Log::error('Endpoint gagal tanpa log', [
                    'endpoint' => $endpoint->name,
                    'kode_klpd' => $this->kodeKlpd,
                    'tahun' => $this->tahun,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $summary;
    }
}
