<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Repositori data RUP (Rencana Umum Pengadaan) dari basis data ril INAPROC.
 *
 * Mengakomodasi:
 * - Penyedia & Penyedia dalam Swakelola (PDS) dari inaproc.rec_rup_paket_penyedia
 * - Swakelola dari inaproc.rec_rup_paket_swakelola
 * - Status proses via ref_rup_proses
 *
 * Filter status umumkan: 'Terumumkan' dan 'Sudah Diproses', serta non-delete.
 */
class RupRepository
{
    public const STATUS_UMUMKAN = ['Terumumkan', 'Sudah Diproses'];

    /**
     * 4 Kategori Baku Jenis Pengadaan Penyedia sesuai Perpres PBJ & INAPROC
     */
    public const JENIS_PENGADAAN_BAKU = [
        'Barang' => 'Barang',
        'Pekerjaan Konstruksi' => 'Pekerjaan Konstruksi',
        'Jasa Konsultansi' => 'Jasa Konsultansi',
        'Jasa Lainnya' => 'Jasa Lainnya',
    ];

    /**
     * Ambil ringkasan seluruh card metrik RUP:
     * 1. Status PDN Penyedia (PDN & Non-PDN: Jumlah & Pagu)
     * 2. Tipe Paket Penyedia (Penyedia & PDS: Jumlah, Diproses, Belum Diproses)
     * 3. Jenis Pengadaan Penyedia (Jumlah, Pagu, Persentase)
     * 4. Tipe Swakelola (Tipe 1, 2, 3, 4: Jumlah & Pagu)
     */
    public static function ringkasan(string $kdKlpd, int $tahun, ?int $satkerId = null): array
    {
        $kdKlpd = strtoupper($kdKlpd);
        $cacheKey = "sipedal_rup_ringkasan_{$kdKlpd}_{$tahun}_" . ($satkerId ?? 'all');

        return Cache::remember($cacheKey, 180, function () use ($kdKlpd, $tahun, $satkerId) {
            return self::hitungRingkasan($kdKlpd, $tahun, $satkerId);
        });
    }

