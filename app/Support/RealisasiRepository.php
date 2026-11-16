<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Repository data Realisasi RUP berbasis data riil PostgreSQL inaproc / v_realisasi_rup.
 */
class RealisasiRepository
{
    public const DAFTAR_SUMBER = [
        'e-katalog' => 'E-Katalog',
        'non-tender' => 'Non Tender',
        'pencatatan-non-tender' => 'Pencatatan Non Tender',
        'tender' => 'Tender',
    ];

    /**
     * Hitung komposisi realisasi per sumber pengadaan (langsung vs warisan) & pagu.
     * Hasil diagregasikan dan dicache selama 180 detik.
     *
     * @return array<int, array{sumber: string, label: string, total: int, langsung: int, warisan: int, pagu: float}>
     */
    public static function komposisiSumber(string $kdKlpd, int $tahun, ?int $satkerId = null): array
    {
        $cacheKey = sprintf('realisasi:komposisi:%s:%d:%s', $kdKlpd, $tahun, $satkerId ?: 'semua');

        return Cache::remember($cacheKey, 180, function () use ($kdKlpd, $tahun, $satkerId) {
            $query = DB::table('v_realisasi_rup')
                ->selectRaw("
                    sumber,
                    COUNT(CASE WHEN jenis = 'langsung' THEN 1 END) AS langsung,
                    COUNT(CASE WHEN jenis = 'warisan' THEN 1 END) AS warisan,
                    COUNT(*) AS total,
                    COALESCE(SUM(NULLIF(pagu::text, '')::numeric), 0) AS total_pagu
                ")
                ->where('kd_klpd', $kdKlpd)
                ->where('tahun_anggaran', $tahun);

            if ($satkerId) {
                $query->where('kd_satker', $satkerId);
            }

            $rows = $query->groupBy('sumber')->get()->keyBy('sumber');

            $hasil = [];
            foreach (self::DAFTAR_SUMBER as $k => $label) {
                $item = $rows->get($k);
                $hasil[] = [
                    'sumber' => $k,
                    'label' => $label,
                    'langsung' => (int) ($item->langsung ?? 0),
                    'warisan' => (int) ($item->warisan ?? 0),
                    'total' => (int) ($item->total ?? 0),
                    'pagu' => (float) ($item->total_pagu ?? 0),
                ];
            }

            return $hasil;
        });
    }

    /**
     * Mengambil daftar paket proses realisasi RUP dengan filter, pencarian, dan pagination.
     *
     * @param  array{
     *     cari?: string|null,
     *     satker?: int|string|null,
     *     sumber?: string|null,
     *     jenis?: string|null,
     *     sort?: string|null,
     *     arah?: string|null,
     * }  $filters
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public static function daftar(
        string $kdKlpd,
        int $tahun,
        array $filters = [],
        int $perHalaman = 25,
        int $offset = 0
    ): array {
        $query = DB::table('v_realisasi_rup')
            ->where('kd_klpd', $kdKlpd)
            ->where('tahun_anggaran', $tahun);

        // Filter satuan kerja (Mendukung Multi-Select Array atau Nilai Tunggal)
        if (!empty($filters['satker'])) {
            $satkerList = is_array($filters['satker']) ? array_filter($filters['satker']) : [$filters['satker']];
            if (!empty($satkerList)) {
                $query->whereIn('kd_satker', array_map('intval', $satkerList));
            }
        }

        // Filter sumber
        if (!empty($filters['sumber'])) {
            $query->where('sumber', (string) $filters['sumber']);
        }

        // Filter jenis (langsung / warisan)
        if (!empty($filters['jenis'])) {
            $query->where('jenis', (string) $filters['jenis']);
        }

        // Filter cari
        if (!empty($filters['cari'])) {
            $cari = trim((string) $filters['cari']);
            $query->where(function ($q) use ($cari) {
                $q->where('kd_rup', 'ilike', "%{$cari}%")
                    ->orWhere('nama_paket', 'ilike', "%{$cari}%")
                    ->orWhere('nama_satker', 'ilike', "%{$cari}%");
            });
        }

        $total = (clone $query)->count();

        // Sorting
        $sortKolom = $filters['sort'] ?? 'kd_rup';
        $arah = strtolower($filters['arah'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $kolomDiizinkan = [
            'kd_rup' => 'kd_rup',
            'nama_paket' => 'nama_paket',
            'nama_satker' => 'nama_satker',
            'sumber' => 'sumber',
            'jenis' => 'jenis',
            'depth' => 'depth',
        ];

        if ($sortKolom === 'pagu') {
            $query->orderByRaw("COALESCE(NULLIF(pagu::text, '')::numeric, 0) {$arah}");
        } elseif (isset($kolomDiizinkan[$sortKolom])) {
            $query->orderBy($kolomDiizinkan[$sortKolom], $arah);
        } else {
            $query->orderBy('kd_rup', 'desc');
        }

        $items = $query->skip($offset)->take($perHalaman)->get();

        $rows = [];
        foreach ($items as $idx => $r) {
            $pagu = (float) ($r->pagu ?? 0);
            $rows[] = [
                'no' => $offset + $idx + 1,
                'kd_rup' => (string) $r->kd_rup,
                'nama_paket' => (string) ($r->nama_paket ?? '-'),
                'metode' => (string) ($r->metode_pengadaan ?? '-'),
                'kd_satker' => $r->kd_satker ? (int) $r->kd_satker : null,
                'satker' => (string) ($r->nama_satker ?? '-'),
                'sumber' => (string) ($r->sumber ?? '-'),
                'pagu' => $pagu,
                'jenis' => (string) ($r->jenis ?? 'langsung'),
                'dari_rup' => $r->dari_rup ? (string) $r->dari_rup : null,
                'depth' => (int) ($r->depth ?? 0),
            ];
        }

        return [
            'items' => $rows,
            'total' => $total,
        ];
    }

    /**
     * Format label badge sumber pengadaan.
     */
    public static function labelSumber(string $sumber): string
    {
        return self::DAFTAR_SUMBER[$sumber] ?? ucwords(str_replace('-', ' ', $sumber));
    }
}
