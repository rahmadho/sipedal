<?php

declare(strict_types=1);

namespace App\Support\Mock;

/**
 * Pilihan satuan kerja untuk combobox.
 *
 * Dipisah dari Mock::satker() agar data tabel (yang punya angka paket, pagu,
 * realisasi) tidak bergantung pada urutan daftar pilihan.
 */
class SatkerPilihan
{
    /**
     * Cari nama satuan kerja berdasarkan kode.
     */
    public static function nama(?int $kode): ?string
    {
        if ($kode === null) {
            return null;
        }

        try {
            $row = \Illuminate\Support\Facades\DB::table('inaproc.rec_rup_master_satker')
                ->where('kd_satker', $kode)
                ->value('nama_satker');
            if ($row) {
                return trim((string) $row, " \t\n\r\0\x0B\"'");
            }
        } catch (\Throwable) {
            //
        }

        foreach (self::semua(600) as $s) {
            if ((int) $s['nilai'] === $kode) {
                return $s['label'];
            }
        }

        return null;
    }

    /**
     * Daftar satuan kerja siap pakai untuk combobox.
     *
     * Setiap butir: nilai (kode satker), label (nama), keterangan (kode).
     *
     * @return array<int, array{nilai:int, label:string, keterangan:string}>
     */
    public static function semua(int $batas = 60, ?string $kdKlpd = null): array
    {
        $hasil = [];

        try {
            $klpd = strtoupper($kdKlpd ?: (string) session('kd_klpd', 'D462'));
            $rows = \Illuminate\Support\Facades\DB::table('inaproc.rec_rup_master_satker')
                ->where('_kode_klpd', $klpd)
                ->orderBy('nama_satker')
                ->limit($batas)
                ->get(['kd_satker', 'kd_satker_str', 'nama_satker']);

            if ($rows->isEmpty()) {
                $rows = \Illuminate\Support\Facades\DB::table('inaproc.rec_rup_paket_penyedia')
                    ->where('_kode_klpd', $klpd)
                    ->select('kd_satker', 'kd_satker_str', 'nama_satker')
                    ->distinct()
                    ->orderBy('nama_satker')
                    ->limit($batas)
                    ->get();
            }

            foreach ($rows as $r) {
                $kode = (int) $r->kd_satker;
                if ($kode <= 0) {
                    continue;
                }
                $nama = trim((string) $r->nama_satker, " \t\n\r\0\x0B\"'");
                $hasil[] = [
                    'nilai' => $kode,
                    'label' => $nama,
                    'keterangan' => !empty($r->kd_satker_str) ? (string) $r->kd_satker_str : 'Kode '.$kode,
                ];
            }
        } catch (\Throwable) {
            //
        }

        // Fallback atau pelengkap agar test unit/feature dengan database kosong tetap jalan
        if (empty($hasil)) {
            foreach (Mock::SATKER as $i => $nama) {
                if (count($hasil) >= $batas) {
                    break;
                }
                $kode = 66_000 + $i;
                $hasil[] = [
                    'nilai' => $kode,
                    'label' => $nama,
                    'keterangan' => 'Kode '.$kode,
                ];
            }
        }

        return $hasil;
    }
}