    private static function hitungRingkasan(string $kdKlpd, int $tahun, ?int $satkerId): array
    {
        $satkerClause = $satkerId ? 'AND p.kd_satker = ' . (int) $satkerId : '';
        $satkerClauseSwakelola = $satkerId ? 'AND s.kd_satker = ' . (int) $satkerId : '';

        // 1 & 2 & 3. Agregasi Penyedia
        $sqlPenyedia = <<<SQL
            SELECT
                p.tipe_paket,
                CASE WHEN UPPER(TRIM(p.status_pdn)) = 'PDN' THEN 'PDN' ELSE 'Non-PDN' END AS pdn_status,
                COALESCE(NULLIF(TRIM(p.jenis_pengadaan), ''), 'Lainnya') AS jenis_pengadaan_clean,
                COUNT(DISTINCT p.kd_rup) AS jml_paket,
                COALESCE(SUM(NULLIF(p.pagu::text, '')::numeric), 0) AS total_pagu,
                COUNT(DISTINCT CASE WHEN rp.kd_rup IS NOT NULL THEN p.kd_rup END) AS jml_diproses,
                COUNT(DISTINCT CASE WHEN rp.kd_rup IS NULL THEN p.kd_rup END) AS jml_belum_diproses
            FROM inaproc.rec_rup_paket_penyedia p
            LEFT JOIN (
                SELECT DISTINCT kd_rup FROM public.ref_rup_proses
            ) rp ON rp.kd_rup = p.kd_rup::text
            WHERE p._kode_klpd = :klpd
              AND p._tahun = :tahun
              AND p.status_umumkan_rup IN ('Terumumkan', 'Sudah Diproses')
              AND COALESCE(p.status_delete_rup, 0) = 0
              {$satkerClause}
            GROUP BY p.tipe_paket, pdn_status, jenis_pengadaan_clean
        SQL;

        $rowsPenyedia = [];
        try {
            $rowsPenyedia = DB::select($sqlPenyedia, ['klpd' => $kdKlpd, 'tahun' => $tahun]);
        } catch (\Throwable) {
            //
        }

        // Susun PDN
        $pdn = [
            'pdn' => ['jumlah' => 0, 'pagu' => 0.0, 'persen' => 0.0],
            'non_pdn' => ['jumlah' => 0, 'pagu' => 0.0, 'persen' => 0.0],
            'total' => ['jumlah' => 0, 'pagu' => 0.0],
        ];

        // Susun Tipe Paket
        $tipePaket = [
            'penyedia' => ['jumlah' => 0, 'diproses' => 0, 'belum_diproses' => 0, 'pagu' => 0.0],
            'pds' => ['jumlah' => 0, 'diproses' => 0, 'belum_diproses' => 0, 'pagu' => 0.0],
            'total' => ['jumlah' => 0, 'diproses' => 0, 'belum_diproses' => 0, 'pagu' => 0.0],
        ];

        // Susun Jenis Pengadaan
        $jenisMap = [];
        $totalPaguPenyedia = 0.0;

        foreach ($rowsPenyedia as $r) {
            $jml = (int) $r->jml_paket;
            $pagu = (float) $r->total_pagu;
            $diproses = (int) $r->jml_diproses;
            $belum = (int) $r->jml_belum_diproses;
            $totalPaguPenyedia += $pagu;

            // PDN
            if ($r->pdn_status === 'PDN') {
                $pdn['pdn']['jumlah'] += $jml;
                $pdn['pdn']['pagu'] += $pagu;
            } else {
                $pdn['non_pdn']['jumlah'] += $jml;
                $pdn['non_pdn']['pagu'] += $pagu;
            }
            $pdn['total']['jumlah'] += $jml;
            $pdn['total']['pagu'] += $pagu;

            // Tipe Paket: PDS vs Penyedia
            $tipeKey = (strtoupper(trim((string) $r->tipe_paket)) === 'PDS' || str_contains(strtoupper((string) $r->tipe_paket), 'SWAKELOLA'))
                ? 'pds'
                : 'penyedia';

            $tipePaket[$tipeKey]['jumlah'] += $jml;
            $tipePaket[$tipeKey]['diproses'] += $diproses;
            $tipePaket[$tipeKey]['belum_diproses'] += $belum;
            $tipePaket[$tipeKey]['pagu'] += $pagu;

            $tipePaket['total']['jumlah'] += $jml;
            $tipePaket['total']['diproses'] += $diproses;
            $tipePaket['total']['belum_diproses'] += $belum;
            $tipePaket['total']['pagu'] += $pagu;

            // Jenis Pengadaan
            $jNama = (string) $r->jenis_pengadaan_clean;
            if (! isset($jenisMap[$jNama])) {
                $jenisMap[$jNama] = ['jenis' => $jNama, 'jumlah' => 0, 'pagu' => 0.0, 'persen' => 0.0];
            }
            $jenisMap[$jNama]['jumlah'] += $jml;
            $jenisMap[$jNama]['pagu'] += $pagu;
        }

        // Persentase PDN
        if ($pdn['total']['pagu'] > 0) {
            $pdn['pdn']['persen'] = round(($pdn['pdn']['pagu'] / $pdn['total']['pagu']) * 100, 1);
            $pdn['non_pdn']['persen'] = round(($pdn['non_pdn']['pagu'] / $pdn['total']['pagu']) * 100, 1);
        }

        // Gabungkan varian jenis pengadaan (termasuk komposit titik-koma seperti 'Barang;Jasa Lainnya')
        // ke dalam 4 kategori baku: Barang, Pekerjaan Konstruksi, Jasa Konsultansi, Jasa Lainnya
        $bakuMap = [
            'Pekerjaan Konstruksi' => ['jenis' => 'Pekerjaan Konstruksi', 'jumlah' => 0, 'pagu' => 0.0, 'persen' => 0.0],
            'Barang' => ['jenis' => 'Barang', 'jumlah' => 0, 'pagu' => 0.0, 'persen' => 0.0],
            'Jasa Lainnya' => ['jenis' => 'Jasa Lainnya', 'jumlah' => 0, 'pagu' => 0.0, 'persen' => 0.0],
            'Jasa Konsultansi' => ['jenis' => 'Jasa Konsultansi', 'jumlah' => 0, 'pagu' => 0.0, 'persen' => 0.0],
        ];

        foreach ($jenisMap as $rawNama => $val) {
            $bagian = array_filter(array_map('trim', explode(';', (string) $rawNama)));
            if (empty($bagian)) {
                $bagian = [$rawNama];
            }
            foreach ($bagian as $sub) {
                $target = self::normalisasiJenisPengadaan($sub);
                if ($target && isset($bakuMap[$target])) {
                    $bakuMap[$target]['jumlah'] += $val['jumlah'];
                    $bakuMap[$target]['pagu'] += $val['pagu'];
                }
            }
        }

        $daftarJenis = array_values($bakuMap);
        foreach ($daftarJenis as &$jItem) {
            $jItem['persen'] = $totalPaguPenyedia > 0
                ? round(($jItem['pagu'] / $totalPaguPenyedia) * 100, 1)
                : 0.0;
        }
        unset($jItem);
        usort($daftarJenis, fn ($a, $b) => $b['pagu'] <=> $a['pagu']);

        // 4. Agregasi Swakelola
        $sqlSwakelola = <<<SQL
            SELECT
                s.tipe_swakelola,
                COUNT(*) AS jml_paket,
                COALESCE(SUM(NULLIF(s.pagu::text, '')::numeric), 0) AS total_pagu
            FROM inaproc.rec_rup_paket_swakelola s
            WHERE s._kode_klpd = :klpd
              AND s._tahun = :tahun
              AND s.status_umumkan_rup IN ('Terumumkan', 'Sudah Diproses')
              AND COALESCE(s.status_delete_rup, 0) = 0
              {$satkerClauseSwakelola}
            GROUP BY s.tipe_swakelola
            ORDER BY s.tipe_swakelola
        SQL;

        $rowsSwakelola = [];
        try {
            $rowsSwakelola = DB::select($sqlSwakelola, ['klpd' => $kdKlpd, 'tahun' => $tahun]);
        } catch (\Throwable) {
            //
        }

        $tipeMeta = [
            1 => ['label' => 'Tipe I', 'deskripsi' => 'Dikerjakan sendiri oleh K/L/PD penanggung jawab anggaran'],
            2 => ['label' => 'Tipe II', 'deskripsi' => 'Dikerjakan oleh K/L/PD lain sebagai pelaksana'],
            3 => ['label' => 'Tipe III', 'deskripsi' => 'Dikerjakan oleh Organisasi Kemasyarakatan (Ormas)'],
            4 => ['label' => 'Tipe IV', 'deskripsi' => 'Dikerjakan oleh Kelompok Masyarakat (Pokmas)'],
        ];

        $swakelola = [
            'tipe' => [
                1 => ['label' => 'Tipe I', 'sub' => $tipeMeta[1]['deskripsi'], 'jumlah' => 0, 'pagu' => 0.0, 'persen' => 0.0],
                2 => ['label' => 'Tipe II', 'sub' => $tipeMeta[2]['deskripsi'], 'jumlah' => 0, 'pagu' => 0.0, 'persen' => 0.0],
                3 => ['label' => 'Tipe III', 'sub' => $tipeMeta[3]['deskripsi'], 'jumlah' => 0, 'pagu' => 0.0, 'persen' => 0.0],
                4 => ['label' => 'Tipe IV', 'sub' => $tipeMeta[4]['deskripsi'], 'jumlah' => 0, 'pagu' => 0.0, 'persen' => 0.0],
            ],
            'total' => ['jumlah' => 0, 'pagu' => 0.0],
        ];

        foreach ($rowsSwakelola as $s) {
            $t = (int) $s->tipe_swakelola;
            $jml = (int) $s->jml_paket;
            $pagu = (float) $s->total_pagu;

            if (isset($swakelola['tipe'][$t])) {
                $swakelola['tipe'][$t]['jumlah'] = $jml;
                $swakelola['tipe'][$t]['pagu'] = $pagu;
            }
            $swakelola['total']['jumlah'] += $jml;
            $swakelola['total']['pagu'] += $pagu;
        }

        if ($swakelola['total']['pagu'] > 0) {
            foreach ($swakelola['tipe'] as &$st) {
                $st['persen'] = round(($st['pagu'] / $swakelola['total']['pagu']) * 100, 1);
            }
            unset($st);
        }

        return [
            'pdn' => $pdn,
            'tipe_paket' => $tipePaket,
            'jenis_pengadaan' => $daftarJenis,
            'swakelola' => $swakelola,
            'total_keseluruhan' => [
                'jumlah' => $tipePaket['total']['jumlah'] + $swakelola['total']['jumlah'],
                'pagu' => $tipePaket['total']['pagu'] + $swakelola['total']['pagu'],
            ],
        ];
    }

