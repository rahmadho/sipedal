<?php

declare(strict_types=1);

namespace App\Services\Pull;

use App\Enums\ApiFamily;
use App\Enums\EndpointScope;
use App\Enums\PullStatus;
use App\Models\DerivedProgress;
use App\Models\Endpoint;
use App\Models\PullLog;
use App\Services\Inaproc\ApiResponse;
use App\Services\Inaproc\EndpointRegistry;
use App\Services\Inaproc\FallbackResolver;
use App\Services\Inaproc\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Orkestrasi tarik data per endpoint (F4, F5, F6, F10).
 */
class PullService
{
    public function __construct(
        private readonly EndpointRegistry $registry,
        private readonly Paginator $paginator,
        private readonly FallbackResolver $fallback,
        private readonly TableWriter $writer,
        private readonly DerivedPuller $derived,
    ) {}

    /**
     * Tarik satu endpoint untuk satu KLPD + tahun.
     */
    public function pullEndpoint(
        Endpoint $endpoint,
        string $kodeKlpd,
        int $tahun,
        ?string $token,
        string $credentialKey,
        bool $refreshDerived = false,
    ): PullLog {
        $startedAt = microtime(true);
        $table = $endpoint->tableName();

        if (! $endpoint->active) {
            return $this->log($endpoint, $kodeKlpd, $tahun, PullStatus::NONAKTIF, 0, 0, null, 'Endpoint nonaktif', $startedAt);
        }

        // Endpoint turunan punya jalur sendiri (F1.3).
        if ($endpoint->endpointScope() === EndpointScope::DERIVED) {
            return $this->derived->pull($endpoint, $kodeKlpd, $tahun, $token, $credentialKey, $startedAt, $refreshDerived);
        }

        try {
            $result = $this->paginator->fetchAll($endpoint, $kodeKlpd, $tahun, $token, $credentialKey);
        } catch (Throwable $e) {
            return $this->log(
                $endpoint,
                $kodeKlpd,
                $tahun,
                PullStatus::GAGAL,
                0,
                0,
                null,
                'Kesalahan tak terduga: '.$e->getMessage(),
                $startedAt,
            );
        }

        /** @var ApiResponse $response */
        $response = $result['response'];
        $pages = $result['pages'];
        $rows = $result['rows'];
        $source = null;

        // F4 — klasifikasi status.
        if ($response->ok()) {
            if ($rows === []) {
                // 200 + kosong → lewati, tabel tidak disentuh, tanpa fallback.
                return $this->log($endpoint, $kodeKlpd, $tahun, PullStatus::KOSONG, 0, $pages, null, null, $startedAt);
            }
        } elseif ($response->status === 403) {
            // 403 → lewati endpoint, lanjut endpoint berikutnya.
            return $this->log(
                $endpoint,
                $kodeKlpd,
                $tahun,
                PullStatus::FORBIDDEN,
                0,
                $pages,
                null,
                $response->error ?? 'Akses ditolak',
                $startedAt,
            );
        } else {
            // Non-200 → coba fallback (R1). 200 kosong tidak sampai ke sini (R2).
            $fallback = $this->fallback->resolve($endpoint, $kodeKlpd, $tahun, $token, $credentialKey, $response);

            if ($fallback === null) {
                // R3 — utama dan fallback gagal: data lama dibiarkan.
                return $this->log(
                    $endpoint,
                    $kodeKlpd,
                    $tahun,
                    PullStatus::GAGAL,
                    0,
                    $pages,
                    null,
                    $response->error ?? 'Gagal menarik data',
                    $startedAt,
                );
            }

            $rows = $fallback['rows'];
            $source = $fallback['source'];
            $pages += $fallback['pages'];

            if ($rows === []) {
                return $this->log(
                    $endpoint,
                    $kodeKlpd,
                    $tahun,
                    PullStatus::KOSONG,
                    0,
                    $pages,
                    $source,
                    null,
                    $startedAt,
                );
            }
        }

        // Simpan — R4: fallback tetap ditulis ke tabel endpoint utama.
        try {
            $saved = $this->writer->replace($table, $kodeKlpd, $tahun, $rows, $source);
        } catch (Throwable $e) {
            Log::error('Gagal menyimpan hasil tarik', [
                'endpoint' => $endpoint->name,
                'tabel' => $table,
                'kode_klpd' => $kodeKlpd,
                'tahun' => $tahun,
                'error' => $e->getMessage(),
            ]);

            return $this->log(
                $endpoint,
                $kodeKlpd,
                $tahun,
                PullStatus::GAGAL,
                0,
                $pages,
                $source,
                'Gagal menyimpan: '.$e->getMessage(),
                $startedAt,
            );
        }

        $message = $source !== null
            ? $this->fallback->reason($response, $source)
            : null;

        return $this->log($endpoint, $kodeKlpd, $tahun, PullStatus::OK, $saved, $pages, $source, $message, $startedAt);
    }

    /**
     * Tulis satu baris pull_log (F10).
     */
    public function log(
        Endpoint $endpoint,
        string $kodeKlpd,
        int $tahun,
        PullStatus $status,
        int $rows,
        int $pages,
        ?string $source,
        ?string $message,
        float $startedAt,
    ): PullLog {
        $duration = (int) round((microtime(true) - $startedAt) * 1000);

        $attributes = [
            'endpoint' => $endpoint->name,
            'grup' => $endpoint->grup,
            'api' => $endpoint->api,
            'kode_klpd' => $kodeKlpd,
            'tahun' => $tahun,
            'status' => $status->value,
            'rows' => $rows,
            'source' => $source,
            'pages' => $pages,
            'durasi_ms' => $duration,
            'pesan' => $message === null ? null : mb_substr($message, 0, 255),
        ];

        // Kolom created_at dan waktu: tabel lama memakai `waktu`, tabel baru
        // memakai created_at. Isi keduanya bila tersedia.
        $payload = $attributes;

        if ($this->hasColumn('created_at')) {
            $payload['created_at'] = now();
        }

        if ($this->hasColumn('waktu')) {
            $payload['waktu'] = now()->toDateTimeString();
        }

        return PullLog::query()->create($payload);
    }

    private function hasColumn(string $column): bool
    {
        static $cache = [];

        if (! array_key_exists($column, $cache)) {
            $cache[$column] = \Illuminate\Support\Facades\Schema::hasColumn('pull_log', $column);
        }

        return $cache[$column];
    }
}
