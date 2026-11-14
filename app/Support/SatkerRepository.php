<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;

class SatkerRepository
{
    public const JENIS_OPSI = [
        'Dinas' => 'Dinas',
        'Badan' => 'Badan',
        'Biro' => 'Biro',
        'Sekretariat' => 'Sekretariat',
        'RSUD' => 'RSUD / RSJ',
        'Sekolah' => 'SMAN / SMKN / SLB',
        'UPTD' => 'UPTD / Cabang Dinas',
        'Lainnya' => 'Lainnya',
    ];

    /**
     * Klasifikasikan jenis satuan kerja dari nama satker.
     */
    public static function deteksiJenis(?string $nama): string
    {
        $u = strtoupper(trim((string) $nama, " \t\n\r\0\x0B\"'"));
        if ($u === '') {
            return 'Lainnya';
        }

        if (str_starts_with($u, 'DINAS ')) {
            return 'Dinas';
        }
        if (str_starts_with($u, 'BADAN ')) {
            return 'Badan';
        }
        if (str_starts_with($u, 'BIRO ')) {
            return 'Biro';
        }
        if (str_starts_with($u, 'SEKRETARIAT ')) {
            return 'Sekretariat';
        }
        if (str_contains($u, 'RUMAH SAKIT') || str_starts_with($u, 'RSUD') || str_starts_with($u, 'RSJ')) {
            return 'RSUD';
        }
        if (preg_match('/^(SMAN|SMKN|SLBN|SLB|SMA|SMK)/', $u)) {
            return 'Sekolah';
        }
        if (
            str_starts_with($u, 'UPTD ') ||
            str_starts_with($u, 'CABANG DINAS ') ||
            str_starts_with($u, 'BALAI ') ||
            str_starts_with($u, 'UNIT PELAKSANA TEKNIS') ||
            str_starts_with($u, 'PANTI SOSIAL')
        ) {
            return 'UPTD';
        }

        return 'Lainnya';
    }