    /**
     * Rekap per Satuan Kerja:
     * 4 informasi utama:
     * 1. Penyedia (Paket & Pagu)
     * 2. Swakelola (Paket & Pagu)
     * 3. Penyedia Dalam Swakelola / PDS (Paket & Pagu)
     * 4. Total Keseluruhan (Paket & Pagu)
     */
    public static function rekapSatker(
        string $kdKlpd,
        int $tahun,
        ?string $cari = null,
        ?int $satkerId = null,
        string $sort = 'total_pagu',
        string $arah = 'desc',
        int $perHalaman = 10,
        int $halaman = 1
    ): array {
        $kdKlpd = strtoupper($kdKlpd);
        $cacheKey = "sipedal_rup_satker_{$kdKlpd}_{$tahun}_" . ($satkerId ?? 'all');

        $semua = Cache::remember($cacheKey, 180, function () use ($kdKlpd, $tahun, $satkerId) {
            $satkerClauseP = $satkerId ? 'AND p.kd_satker = ' . (int) $satkerId : '';
            $satkerClauseS = $satkerId ? 'AND s.kd_satker = ' . (int) $satkerId : '';

            $sql = <<<SQL
                WITH p_agg AS (
                    SELECT
                        p.kd_satker,
                        MAX(p.kd_satker_str) AS kd_satker_str,
                        MAX(p.nama_satker) AS nama_satker,
                        COUNT(CASE WHEN UPPER(TRIM(p.tipe_paket)) = 'PDS' OR UPPER(TRIM(p.tipe_paket)) LIKE '%SWAKELOLA%' THEN NULL ELSE 1 END) AS jml_penyedia,
                        COALESCE(SUM(CASE WHEN UPPER(TRIM(p.tipe_paket)) = 'PDS' OR UPPER(TRIM(p.tipe_paket)) LIKE '%SWAKELOLA%' THEN 0 ELSE NULLIF(p.pagu::text, '')::numeric END), 0) AS pagu_penyedia,
                        COUNT(CASE WHEN UPPER(TRIM(p.tipe_paket)) = 'PDS' OR UPPER(TRIM(p.tipe_paket)) LIKE '%SWAKELOLA%' THEN 1 ELSE NULL END) AS jml_pds,
                        COALESCE(SUM(CASE WHEN UPPER(TRIM(p.tipe_paket)) = 'PDS' OR UPPER(TRIM(p.tipe_paket)) LIKE '%SWAKELOLA%' THEN NULLIF(p.pagu::text, '')::numeric ELSE 0 END), 0) AS pagu_pds
                    FROM inaproc.rec_rup_paket_penyedia p
                    WHERE p._kode_klpd = :klpd
                      AND p._tahun = :tahun
                      AND p.status_umumkan_rup IN ('Terumumkan', 'Sudah Diproses')
                      AND COALESCE(p.status_delete_rup, 0) = 0
                      {$satkerClauseP}
                    GROUP BY p.kd_satker
                ),
                s_agg AS (
                    SELECT
                        s.kd_satker,
                        MAX(s.kd_satker_str) AS kd_satker_str,
                        MAX(s.nama_satker) AS nama_satker,
                        COUNT(*) AS jml_swakelola,
                        COALESCE(SUM(NULLIF(s.pagu::text, '')::numeric), 0) AS pagu_swakelola
                    FROM inaproc.rec_rup_paket_swakelola s
                    WHERE s._kode_klpd = :klpd
                      AND s._tahun = :tahun
                      AND s.status_umumkan_rup IN ('Terumumkan', 'Sudah Diproses')
                      AND COALESCE(s.status_delete_rup, 0) = 0
                      {$satkerClauseS}
                    GROUP BY s.kd_satker
                ),
                all_satker AS (
                    SELECT kd_satker FROM p_agg
                    UNION
                    SELECT kd_satker FROM s_agg
                )
                SELECT
                    a.kd_satker,
                    COALESCE(m.kd_satker_str, p.kd_satker_str, s.kd_satker_str, '') AS kd_satker_str,
                    COALESCE(m.nama_satker, p.nama_satker, s.nama_satker, 'Satker ' || a.kd_satker) AS nama_satker,
                    COALESCE(p.jml_penyedia, 0)::bigint AS jml_penyedia,
                    COALESCE(p.pagu_penyedia, 0)::numeric AS pagu_penyedia,
                    COALESCE(s.jml_swakelola, 0)::bigint AS jml_swakelola,
                    COALESCE(s.pagu_swakelola, 0)::numeric AS pagu_swakelola,
                    COALESCE(p.jml_pds, 0)::bigint AS jml_pds,
                    COALESCE(p.pagu_pds, 0)::numeric AS pagu_pds,
                    (COALESCE(p.jml_penyedia, 0) + COALESCE(s.jml_swakelola, 0) + COALESCE(p.jml_pds, 0))::bigint AS total_paket,
                    (COALESCE(p.pagu_penyedia, 0) + COALESCE(s.pagu_swakelola, 0) + COALESCE(p.pagu_pds, 0))::numeric AS total_pagu
                FROM all_satker a
                LEFT JOIN p_agg p ON p.kd_satker = a.kd_satker
                LEFT JOIN s_agg s ON s.kd_satker = a.kd_satker
                LEFT JOIN (
                    SELECT DISTINCT ON (kd_satker) kd_satker, kd_satker_str, nama_satker
                    FROM inaproc.rec_rup_master_satker
                ) m ON m.kd_satker = a.kd_satker
                ORDER BY total_pagu DESC
            SQL;

            try {
                $rows = DB::select($sql, ['klpd' => $kdKlpd, 'tahun' => $tahun]);

                return array_map(static function ($r): array {
                    return [
                        'kd_satker' => (int) $r->kd_satker,
                        'kd_satker_str' => (string) ($r->kd_satker_str ?? ''),
                        'nama_satker' => trim((string) $r->nama_satker, " \t\n\r\0\x0B\"'"),
                        'penyedia' => [
                            'paket' => (int) $r->jml_penyedia,
                            'pagu' => (float) $r->pagu_penyedia,
                        ],
                        'swakelola' => [
                            'paket' => (int) $r->jml_swakelola,
                            'pagu' => (float) $r->pagu_swakelola,
                        ],
                        'pds' => [
                            'paket' => (int) $r->jml_pds,
                            'pagu' => (float) $r->pagu_pds,
                        ],
                        'total' => [
                            'paket' => (int) $r->total_paket,
                            'pagu' => (float) $r->total_pagu,
                        ],
                    ];
                }, $rows);
            } catch (\Throwable) {
                return [];
            }
        });

        // Filter pencarian
        if ($cari !== null && trim($cari) !== '') {
            $q = mb_strtolower(trim($cari));
            $semua = array_values(array_filter($semua, static function (array $item) use ($q): bool {
                return str_contains(mb_strtolower($item['nama_satker']), $q)
                    || str_contains((string) $item['kd_satker'], $q)
                    || str_contains(mb_strtolower($item['kd_satker_str']), $q);
            }));
        }

        // Hitung Grand Total
        $grandTotal = [
            'penyedia' => ['paket' => 0, 'pagu' => 0.0],
            'swakelola' => ['paket' => 0, 'pagu' => 0.0],
            'pds' => ['paket' => 0, 'pagu' => 0.0],
            'total' => ['paket' => 0, 'pagu' => 0.0],
        ];

        foreach ($semua as $s) {
            $grandTotal['penyedia']['paket'] += $s['penyedia']['paket'];
            $grandTotal['penyedia']['pagu'] += $s['penyedia']['pagu'];
            $grandTotal['swakelola']['paket'] += $s['swakelola']['paket'];
            $grandTotal['swakelola']['pagu'] += $s['swakelola']['pagu'];
            $grandTotal['pds']['paket'] += $s['pds']['paket'];
            $grandTotal['pds']['pagu'] += $s['pds']['pagu'];
            $grandTotal['total']['paket'] += $s['total']['paket'];
            $grandTotal['total']['pagu'] += $s['total']['pagu'];
        }

        // Pengurutan (sorting)
        $arah = strtolower($arah) === 'asc' ? 'asc' : 'desc';
        usort($semua, static function (array $a, array $b) use ($sort, $arah): int {
            $cmp = match ($sort) {
                'nama_satker' => strcasecmp($a['nama_satker'], $b['nama_satker']),
                'kd_satker' => $a['kd_satker'] <=> $b['kd_satker'],
                'penyedia_paket' => $a['penyedia']['paket'] <=> $b['penyedia']['paket'],
                'penyedia_pagu' => $a['penyedia']['pagu'] <=> $b['penyedia']['pagu'],
                'swakelola_paket' => $a['swakelola']['paket'] <=> $b['swakelola']['paket'],
                'swakelola_pagu' => $a['swakelola']['pagu'] <=> $b['swakelola']['pagu'],
                'pds_paket' => $a['pds']['paket'] <=> $b['pds']['paket'],
                'pds_pagu' => $a['pds']['pagu'] <=> $b['pds']['pagu'],
                'total_paket' => $a['total']['paket'] <=> $b['total']['paket'],
                default => $a['total']['pagu'] <=> $b['total']['pagu'],
            };

            return $arah === 'asc' ? $cmp : -$cmp;
        });

        $total = count($semua);
        $baris = $perHalaman > 0
            ? array_slice($semua, max(0, ($halaman - 1) * $perHalaman), $perHalaman)
            : $semua;

        return [
            'baris' => $baris,
            'grand_total' => $grandTotal,
            'total' => $total,
            'halaman' => $halaman,
            'per_halaman' => $perHalaman,
            'sort' => $sort,
            'arah' => $arah,
        ];
    }

