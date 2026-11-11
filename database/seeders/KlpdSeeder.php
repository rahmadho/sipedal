<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KlpdSeeder extends Seeder
{
    public const DATA = [
        [
            "kd_klpd" => "D462",
            "nama" => "Pemerintah Daerah Provinsi Sumatera Barat",
            "alamat" => "Jl Sudirman No 55",
            "ibukota" => "Padang",
            "jenis" => "PROVINSI",
            "prp_id" => "3",
            "kbp_id" => "14675",
            "created_at" => "2023-03-31 09:54:19",
            "updated_at" => "2023-07-20 22:53:35",
            "spse_id" => "16",
            "spse_versi" => "4.5",
            "spse_url" => "https://lpse.sumbarprov.go.id"
        ],
        [
            "kd_klpd" => "D463",
            "nama" => "Pemerintah Daerah Kabupaten Sijunjung",
            "alamat" => "",
            "ibukota" => "Muaro Sijunjung",
            "jenis" => "KABUPATEN",
            "prp_id" => "3",
            "kbp_id" => "14686",
            "created_at" => "2023-03-31 09:54:19",
            "updated_at" => "2023-03-31 09:54:19",
            "spse_id" => "615",
            "spse_versi" => null,
            "spse_url" => "https://lpse.sijunjung.go.id"
        ],
        [
            "kd_klpd" => "D464",
            "nama" => "Pemerintah Daerah Kabupaten Pesisir Selatan",
            "alamat" => "",
            "ibukota" => "Painan",
            "jenis" => "KABUPATEN",
            "prp_id" => "3",
            "kbp_id" => "14688",
            "created_at" => "2023-03-31 09:54:19",
            "updated_at" => "2023-03-31 09:54:19",
            "spse_id" => "599",
            "spse_versi" => null,
            "spse_url" => "https://lpse.pesisirselatankab.go.id"
        ],
        [
            "kd_klpd" => "D465",
            "nama" => "Pemerintah Daerah Kabupaten Tanah Datar",
            "alamat" => "",
            "ibukota" => "Batusangkar",
            "jenis" => "KABUPATEN",
            "prp_id" => "3",
            "kbp_id" => "14689",
            "created_at" => "2023-03-31 09:54:19",
            "updated_at" => "2023-03-31 09:54:19",
            "spse_id" => "157",
            "spse_versi" => null,
            "spse_url" => "https://lpse.tanahdatar.go.id"
        ],
        [
            "kd_klpd" => "D466",
            "nama" => "Pemerintah Daerah Kabupaten Dharmasraya",
            "alamat" => "",
            "ibukota" => "Pulau Punjung",
            "jenis" => "KABUPATEN",
            "prp_id" => "3",
            "kbp_id" => "14677",
            "created_at" => "2023-03-31 09:54:19",
            "updated_at" => "2023-03-31 09:54:19",
            "spse_id" => "443",
            "spse_versi" => null,
            "spse_url" => "https://lpse.dharmasrayakab.go.id"
        ],
        [
            "kd_klpd" => "D467",
            "nama" => "Pemerintah Daerah Kabupaten Pasaman",
            "alamat" => "",
            "ibukota" => "Lubuk Sikaping",
            "jenis" => "KABUPATEN",
            "prp_id" => "3",
            "kbp_id" => "14691",
            "created_at" => "2023-03-31 09:54:19",
            "updated_at" => "2023-03-31 09:54:19",
            "spse_id" => "223",
            "spse_versi" => null,
            "spse_url" => "https://lpse.pasamankab.go.id"
        ],
        [
            "kd_klpd" => "D468",
            "nama" => "Pemerintah Daerah Kota Sawahlunto",
            "alamat" => "",
            "ibukota" => "Sawahlunto",
            "jenis" => "KOTA",
            "prp_id" => "3",
            "kbp_id" => "14676",
            "created_at" => "2023-03-31 09:54:19",
            "updated_at" => "2023-03-31 09:54:19",
            "spse_id" => "656",
            "spse_versi" => null,
            "spse_url" => "https://lpse.sawahluntokota.go.id"
        ],
        [
            "kd_klpd" => "D469",
            "nama" => "Pemerintah Daerah Kota Padang Panjang",
            "alamat" => "",
            "ibukota" => "Padang Panjang",
            "jenis" => "KOTA",
            "prp_id" => "3",
            "kbp_id" => "14679",
            "created_at" => "2023-03-31 09:54:19",
            "updated_at" => "2023-03-31 09:54:19",
            "spse_id" => "714",
            "spse_versi" => null,
            "spse_url" => "https://lpse.padangpanjang.go.id"
        ],
        [
            "kd_klpd" => "D470",
            "nama" => "Pemerintah Daerah Kota Payakumbuh",
            "alamat" => "",
            "ibukota" => "Payakumbuh",
            "jenis" => "KOTA",
            "prp_id" => "3",
            "kbp_id" => "14683",
            "created_at" => "2023-03-31 09:54:19",
            "updated_at" => "2023-03-31 09:54:19",
            "spse_id" => "50",
            "spse_versi" => null,
            "spse_url" => "https://lpse.payakumbuhkota.go.id"
        ],
        [
            "kd_klpd" => "D471",
            "nama" => "Pemerintah Daerah Kota Padang",
            "alamat" => "",
            "ibukota" => "Padang",
            "jenis" => "KOTA",
            "prp_id" => "3",
            "kbp_id" => "14675",
            "created_at" => "2023-03-31 09:54:19",
            "updated_at" => "2023-03-31 09:54:19",
            "spse_id" => "624",
            "spse_versi" => null,
            "spse_url" => "http://lpse.padang.go.id"
        ],
        [
            "kd_klpd" => "D472",
            "nama" => "Pemerintah Daerah Kabupaten Agam",
            "alamat" => "",
            "ibukota" => "Lubuk Basung",
            "jenis" => "KABUPATEN",
            "prp_id" => "3",
            "kbp_id" => "14693",
            "created_at" => "2023-03-31 09:54:19",
            "updated_at" => "2023-03-31 09:54:19",
            "spse_id" => "645",
            "spse_versi" => null,
            "spse_url" => "https://lpse.agamkab.go.id"
        ],
        [
            "kd_klpd" => "D473",
            "nama" => "Pemerintah Daerah Kabupaten Padang Pariaman",
            "alamat" => "",
            "ibukota" => "Parit Melintang",
            "jenis" => "KABUPATEN",
            "prp_id" => "3",
            "kbp_id" => "14692",
            "created_at" => "2023-03-31 09:54:19",
            "updated_at" => "2023-03-31 09:54:19",
            "spse_id" => "717",
            "spse_versi" => null,
            "spse_url" => "https://lpse.padangpariamankab.go.id"
        ],
        [
            "kd_klpd" => "D474",
            "nama" => "Pemerintah Daerah Kota Solok",
            "alamat" => "",
            "ibukota" => "Solok",
            "jenis" => "KOTA",
            "prp_id" => "3",
            "kbp_id" => "14681",
            "created_at" => "2023-03-31 09:54:19",
            "updated_at" => "2023-03-31 09:54:19",
            "spse_id" => "385",
            "spse_versi" => null,
            "spse_url" => "https://lpse.solokkota.go.id"
        ],
        [
            "kd_klpd" => "D475",
            "nama" => "Pemerintah Daerah Kabupaten Solok",
            "alamat" => "",
            "ibukota" => "Arosuka",
            "jenis" => "KABUPATEN",
            "prp_id" => "3",
            "kbp_id" => "14685",
            "created_at" => "2023-03-31 09:54:19",
            "updated_at" => "2023-03-31 09:54:19",
            "spse_id" => "608",
            "spse_versi" => null,
            "spse_url" => "http://lpse.solokkab.go.id"
        ],
        [
            "kd_klpd" => "D476",
            "nama" => "Pemerintah Daerah Kabupaten Lima Puluh Kota",
            "alamat" => "",
            "ibukota" => "Sarilamak",
            "jenis" => "KABUPATEN",
            "prp_id" => "3",
            "kbp_id" => "14690",
            "created_at" => "2023-03-31 09:54:19",
            "updated_at" => "2023-03-31 09:54:19",
            "spse_id" => "610",
            "spse_versi" => null,
            "spse_url" => "https://lpse.limapuluhkotakab.go.id"
        ],
        [
            "kd_klpd" => "D477",
            "nama" => "Pemerintah Daerah Kabupaten Pasaman Barat",
            "alamat" => "",
            "ibukota" => "Simpang Ampek",
            "jenis" => "KABUPATEN",
            "prp_id" => "3",
            "kbp_id" => "14678",
            "created_at" => "2023-03-31 09:54:19",
            "updated_at" => "2023-03-31 09:54:19",
            "spse_id" => "301",
            "spse_versi" => null,
            "spse_url" => "https://lpse.pasamanbaratkab.go.id"
        ],
        [
            "kd_klpd" => "D478",
            "nama" => "Pemerintah Daerah Kabupaten Solok Selatan",
            "alamat" => "",
            "ibukota" => "Padang Aro",
            "jenis" => "KABUPATEN",
            "prp_id" => "3",
            "kbp_id" => "14684",
            "created_at" => "2023-03-31 09:54:19",
            "updated_at" => "2023-03-31 09:54:19",
            "spse_id" => "718",
            "spse_versi" => null,
            "spse_url" => "https://lpse.solselkab.go.id"
        ],
        [
            "kd_klpd" => "D479",
            "nama" => "Pemerintah Daerah Kota Bukit Tinggi",
            "alamat" => "",
            "ibukota" => "Bukittinggi",
            "jenis" => "KOTA",
            "prp_id" => "3",
            "kbp_id" => "14682",
            "created_at" => "2023-03-31 09:54:19",
            "updated_at" => "2023-03-31 09:54:19",
            "spse_id" => "297",
            "spse_versi" => null,
            "spse_url" => "https://lpse.bukittinggikota.go.id"
        ],
        [
            "kd_klpd" => "D480",
            "nama" => "Pemerintah Daerah Kota Pariaman",
            "alamat" => "",
            "ibukota" => "Pariaman",
            "jenis" => "KOTA",
            "prp_id" => "3",
            "kbp_id" => "14680",
            "created_at" => "2023-03-31 09:54:19",
            "updated_at" => "2023-03-31 09:54:19",
            "spse_id" => "663",
            "spse_versi" => null,
            "spse_url" => "https://lpse.pariamankota.go.id"
        ],
        [
            "kd_klpd" => "D481",
            "nama" => "Pemerintah Daerah Kabupaten Kepulauan Mentawai",
            "alamat" => "",
            "ibukota" => "Tuapejat",
            "jenis" => "KABUPATEN",
            "prp_id" => "3",
            "kbp_id" => "14687",
            "created_at" => "2023-03-31 09:54:19",
            "updated_at" => "2023-03-31 09:54:19",
            "spse_id" => "489",
            "spse_versi" => null,
            "spse_url" => "https://lpse.mentawaikab.go.id"
        ]
    ];

    public function run(): void
    {
        foreach (self::DATA as $item) {
            $parsedHost = parse_url($item['spse_url'] ?? '', PHP_URL_HOST);
            $domain = $parsedHost ? str_replace('lpse.', '', $parsedHost) : strtolower($item['kd_klpd']) . '.sipedal.id';

            DB::table('klpds')->updateOrInsert(
                ['kd_klpd' => $item['kd_klpd']],
                [
                    'nama' => $item['nama'],
                    'alamat' => $item['alamat'] ?? null,
                    'ibukota' => $item['ibukota'] ?? null,
                    'jenis' => $item['jenis'] ?? null,
                    'prp_id' => $item['prp_id'] ?? null,
                    'kbp_id' => $item['kbp_id'] ?? null,
                    'spse_id' => $item['spse_id'] ?? null,
                    'spse_versi' => $item['spse_versi'] ?? null,
                    'spse_url' => $item['spse_url'] ?? null,
                    'domain' => $domain,
                    'tema' => 'biru',
                    'status' => 'aktif',
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => $item['updated_at'] ?? now(),
                ]
            );
        }
    }
}
