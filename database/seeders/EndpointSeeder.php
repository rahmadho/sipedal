<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sumber kebenaran daftar endpoint (Lampiran A PRD).
 *
 * 65 endpoint, 61 aktif. Kolom `tabel` TIDAK disimpan di sini — diturunkan
 * dari api + grup + name oleh EndpointRegistry (F2).
 *
 * Seeder idempoten: aman dijalankan berulang.
 */
class EndpointSeeder extends Seeder
{
    /** @var array<int, array{name:string,path:string,api:string,grup:string,scope:string,active:bool,urutan:int}> */
    public const ENDPOINTS = [
            ['name' => 'ekatalog-e-purchasing-by-produk', 'path' => '/api/v1/ekatalog/e-purchasing-by-produk', 'api' => 'v1', 'grup' => 'ekatalog', 'scope' => 'klpd', 'active' => true, 'urutan' => 1],
            ['name' => 'ekatalog-list-kategori-produk', 'path' => '/api/v1/ekatalog/list-kategori-produk', 'api' => 'v1', 'grup' => 'ekatalog', 'scope' => 'derived', 'active' => false, 'urutan' => 2],
            ['name' => 'ekatalog-list-produk-penyedia', 'path' => '/api/v1/ekatalog/list-produk-penyedia', 'api' => 'v1', 'grup' => 'ekatalog', 'scope' => 'derived', 'active' => false, 'urutan' => 3],
            ['name' => 'ekatalog-paket-e-purchasing', 'path' => '/api/v1/ekatalog/paket-e-purchasing', 'api' => 'v1', 'grup' => 'ekatalog', 'scope' => 'klpd', 'active' => true, 'urutan' => 4],
            ['name' => 'ekatalog-penyedia-detail', 'path' => '/api/v1/ekatalog/penyedia-detail', 'api' => 'v1', 'grup' => 'ekatalog', 'scope' => 'derived', 'active' => false, 'urutan' => 5],
            ['name' => 'master-program', 'path' => '/api/v1/master/program', 'api' => 'v1', 'grup' => 'master', 'scope' => 'tahun', 'active' => true, 'urutan' => 6],
            ['name' => 'history-kaji-ulang', 'path' => '/api/v1/rup/history-kaji-ulang', 'api' => 'v1', 'grup' => 'rup', 'scope' => 'klpd', 'active' => true, 'urutan' => 7],
            ['name' => 'master-satker', 'path' => '/api/v1/rup/master-satker', 'api' => 'v1', 'grup' => 'rup', 'scope' => 'klpd', 'active' => true, 'urutan' => 8],
            ['name' => 'paket-anggaran-penyedia', 'path' => '/api/v1/rup/paket-anggaran-penyedia', 'api' => 'v1', 'grup' => 'rup', 'scope' => 'klpd', 'active' => true, 'urutan' => 9],
            ['name' => 'paket-anggaran-swakelola', 'path' => '/api/v1/rup/paket-anggaran-swakelola', 'api' => 'v1', 'grup' => 'rup', 'scope' => 'klpd', 'active' => true, 'urutan' => 10],
            ['name' => 'paket-penyedia', 'path' => '/api/v1/rup/paket-penyedia', 'api' => 'v1', 'grup' => 'rup', 'scope' => 'klpd', 'active' => true, 'urutan' => 11],
            ['name' => 'paket-penyedia-terumumkan', 'path' => '/api/v1/rup/paket-penyedia-terumumkan', 'api' => 'v1', 'grup' => 'rup', 'scope' => 'klpd', 'active' => true, 'urutan' => 12],
            ['name' => 'paket-swakelola', 'path' => '/api/v1/rup/paket-swakelola', 'api' => 'v1', 'grup' => 'rup', 'scope' => 'klpd', 'active' => true, 'urutan' => 13],
            ['name' => 'paket-swakelola-terumumkan', 'path' => '/api/v1/rup/paket-swakelola-terumumkan', 'api' => 'v1', 'grup' => 'rup', 'scope' => 'klpd', 'active' => true, 'urutan' => 14],
            ['name' => 'program-master', 'path' => '/api/v1/rup/program-master', 'api' => 'v1', 'grup' => 'rup', 'scope' => 'klpd', 'active' => true, 'urutan' => 15],
            ['name' => 'jadwal-tahapan-non-tender', 'path' => '/api/v1/tender/jadwal-tahapan-non-tender', 'api' => 'v1', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 16],
            ['name' => 'jadwal-tahapan-tender', 'path' => '/api/v1/tender/jadwal-tahapan-tender', 'api' => 'v1', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 17],
            ['name' => 'non-tender-ekontrak', 'path' => '/api/v1/tender/non-tender-ekontrak', 'api' => 'v1', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 18],
            ['name' => 'non-tender-ekontrak-kontrak', 'path' => '/api/v1/tender/non-tender-ekontrak-kontrak', 'api' => 'v1', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 19],
            ['name' => 'non-tender-pengumuman', 'path' => '/api/v1/tender/non-tender-pengumuman', 'api' => 'v1', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 20],
            ['name' => 'non-tender-selesai', 'path' => '/api/v1/tender/non-tender-selesai', 'api' => 'v1', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 21],
            ['name' => 'pencatatan-non-tender', 'path' => '/api/v1/tender/pencatatan-non-tender', 'api' => 'v1', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 22],
            ['name' => 'pencatatan-non-tender-realisasi', 'path' => '/api/v1/tender/pencatatan-non-tender-realisasi', 'api' => 'v1', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 23],
            ['name' => 'pencatatan-swakelola', 'path' => '/api/v1/tender/pencatatan-swakelola', 'api' => 'v1', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 24],
            ['name' => 'pencatatan-swakelola-realisasi', 'path' => '/api/v1/tender/pencatatan-swakelola-realisasi', 'api' => 'v1', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 25],
            ['name' => 'pengumuman', 'path' => '/api/v1/tender/pengumuman', 'api' => 'v1', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 26],
            ['name' => 'peserta-tender', 'path' => '/api/v1/tender/peserta-tender', 'api' => 'v1', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 27],
            ['name' => 'tender-ekontrak', 'path' => '/api/v1/tender/ekontrak', 'api' => 'v1', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 28],
            ['name' => 'tender-ekontrak-kontrak', 'path' => '/api/v1/tender/ekontrak-kontrak', 'api' => 'v1', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 29],
            ['name' => 'tender-selesai', 'path' => '/api/v1/tender/selesai', 'api' => 'v1', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 30],
            ['name' => 'tender-selesai-nilai', 'path' => '/api/v1/tender/selesai-nilai', 'api' => 'v1', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 31],
            ['name' => 'legacy-bela-toko-daring-realisasi', 'path' => '/api/legacy/bela/toko-daring-realisasi', 'api' => 'legacy', 'grup' => 'bela', 'scope' => 'klpd', 'active' => true, 'urutan' => 32],
            ['name' => 'legacy-ekatalog-paket-e-purchasing', 'path' => '/api/legacy/ekatalog/paket-e-purchasing', 'api' => 'legacy', 'grup' => 'ekatalog', 'scope' => 'klpd', 'active' => true, 'urutan' => 33],
            ['name' => 'legacy-ekatalog-penyedia-detail', 'path' => '/api/legacy/ekatalog/penyedia-detail', 'api' => 'legacy', 'grup' => 'ekatalog', 'scope' => 'derived', 'active' => false, 'urutan' => 34],
            ['name' => 'legacy-rup-kegiatan-master', 'path' => '/api/legacy/rup/kegiatan-master', 'api' => 'legacy', 'grup' => 'rup', 'scope' => 'klpd', 'active' => true, 'urutan' => 35],
            ['name' => 'legacy-rup-master-satker', 'path' => '/api/legacy/rup/master-satker', 'api' => 'legacy', 'grup' => 'rup', 'scope' => 'klpd', 'active' => true, 'urutan' => 36],
            ['name' => 'legacy-rup-paket-anggaran-penyedia', 'path' => '/api/legacy/rup/paket-anggaran-penyedia', 'api' => 'legacy', 'grup' => 'rup', 'scope' => 'klpd', 'active' => true, 'urutan' => 37],
            ['name' => 'legacy-rup-paket-anggaran-swakelola', 'path' => '/api/legacy/rup/paket-anggaran-swakelola', 'api' => 'legacy', 'grup' => 'rup', 'scope' => 'klpd', 'active' => true, 'urutan' => 38],
            ['name' => 'legacy-rup-paket-penyedia-lokasi', 'path' => '/api/legacy/rup/paket-penyedia-lokasi', 'api' => 'legacy', 'grup' => 'rup', 'scope' => 'klpd', 'active' => true, 'urutan' => 39],
            ['name' => 'legacy-rup-paket-penyedia-terumumkan', 'path' => '/api/legacy/rup/paket-penyedia-terumumkan', 'api' => 'legacy', 'grup' => 'rup', 'scope' => 'klpd', 'active' => true, 'urutan' => 40],
            ['name' => 'legacy-rup-paket-swakelola-lokasi', 'path' => '/api/legacy/rup/paket-swakelola-lokasi', 'api' => 'legacy', 'grup' => 'rup', 'scope' => 'klpd', 'active' => true, 'urutan' => 41],
            ['name' => 'legacy-rup-paket-swakelola-terumumkan', 'path' => '/api/legacy/rup/paket-swakelola-terumumkan', 'api' => 'legacy', 'grup' => 'rup', 'scope' => 'klpd', 'active' => true, 'urutan' => 42],
            ['name' => 'legacy-rup-program-master', 'path' => '/api/legacy/rup/program-master', 'api' => 'legacy', 'grup' => 'rup', 'scope' => 'klpd', 'active' => true, 'urutan' => 43],
            ['name' => 'legacy-rup-struktur-anggaran-pd', 'path' => '/api/legacy/rup/struktur-anggaran-pd', 'api' => 'legacy', 'grup' => 'rup', 'scope' => 'klpd', 'active' => true, 'urutan' => 44],
            ['name' => 'legacy-rup-sub-kegiatan-master', 'path' => '/api/legacy/rup/sub-kegiatan-master', 'api' => 'legacy', 'grup' => 'rup', 'scope' => 'klpd', 'active' => true, 'urutan' => 45],
            ['name' => 'legacy-tender-jadwal-tahapan-non-tender', 'path' => '/api/legacy/tender/jadwal-tahapan-non-tender', 'api' => 'legacy', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 46],
            ['name' => 'legacy-tender-jadwal-tahapan-tender', 'path' => '/api/legacy/tender/jadwal-tahapan-tender', 'api' => 'legacy', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 47],
            ['name' => 'legacy-tender-non-tender-ekontrak-bapbast', 'path' => '/api/legacy/tender/non-tender-ekontrak-bapbast', 'api' => 'legacy', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 48],
            ['name' => 'legacy-tender-non-tender-ekontrak-kontrak', 'path' => '/api/legacy/tender/non-tender-ekontrak-kontrak', 'api' => 'legacy', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 49],
            ['name' => 'legacy-tender-non-tender-ekontrak-spmkspp', 'path' => '/api/legacy/tender/non-tender-ekontrak-spmkspp', 'api' => 'legacy', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 50],
            ['name' => 'legacy-tender-non-tender-ekontrak-sppbj', 'path' => '/api/legacy/tender/non-tender-ekontrak-sppbj', 'api' => 'legacy', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 51],
            ['name' => 'legacy-tender-non-tender-pengumuman', 'path' => '/api/legacy/tender/non-tender-pengumuman', 'api' => 'legacy', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 52],
            ['name' => 'legacy-tender-non-tender-selesai', 'path' => '/api/legacy/tender/non-tender-selesai', 'api' => 'legacy', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 53],
            ['name' => 'legacy-tender-pencatatan-non-tender', 'path' => '/api/legacy/tender/pencatatan-non-tender', 'api' => 'legacy', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 54],
            ['name' => 'legacy-tender-pencatatan-non-tender-realisasi', 'path' => '/api/legacy/tender/pencatatan-non-tender-realisasi', 'api' => 'legacy', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 55],
            ['name' => 'legacy-tender-pencatatan-swakelola', 'path' => '/api/legacy/tender/pencatatan-swakelola', 'api' => 'legacy', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 56],
            ['name' => 'legacy-tender-pencatatan-swakelola-realisasi', 'path' => '/api/legacy/tender/pencatatan-swakelola-realisasi', 'api' => 'legacy', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 57],
            ['name' => 'legacy-tender-pengumuman', 'path' => '/api/legacy/tender/pengumuman', 'api' => 'legacy', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 58],
            ['name' => 'legacy-tender-peserta-tender', 'path' => '/api/legacy/tender/peserta-tender', 'api' => 'legacy', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 59],
            ['name' => 'legacy-tender-tender-ekontrak-bapbast', 'path' => '/api/legacy/tender/tender-ekontrak-bapbast', 'api' => 'legacy', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 60],
            ['name' => 'legacy-tender-tender-ekontrak-kontrak', 'path' => '/api/legacy/tender/tender-ekontrak-kontrak', 'api' => 'legacy', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 61],
            ['name' => 'legacy-tender-tender-ekontrak-spmkspp', 'path' => '/api/legacy/tender/tender-ekontrak-spmkspp', 'api' => 'legacy', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 62],
            ['name' => 'legacy-tender-tender-ekontrak-sppbj', 'path' => '/api/legacy/tender/tender-ekontrak-sppbj', 'api' => 'legacy', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 63],
            ['name' => 'legacy-tender-tender-selesai', 'path' => '/api/legacy/tender/tender-selesai', 'api' => 'legacy', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 64],
            ['name' => 'legacy-tender-tender-selesai-nilai', 'path' => '/api/legacy/tender/tender-selesai-nilai', 'api' => 'legacy', 'grup' => 'tender', 'scope' => 'klpd', 'active' => true, 'urutan' => 65],
    ];

    public function run(): void
    {
        $now = now();
        $keep = [];

        foreach (self::ENDPOINTS as $row) {
            $keep[] = $row['name'];

            DB::table('endpoints')->updateOrInsert(
                ['name' => $row['name']],
                $row + ['updated_at' => $now, 'created_at' => $now],
            );
        }

        // Endpoint yang tidak lagi ada di daftar resmi dihapus agar registry
        // tetap identik dengan PRD (idempoten, bukan akumulatif).
        DB::table('endpoints')->whereNotIn('name', $keep)->delete();
    }
}