    /**
     * Konversi kode sumber proses ke label antarmuka yang rapi dan kelas badge.
     *
     * @return array{label: string, badge: string, diproses: bool}
     */
    public static function labelProses(?string $sumber): array
    {
        if (empty($sumber)) {
            return [
                'label' => 'Belum Diproses',
                'badge' => 'badge--netral',
                'diproses' => false,
            ];
        }

        $s = strtolower(trim($sumber));
        return match ($s) {
            'e-katalog' => ['label' => 'E-Katalog', 'badge' => 'badge--hijau', 'diproses' => true],
            'tender' => ['label' => 'Tender', 'badge' => 'badge--biru', 'diproses' => true],
            'non-tender', 'nontender' => ['label' => 'Non Tender', 'badge' => 'badge--biru', 'diproses' => true],
            'pencatatan-non-tender', 'pencatatan-nontender' => ['label' => 'Pencatatan Nontender', 'badge' => 'badge--amber', 'diproses' => true],
            'pencatatan-swakelola' => ['label' => 'Pencatatan Swakelola', 'badge' => 'badge--amber', 'diproses' => true],
            'e-kontrak' => ['label' => 'E-Kontrak', 'badge' => 'badge--brand', 'diproses' => true],
            default => ['label' => ucwords(str_replace(['-', '_'], ' ', $s)), 'badge' => 'badge--hijau', 'diproses' => true],
        };
    }

