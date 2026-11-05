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

        foreach (self::semua() as $s) {
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
    public static function semua(int $batas = 60): array
    {
        $hasil = [];

        // Mock::SATKER hanya nama; kode dibuat tetap supaya stabil antar-panggilan.
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

        return $hasil;
    }
}
