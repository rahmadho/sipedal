<?php

declare(strict_types=1);

namespace App\Support;

class KlpdList
{
    /**
     * Daftar 20 KLPD resmi se-Sumatera Barat.
     */
    public const DAFTAR = [
        ['kode' => 'D462', 'nama' => 'Pemerintah Daerah Provinsi Sumatera Barat'],
        ['kode' => 'D463', 'nama' => 'Pemerintah Daerah Kabupaten Sijunjung'],
        ['kode' => 'D464', 'nama' => 'Pemerintah Daerah Kabupaten Pesisir Selatan'],
        ['kode' => 'D465', 'nama' => 'Pemerintah Daerah Kabupaten Tanah Datar'],
        ['kode' => 'D466', 'nama' => 'Pemerintah Daerah Kabupaten Dharmasraya'],
        ['kode' => 'D467', 'nama' => 'Pemerintah Daerah Kabupaten Pasaman'],
        ['kode' => 'D468', 'nama' => 'Pemerintah Daerah Kota Sawahlunto'],
        ['kode' => 'D469', 'nama' => 'Pemerintah Daerah Kota Padang Panjang'],
        ['kode' => 'D470', 'nama' => 'Pemerintah Daerah Kota Payakumbuh'],
        ['kode' => 'D471', 'nama' => 'Pemerintah Daerah Kota Padang'],
        ['kode' => 'D472', 'nama' => 'Pemerintah Daerah Kabupaten Agam'],
        ['kode' => 'D473', 'nama' => 'Pemerintah Daerah Kabupaten Padang Pariaman'],
        ['kode' => 'D474', 'nama' => 'Pemerintah Daerah Kota Solok'],
        ['kode' => 'D475', 'nama' => 'Pemerintah Daerah Kabupaten Solok'],
        ['kode' => 'D476', 'nama' => 'Pemerintah Daerah Kabupaten Lima Puluh Kota'],
        ['kode' => 'D477', 'nama' => 'Pemerintah Daerah Kabupaten Pasaman Barat'],
        ['kode' => 'D478', 'nama' => 'Pemerintah Daerah Kabupaten Solok Selatan'],
        ['kode' => 'D479', 'nama' => 'Pemerintah Daerah Kota Bukit Tinggi'],
        ['kode' => 'D480', 'nama' => 'Pemerintah Daerah Kota Pariaman'],
        ['kode' => 'D481', 'nama' => 'Pemerintah Daerah Kabupaten Kepulauan Mentawai'],
    ];

    public static function semua(): array
    {
        return self::DAFTAR;
    }

    public static function nama(?string $kode): ?string
    {
        if (!$kode) return null;
        foreach (self::DAFTAR as $k) {
            if (strtoupper($k['kode']) === strtoupper($kode)) {
                return $k['nama'];
            }
        }
        return null;
    }
}