    /**
     * Daftar Paket Penyedia (Penyedia murni & Penyedia Dalam Swakelola / PDS).
     *
     * @return array{items: array, total: int}
     */
    public static function daftarPenyedia(string $kdKlpd, int $tahun, array $filters, int $perHalaman = 25, int $offset = 0): array
    {
        $kdKlpd = strtoupper($kdKlpd);

        $query = DB::table('inaproc.rec_rup_paket_penyedia as p')
            ->where('p._kode_klpd', $kdKlpd)
            ->where('p._tahun', $tahun)
            ->whereIn('p.status_umumkan_rup', self::STATUS_UMUMKAN)
            ->where(function ($q) {
                $q->whereNull('p.status_delete_rup')->orWhere('p.status_delete_rup', 0)->orWhere('p.status_delete_rup', '0');
            });

        // Filter Satker (Mendukung Multi-Select Array atau Nilai Tunggal)
        if (! empty($filters['satker'])) {
            $satkerList = is_array($filters['satker']) ? array_filter($filters['satker']) : [$filters['satker']];
            if (! empty($satkerList)) {
                $query->whereIn('p.kd_satker', array_map('intval', $satkerList));
            }
        }

        // Filter Cari
        if (! empty($filters['cari'])) {
            $c = '%' . trim((string) $filters['cari']) . '%';
            $query->where(function ($q) use ($c) {
                $q->where('p.nama_paket', 'ILIKE', $c)
                    ->orWhere(DB::raw('p.kd_rup::text'), 'ILIKE', $c)
                    ->orWhere('p.nama_satker', 'ILIKE', $c);
            });
        }

        // Filter Tipe Paket
        if (! empty($filters['tipe_paket'])) {
            $t = strtoupper(trim((string) $filters['tipe_paket']));
            if ($t === 'PDS') {
                $query->where(function ($q) {
                    $q->where('p.tipe_paket', 'ILIKE', '%PDS%')
                        ->orWhere('p.tipe_paket', 'ILIKE', '%SWAKELOLA%');
                });
            } else {
                $query->where(function ($q) {
                    $q->whereNull('p.tipe_paket')
                        ->orWhere('p.tipe_paket', 'ILIKE', 'PENYEDIA%')
                        ->where('p.tipe_paket', 'NOT ILIKE', '%SWAKELOLA%')
                        ->where('p.tipe_paket', 'NOT ILIKE', '%PDS%');
                });
            }
        }

        // Filter Status PDN
        if (! empty($filters['status_pdn'])) {
            if ($filters['status_pdn'] === 'PDN') {
                $query->where(DB::raw('UPPER(TRIM(p.status_pdn))'), 'PDN');
            } else {
                $query->where(DB::raw('UPPER(TRIM(p.status_pdn))'), '!=', 'PDN');
            }
        }

        // Filter Jenis Pengadaan (Mendukung multi-select & data komposit bertitik koma seperti 'Barang;Jasa Lainnya')
        if (! empty($filters['jenis_pengadaan'])) {
            $jpList = is_array($filters['jenis_pengadaan'])
                ? array_filter($filters['jenis_pengadaan'])
                : array_filter(array_map('trim', explode(',', (string) $filters['jenis_pengadaan'])));

            if (! empty($jpList)) {
                $query->where(function ($subQ) use ($jpList) {
                    foreach ($jpList as $jpItem) {
                        $jpClean = strtolower(trim((string) $jpItem));
                        if (str_contains($jpClean, 'barang')) {
                            $subQ->orWhere('p.jenis_pengadaan', 'ILIKE', '%Barang%');
                        } elseif (str_contains($jpClean, 'konstruksi')) {
                            $subQ->orWhere('p.jenis_pengadaan', 'ILIKE', '%Konstruksi%');
                        } elseif (str_contains($jpClean, 'konsultansi')) {
                            $subQ->orWhere('p.jenis_pengadaan', 'ILIKE', '%Konsultansi%');
                        } elseif (str_contains($jpClean, 'lainnya')) {
                            $subQ->orWhere('p.jenis_pengadaan', 'ILIKE', '%Lainnya%');
                        } else {
                            $subQ->orWhere('p.jenis_pengadaan', 'ILIKE', "%{$jpItem}%");
                        }
                    }
                });
            }
        }

        // Filter Status / Jenis Proses
        if (! empty($filters['status_proses'])) {
            $sp = strtolower(trim((string) $filters['status_proses']));
            if ($sp === 'belum') {
                $query->whereNotIn(DB::raw('p.kd_rup::text'), function ($sub) {
                    $sub->select('kd_rup')->from('public.ref_rup_proses');
                });
            } elseif ($sp === 'diproses') {
                $query->whereIn(DB::raw('p.kd_rup::text'), function ($sub) {
                    $sub->select('kd_rup')->from('public.ref_rup_proses');
                });
            } else {
                // Filter spesifik nama proses (misal: 'e-katalog', 'tender', dsb)
                $query->whereIn(DB::raw('p.kd_rup::text'), function ($sub) use ($sp) {
                    $sub->select('kd_rup')->from('public.ref_rup_proses')->where('sumber', $sp);
                });
            }
        }

        $total = $query->count();

        // Pengurutan dinamis
        $sort = $filters['sort'] ?? 'kd_rup';
        $arah = strtolower((string) ($filters['arah'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        match ($sort) {
            'pagu' => $query->orderByRaw("COALESCE(NULLIF(p.pagu::text, '')::numeric, 0) {$arah}"),
            'nama_paket' => $query->orderBy('p.nama_paket', $arah),
            'nama_satker' => $query->orderBy('p.nama_satker', $arah),
            'tipe_paket' => $query->orderBy('p.tipe_paket', $arah),
            'jenis_pengadaan' => $query->orderBy('p.jenis_pengadaan', $arah),
            'status_pdn' => $query->orderBy('p.status_pdn', $arah),
            default => $query->orderBy('p.kd_rup', $arah),
        };

        $rows = $query->select([
            'p.kd_rup',
            'p.nama_paket',
            'p.nama_satker',
            'p.kd_satker',
            'p.kd_satker_str',
            'p.tipe_paket',
            'p.jenis_pengadaan',
            'p.metode_pengadaan',
            'p.status_pdn',
            'p.pagu',
            'p.tgl_pengumuman_paket',
            'p.status_umumkan_rup',
        ])
            ->offset($offset)
            ->limit($perHalaman)
            ->get();

        // Cari status proses untuk paket pada halaman ini
        $kdRupList = $rows->pluck('kd_rup')->all();
        $prosesMap = [];
        if (! empty($kdRupList)) {
            try {
                $prosesMap = DB::table('public.ref_rup_proses')
                    ->whereIn('kd_rup', array_map('strval', $kdRupList))
                    ->pluck('sumber', 'kd_rup')
                    ->all();
            } catch (\Throwable) {
                //
            }
        }

        $items = [];
        foreach ($rows as $idx => $r) {
            $kd = (string) $r->kd_rup;
            $sumberProses = $prosesMap[$kd] ?? null;
            $infoProses = self::labelProses($sumberProses);
            $isPds = (strtoupper(trim((string) $r->tipe_paket)) === 'PDS' || str_contains(strtoupper((string) $r->tipe_paket), 'SWAKELOLA'));

            $items[] = [
                'no' => $offset + $idx + 1,
                'kd_rup' => $r->kd_rup,
                'nama_paket' => (string) $r->nama_paket,
                'nama_satker' => trim((string) $r->nama_satker, " \t\n\r\0\x0B\"'"),
                'kd_satker' => $r->kd_satker,
                'tipe_paket' => $isPds ? 'PDS' : 'Penyedia',
                'is_pds' => $isPds,
                'jenis_pengadaan' => (string) ($r->jenis_pengadaan ?: '—'),
                'metode_pengadaan' => (string) ($r->metode_pengadaan ?: '—'),
                'status_pdn' => strtoupper(trim((string) $r->status_pdn)) === 'PDN' ? 'PDN' : 'Non-PDN',
                'nama_proses' => $infoProses['label'],
                'badge_proses' => $infoProses['badge'],
                'is_diproses' => $infoProses['diproses'],
                'sumber_raw' => $sumberProses,
                'pagu' => (float) $r->pagu,
                'tgl_pengumuman' => $r->tgl_pengumuman_paket,
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * Daftar Paket Swakelola.
     *
     * @return array{items: array, total: int}
     */
    public static function daftarSwakelola(string $kdKlpd, int $tahun, array $filters, int $perHalaman = 25, int $offset = 0): array
    {
        $kdKlpd = strtoupper($kdKlpd);

        $query = DB::table('inaproc.rec_rup_paket_swakelola as s')
            ->where('s._kode_klpd', $kdKlpd)
            ->where('s._tahun', $tahun)
            ->whereIn('s.status_umumkan_rup', self::STATUS_UMUMKAN)
            ->where(function ($q) {
                $q->whereNull('s.status_delete_rup')->orWhere('s.status_delete_rup', 0)->orWhere('s.status_delete_rup', '0');
            });

        // Filter Satker (Mendukung Multi-Select Array atau Nilai Tunggal)
        if (! empty($filters['satker'])) {
            $satkerList = is_array($filters['satker']) ? array_filter($filters['satker']) : [$filters['satker']];
            if (! empty($satkerList)) {
                $query->whereIn('s.kd_satker', array_map('intval', $satkerList));
            }
        }

        // Filter Cari
        if (! empty($filters['cari'])) {
            $c = '%' . trim((string) $filters['cari']) . '%';
            $query->where(function ($q) use ($c) {
                $q->where('s.nama_paket', 'ILIKE', $c)
                    ->orWhere(DB::raw('s.kd_rup::text'), 'ILIKE', $c)
                    ->orWhere('s.nama_satker', 'ILIKE', $c);
            });
        }

        // Filter Tipe Swakelola
        if (! empty($filters['tipe_swakelola'])) {
            $query->where('s.tipe_swakelola', (int) $filters['tipe_swakelola']);
        }

        $total = $query->count();

        // Pengurutan dinamis
        $sort = $filters['sort'] ?? 'kd_rup';
        $arah = strtolower((string) ($filters['arah'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        match ($sort) {
            'pagu' => $query->orderByRaw("COALESCE(NULLIF(s.pagu::text, '')::numeric, 0) {$arah}"),
            'nama_paket' => $query->orderBy('s.nama_paket', $arah),
            'nama_satker' => $query->orderBy('s.nama_satker', $arah),
            'tipe_swakelola' => $query->orderBy('s.tipe_swakelola', $arah),
            default => $query->orderBy('s.kd_rup', $arah),
        };

        $rows = $query->select([
            's.kd_rup',
            's.nama_paket',
            's.nama_satker',
            's.kd_satker',
            's.kd_satker_str',
            's.tipe_swakelola',
            's.nama_klpd_penyelenggara',
            's.nama_satker_penyelenggara',
            's.pagu',
            's.tgl_pengumuman_paket',
            's.tgl_awal_pelaksanaan_kontrak',
            's.status_umumkan_rup',
        ])
            ->offset($offset)
            ->limit($perHalaman)
            ->get();

        $items = [];
        foreach ($rows as $idx => $r) {
            $penyelenggara = trim((string) ($r->nama_satker_penyelenggara ?: $r->nama_klpd_penyelenggara ?: '—'));

            $items[] = [
                'no' => $offset + $idx + 1,
                'kd_rup' => $r->kd_rup,
                'nama_paket' => (string) $r->nama_paket,
                'nama_satker' => trim((string) $r->nama_satker, " \t\n\r\0\x0B\"'"),
                'kd_satker' => $r->kd_satker,
                'tipe_swakelola' => (int) $r->tipe_swakelola,
                'label_tipe' => 'Tipe ' . ((int) $r->tipe_swakelola),
                'penyelenggara' => $penyelenggara !== '' ? $penyelenggara : '—',
                'pagu' => (float) $r->pagu,
                'tgl_pengumuman' => $r->tgl_pengumuman_paket,
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * Petakan variasi string ke kategori baku jenis pengadaan.
     */
    public static function normalisasiJenisPengadaan(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $s = strtolower(trim($raw));
        if (str_contains($s, 'barang')) {
            return 'Barang';
        }
        if (str_contains($s, 'konstruksi')) {
            return 'Pekerjaan Konstruksi';
        }
        if (str_contains($s, 'konsultansi')) {
            return 'Jasa Konsultansi';
        }
        if (str_contains($s, 'lainnya')) {
            return 'Jasa Lainnya';
        }

        return null;
    }
}