    /**
     * Ambil daftar seluruh satker beserta agregasi paket, pagu, dan realisasi dari DB ril.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function daftarLengkap(string $kdKlpd, int $tahun): array
    {
        $kdKlpd = strtoupper($kdKlpd);

        try {
            // 1. Master Satker
            $masterRows = DB::table('inaproc.rec_rup_master_satker')
                ->where('_kode_klpd', $kdKlpd)
                ->where('_tahun', $tahun)
                ->select('kd_satker', 'kd_satker_str', 'nama_satker', 'jenis_satker', 'ket_satker', 'status_satker', 'alamat', 'telepon')
                ->get();

            if ($masterRows->isEmpty()) {
                // Fallback jika tahun berjalan belum ada di master_satker, ambil yang tersedia untuk KLPD tsb
                $masterRows = DB::table('inaproc.rec_rup_master_satker')
                    ->where('_kode_klpd', $kdKlpd)
                    ->select('kd_satker', 'kd_satker_str', 'nama_satker', 'jenis_satker', 'ket_satker', 'status_satker', 'alamat', 'telepon')
                    ->get();
            }

            // 2. Agregasi Paket Penyedia
            $penyediaRows = DB::table('inaproc.rec_rup_paket_penyedia')
                ->where('_kode_klpd', $kdKlpd)
                ->where('_tahun', $tahun)
                ->where(function ($q) {
                    $q->whereNull('status_delete_rup')->orWhere('status_delete_rup', '0')->orWhere('status_delete_rup', 0)->orWhere('status_delete_rup', false);
                })
                ->select(
                    'kd_satker',
                    'kd_satker_str',
                    'nama_satker',
                    DB::raw('count(*) as jml_paket'),
                    DB::raw('coalesce(sum(nullif(pagu::text, \'\')::numeric), 0) as total_pagu')
                )
                ->groupBy('kd_satker', 'kd_satker_str', 'nama_satker')
                ->get();

            // 3. Agregasi Paket Swakelola
            $swakelolaRows = DB::table('inaproc.rec_rup_paket_swakelola')
                ->where('_kode_klpd', $kdKlpd)
                ->where('_tahun', $tahun)
                ->where(function ($q) {
                    $q->whereNull('status_delete_rup')->orWhere('status_delete_rup', '0')->orWhere('status_delete_rup', 0)->orWhere('status_delete_rup', false);
                })
                ->select(
                    'kd_satker',
                    'kd_satker_str',
                    'nama_satker',
                    DB::raw('count(*) as jml_paket'),
                    DB::raw('coalesce(sum(nullif(pagu::text, \'\')::numeric), 0) as total_pagu')
                )
                ->groupBy('kd_satker', 'kd_satker_str', 'nama_satker')
                ->get();

            // 4. Agregasi Realisasi E-Katalog (berdasarkan kode_satker = kd_satker_str)
            $ekatMap = DB::table('inaproc.rec_ekatalog_paket_e_purchasing')
                ->where('_kode_klpd', $kdKlpd)
                ->where('_tahun', $tahun)
                ->select(
                    'kode_satker',
                    DB::raw('coalesce(sum(nullif(total::text, \'\')::numeric), 0) as total_realisasi')
                )
                ->groupBy('kode_satker')
                ->pluck('total_realisasi', 'kode_satker')
                ->all();

            // 5. Agregasi Realisasi Tender Selesai (jika tabel berisi data)
            $tenderMap = [];
            if (DB::table('inaproc.rec_tender_selesai_nilai')->where('_kode_klpd', $kdKlpd)->where('_tahun', $tahun)->exists()) {
                $tenderMap = DB::table('inaproc.rec_tender_selesai_nilai')
                    ->where('_kode_klpd', $kdKlpd)
                    ->where('_tahun', $tahun)
                    ->select('kd_satker', DB::raw('coalesce(sum(nullif(nilai_kontrak::text, \'\')::numeric), 0) as total_realisasi'))
                    ->groupBy('kd_satker')
                    ->pluck('total_realisasi', 'kd_satker')
                    ->all();
            }

            // Gabungkan berdasarkan kd_satker
            $satkerMap = [];

            foreach ($masterRows as $m) {
                $id = (int) $m->kd_satker;
                if ($id <= 0) {
                    continue;
                }
                $namaBersih = trim((string) $m->nama_satker, " \t\n\r\0\x0B\"'");
                $satkerMap[$id] = [
                    'kd_satker' => $id,
                    'kd_satker_str' => (string) ($m->kd_satker_str ?? ''),
                    'nama' => $namaBersih,
                    'jenis' => self::deteksiJenis($namaBersih),
                    'ket_satker' => (string) ($m->ket_satker ?? 'SKPD'),
                    'status_satker' => (string) ($m->status_satker ?? 'Aktif'),
                    'alamat' => $m->alamat,
                    'telepon' => $m->telepon,
                    'paket_penyedia' => 0,
                    'paket_swakelola' => 0,
                    'paket' => 0,
                    'pagu' => 0,
                    'realisasi' => 0,
                ];
            }

            foreach ($penyediaRows as $p) {
                $id = (int) $p->kd_satker;
                if ($id <= 0) {
                    continue;
                }
                if (!isset($satkerMap[$id])) {
                    $namaBersih = trim((string) $p->nama_satker, " \t\n\r\0\x0B\"'");
                    $satkerMap[$id] = [
                        'kd_satker' => $id,
                        'kd_satker_str' => (string) ($p->kd_satker_str ?? ''),
                        'nama' => $namaBersih,
                        'jenis' => self::deteksiJenis($namaBersih),
                        'ket_satker' => 'SKPD',
                        'status_satker' => 'Aktif',
                        'alamat' => null,
                        'telepon' => null,
                        'paket_penyedia' => 0,
                        'paket_swakelola' => 0,
                        'paket' => 0,
                        'pagu' => 0,
                        'realisasi' => 0,
                    ];
                }
                $satkerMap[$id]['paket_penyedia'] += (int) $p->jml_paket;
                $satkerMap[$id]['paket'] += (int) $p->jml_paket;
                $satkerMap[$id]['pagu'] += (int) round((float) $p->total_pagu);
                if (empty($satkerMap[$id]['kd_satker_str']) && !empty($p->kd_satker_str)) {
                    $satkerMap[$id]['kd_satker_str'] = (string) $p->kd_satker_str;
                }
            }

            foreach ($swakelolaRows as $s) {
                $id = (int) $s->kd_satker;
                if ($id <= 0) {
                    continue;
                }
                if (!isset($satkerMap[$id])) {
                    $namaBersih = trim((string) $s->nama_satker, " \t\n\r\0\x0B\"'");
                    $satkerMap[$id] = [
                        'kd_satker' => $id,
                        'kd_satker_str' => (string) ($s->kd_satker_str ?? ''),
                        'nama' => $namaBersih,
                        'jenis' => self::deteksiJenis($namaBersih),
                        'ket_satker' => 'SKPD',
                        'status_satker' => 'Aktif',
                        'alamat' => null,
                        'telepon' => null,
                        'paket_penyedia' => 0,
                        'paket_swakelola' => 0,
                        'paket' => 0,
                        'pagu' => 0,
                        'realisasi' => 0,
                    ];
                }
                $satkerMap[$id]['paket_swakelola'] += (int) $s->jml_paket;
                $satkerMap[$id]['paket'] += (int) $s->jml_paket;
                $satkerMap[$id]['pagu'] += (int) round((float) $s->total_pagu);
                if (empty($satkerMap[$id]['kd_satker_str']) && !empty($s->kd_satker_str)) {
                    $satkerMap[$id]['kd_satker_str'] = (string) $s->kd_satker_str;
                }
            }

            // Pasangkan realisasi dan hitung rasio serapan
            foreach ($satkerMap as $id => &$item) {
                $kdStr = $item['kd_satker_str'];
                $realEkat = ($kdStr !== '' && isset($ekatMap[$kdStr])) ? (int) round((float) $ekatMap[$kdStr]) : 0;
                $realTender = isset($tenderMap[$id]) ? (int) round((float) $tenderMap[$id]) : 0;

                $item['realisasi'] = $realEkat + $realTender;
                $item['serapan'] = $item['pagu'] > 0
                    ? round(($item['realisasi'] / $item['pagu']) * 100, 1)
                    : 0.0;
            }
            unset($item);

            // Urutkan: satker yang punya pagu terbesar di atas, lalu alfabetis nama
            $hasil = array_values($satkerMap);
            usort($hasil, static function (array $a, array $b): int {
                if ($b['pagu'] !== $a['pagu']) {
                    return $b['pagu'] <=> $a['pagu'];
                }
                return strcmp($a['nama'], $b['nama']);
            });

            return $hasil;
        } catch (\Throwable) {
            return [];
        }
    }
}
