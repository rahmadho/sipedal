<?php

declare(strict_types=1);

namespace App\Services\Inaproc;

use App\Enums\ApiFamily;
use App\Models\Endpoint;

/**
 * Aturan fallback (F5).
 *
 * R1  fallback hanya dipicu saat endpoint utama membalas HTTP non-200
 * R2  200 + data kosong → lewati, JANGAN fallback
 * R3  utama dan fallback gagal → data lama tidak dihapus
 * R4  hasil fallback ditulis ke tabel endpoint utama, ditandai kolom _source
 */
class FallbackResolver
{
    /**
     * Pasangan fallback resmi.
     *
     * @var array<string, string>
     */
    public const PAIRS = [
        'paket-penyedia' => 'paket-penyedia-terumumkan',
        'paket-swakelola' => 'paket-swakelola-terumumkan',
    ];

    public function __construct(
        private readonly EndpointRegistry $registry,
        private readonly Paginator $paginator,
    ) {}

    public function fallbackFor(string $endpointName): ?string
    {
        return self::PAIRS[$endpointName] ?? null;
    }

    public function hasFallback(string $endpointName): bool
    {
        return isset(self::PAIRS[$endpointName]);
    }

    /**
     * Apakah fallback layak dipicu untuk respons utama ini?
     *
     * R1: hanya bila HTTP non-200.
     * R2: 200 kosong → tidak.
     */
    public function shouldTrigger(ApiResponse $primary): bool
    {
        if ($primary->status === 200) {
            return false;
        }

        return true;
    }

    /**
     * Jalankan fallback dan kembalikan hasilnya (null bila tidak bisa).
     *
     * @return array{rows: array<int, array<string, mixed>>, response: ApiResponse, pages: int, source: string}|null
     */
    public function resolve(
        Endpoint $primary,
        ?string $kodeKlpd,
        ?int $tahun,
        ?string $token,
        string $credentialKey,
        ApiResponse $primaryResponse,
    ): ?array {
        $fallbackName = $this->fallbackFor($primary->name);

        if ($fallbackName === null || ! $this->shouldTrigger($primaryResponse)) {
            return null;
        }

        $fallback = $this->registry->find($fallbackName);

        if ($fallback === null) {
            return null;
        }

        // Fallback selalu memakai keluarga API yang sama dengan endpoint utama
        // (struktur respons identik, R4).
        $result = $this->paginator->fetchAll(
            endpoint: $fallback,
            kodeKlpd: $kodeKlpd,
            tahun: $tahun,
            token: $token,
            credentialKey: $credentialKey,
        );

        if (! $result['response']->ok() || $result['rows'] === []) {
            // R3: fallback gagal → biarkan data lama.
            return null;
        }

        return [
            'rows' => $result['rows'],
            'response' => $result['response'],
            'pages' => $result['pages'],
            'source' => $fallback->name,
        ];
    }

    /**
     * Alasan fallback untuk pull_log.
     */
    public function reason(ApiResponse $primaryResponse, string $fallbackName): string
    {
        $status = $primaryResponse->status === 0 ? 'koneksi gagal' : 'HTTP '.$primaryResponse->status;

        return "Utama {$status}, fallback {$fallbackName}";
    }
}
