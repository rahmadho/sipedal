<?php

namespace App\Support\Mock;

/**
 * Sumber data palsu untuk tahap UI/UX.
 *
 * Semua metode di sini mengembalikan array biasa dengan bentuk yang sama
 * seperti yang nanti dikembalikan query sungguhan. Dengan begitu, saat
 * penyambungan basis data dilakukan, cukup ganti pemanggilan Mock::x()
 * menjadi service query — tampilan tidak perlu diubah.
 *
 * Data dibangkitkan dengan seed tetap agar angka tidak berubah setiap
 * halaman dimuat (penting saat menilai desain dan menguji tata letak).
 */
class Mock
{
    private static array $cache = [];

    /** Angka acuan nyata dari basis data D462/2026 (lihat PRD-DASHBOARD.md Bab 2.1). */
    public const ACUAN = [
        'paket_total' => 16_024,
        'pagu_total' => 6_362_688_994_450,
        'pagu_rata' => 397_518_993,
        'pagu_maks' => 1_500_000_000_000,
        'terealisasi' => 3_663,
        'belum' => 12_361,
        'view_baris' => 3_665,
        'proses' => 3_375,
        'warisan' => 290,
        'satker' => 669,
        'klpd_terdaftar' => 20,
        'klpd_valid' => 9,
        'klpd_403' => 9,
        'klpd_kosong' => 2,
        'endpoint_total' => 65,
        'endpoint_aktif' => 61,
    ];

    /* =====================================================================
       Utilitas pembangkit
       ===================================================================== */

    private static function rng(string $seed): \Random\Randomizer
    {
        return new \Random\Randomizer(new \Random\Engine\Mt19937(crc32($seed)));
    }

    private static function pilih(array $arr, \Random\Randomizer $r): mixed
    {
        return $arr[$r->getInt(0, count($arr) - 1)];
    }

    private static function uang(int $min, int $max, \Random\Randomizer $r): int
    {
        // Dibulatkan ke ratusan ribu agar tampak seperti nilai pengadaan nyata.
        return (int) (round($r->getInt($min, $max) / 100_000) * 100_000);
    }

    /* =====================================================================
       Dimensi
       ===================================================================== */

    public const SATKER = [
        'Dinas Sumber Daya Air dan Bina Konstruksi',
        'Dinas Perkebunan, Tanaman Pangan dan Hortikultura',
        'Dinas Pendidikan',
        'Dinas Perumahan Rakyat, Kawasan Permukiman dan Pertanahan',
        'Dinas Bina Marga, Cipta Karya dan Tata Ruang',
        'Dinas Kesehatan',
        'Dinas Sosial',
        'Dinas Perhubungan',
        'Dinas Kominfo',
        'Dinas Lingkungan Hidup',
        'Dinas Pemuda dan Olahraga',
        'Dinas Pariwisata dan Ekonomi Kreatif',
        'Sekretariat Daerah',
        'Sekretariat DPRD',
        'Badan Perencanaan Pembangunan Daerah',
        'Badan Pengelolaan Keuangan dan Aset Daerah',
        'Badan Kepegawaian dan Pengembangan SDM',
        'Rumah Sakit Umum Daerah dr. Rasidin',
        'Satuan Polisi Pamong Praja',
        'Dinas Kependudukan dan Pencatatan Sipil',
    ];

    public const METODE = [
        'E-Purchasing' => 7_402,
        'Pengadaan Langsung' => 7_035,
        'Dikecualikan' => 1_282,
        'Seleksi' => 132,
        'Tender' => 127,
        'Penunjukan Langsung' => 46,
    ];

    public const JENIS = [
        'Barang' => 8_326,
        'Jasa Lainnya' => 3_488,
        'Pekerjaan Konstruksi' => 2_160,
        'Jasa Konsultansi' => 2_011,
    ];

    public const SUMBER = [
        'e-katalog' => ['langsung' => 1_653, 'warisan' => 222, 'pagu_l' => 1_040_634_896_717, 'pagu_w' => 91_240_993_325],
        'non-tender' => ['langsung' => 1_625, 'warisan' => 61, 'pagu_l' => 203_925_186_682, 'pagu_w' => 11_560_936_676],
        'pencatatan-non-tender' => ['langsung' => 7, 'warisan' => 0, 'pagu_l' => 302_846_100, 'pagu_w' => 0],
        'tender' => ['langsung' => 90, 'warisan' => 7, 'pagu_l' => 70_887_782_870, 'pagu_w' => 2_951_386_600],
    ];

    public const NAMA_PAKET = [
        'Pembangunan Jalan Lingkungan RT 03',
        'Rehabilitasi Gedung Kantor',
        'Pengadaan Komputer dan Perangkat Pendukung',
        'Peningkatan Saluran Drainase Kawasan',
        'Pengadaan Obat dan Bahan Medis Habis Pakai',
        'Belanja Alat Tulis Kantor',
        'Pembangunan Turap Penahan Tanah',
        'Pengadaan Kendaraan Operasional Roda Dua',
        'Rehabilitasi Ruang Kelas Sekolah Dasar',
        'Penyediaan Jasa Kebersihan Kantor',
        'Peningkatan Jalan Beton Poros Desa',
        'Pengadaan Alat Kesehatan Puskesmas',
        'Pembangunan Sumur Bor dan Instalasi Air',
        'Pengadaan Mebel dan Perabot Kantor',
        'Penyediaan Jasa Keamanan Kantor',
        'Perencanaan Teknis Jembatan Penghubung',
        'Pengadaan Bibit Tanaman Produktif',
        'Rehabilitasi Pasar Rakyat',
        'Pengadaan Pakaian Dinas Harian',
        'Pembangunan Taman dan Ruang Terbuka Hijau',
        'Pengadaan Peralatan Laboratorium Lingkungan',
        'Penyediaan Makan Minum Rapat',
        'Peningkatan Kapasitas Saluran Irigasi',
        'Pengadaan Perangkat Jaringan Komputer',
        'Rehabilitasi Gedung Pertemuan',
        'Pembangunan Posyandu Terpadu',
        'Pengadaan Buku Perpustakaan Daerah',
        'Pemasangan Penerangan Jalan Umum',
        'Pengadaan Alat Pemadam Kebakaran',
        'Studi Kelayakan Kawasan Wisata',
    ];

    public const PENYEDIA = [
        'CV Karya Andalas Mandiri',
        'PT Padang Konstruksi Utama',
        'CV Mitra Sejahtera Abadi',
        'PT Sumbar Teknologi Nusantara',
        'CV Berkah Jaya Sentosa',
        'PT Minang Karya Persada',
        'CV Tunas Harapan',
        'PT Cendana Medika Utama',
        'CV Sinar Mas Konstruksi',
        'PT Ranah Digital Solusi',
        'CV Aneka Niaga Mandiri',
        'PT Bukit Barisan Perkasa',
    ];

    public const USER_DEMO = [
        'nama' => 'Rahmat Hidayat, S.T.',
        'username' => 'rahmat.hidayat',
        'email' => 'rahmat.hidayat@padang.go.id', // opsional, tidak wajib & tidak unik
        'peran' => 'super_admin',
        'jabatan' => 'Kepala Bidang Pengadaan Barang/Jasa',
        'nip' => '19850412 201001 1 008',
        'telepon' => '0812-6631-4478',
        'terakhir_masuk' => '2026-10-23 08:42:11',
    ];

    /* =====================================================================
       Ringkasan / KPI
       ===================================================================== */

    public static function kpi(): array
    {
        $a = self::ACUAN;

        return [
            [
                'label' => 'Total Pagu RUP',
                'nilai' => rp($a['pagu_total']),
                'ringkas' => ringkas($a['pagu_total']),
                'sub' => 'Dari ' . angka($a['paket_total']) . ' paket penyedia',
                'tren' => ['arah' => 'naik', 'nilai' => '4,2%', 'ket' => 'vs bulan lalu'],
                'ikon' => 'wallet',
                'rute' => 'perencanaan',
            ],
            [
                'label' => 'Paket Terealisasi',
                'nilai' => angka($a['terealisasi']),
                'ringkas' => persen($a['terealisasi'] / $a['paket_total'] * 100),
                'sub' => 'Cakupan terhadap seluruh paket',
                'tren' => ['arah' => 'naik', 'nilai' => '2,8%', 'ket' => 'vs bulan lalu'],
                'ikon' => 'check-circle',
                'rute' => 'realisasi',
            ],
            [
                'label' => 'Paket Belum Terealisasi',
                'nilai' => angka($a['belum']),
                'ringkas' => persen($a['belum'] / $a['paket_total'] * 100),
                'sub' => 'Perlu tindak lanjut PPK',
                'tren' => ['arah' => 'turun', 'nilai' => '1,9%', 'ket' => 'vs bulan lalu'],
                'ikon' => 'clock',
                'rute' => 'perencanaan',
            ],
            [
                'label' => 'Nilai Realisasi',
                'nilai' => rp(array_sum(array_column(self::SUMBER, 'pagu_l')) + array_sum(array_column(self::SUMBER, 'pagu_w'))),
                'ringkas' => ringkas(array_sum(array_column(self::SUMBER, 'pagu_l')) + array_sum(array_column(self::SUMBER, 'pagu_w'))),
                'sub' => angka($a['view_baris']) . ' baris proses pengadaan',
                'tren' => ['arah' => 'naik', 'nilai' => '6,1%', 'ket' => 'vs bulan lalu'],
                'ikon' => 'trending',
                'rute' => 'realisasi',
            ],
            [
                'label' => 'Rata-rata Pagu per Paket',
                'nilai' => rp($a['pagu_rata']),
                'ringkas' => ringkas($a['pagu_rata']),
                'sub' => 'Median Rp182.400.000',
                'tren' => ['arah' => 'datar', 'nilai' => '0,3%', 'ket' => 'vs bulan lalu'],
                'ikon' => 'scale',
                'rute' => 'perencanaan',
            ],
            [
                'label' => 'Paket Warisan RUP',
                'nilai' => angka($a['warisan']),
                'ringkas' => persen($a['warisan'] / $a['view_baris'] * 100),
                'sub' => 'Dari ' . angka($a['view_baris']) . ' baris realisasi',
                'tren' => ['arah' => 'naik', 'nilai' => '0,7%', 'ket' => 'vs bulan lalu'],
                'ikon' => 'layers',
                'rute' => 'realisasi',
            ],
        ];
    }

    /** Komposisi realisasi per sumber (langsung + warisan). */
    public static function komposisiSumber(): array
    {
        $hasil = [];
        foreach (self::SUMBER as $sumber => $v) {
            $hasil[] = [
                'label' => ucwords(str_replace('-', ' ', $sumber)),
                'langsung' => $v['langsung'],
                'warisan' => $v['warisan'],
                'total' => $v['langsung'] + $v['warisan'],
                'pagu' => $v['pagu_l'] + $v['pagu_w'],
            ];
        }

        return $hasil;
    }

    public static function topSatker(int $batas = 5): array
    {
        $acuan = [
            'Dinas Sumber Daya Air dan Bina Konstruksi' => 519,
            'Dinas Perkebunan, Tanaman Pangan dan Hortikultura' => 470,
            'Dinas Pendidikan' => 415,
            'Dinas Perumahan Rakyat, Kawasan Permukiman dan Pertanahan' => 286,
            'Dinas Bina Marga, Cipta Karya dan Tata Ruang' => 241,
        ];
        $hasil = [];
        foreach (array_slice($acuan, 0, $batas, true) as $nama => $jml) {
            $hasil[] = ['nama' => $nama, 'jumlah' => $jml];
        }

        return $hasil;
    }

    public static function metodePengadaan(): array
    {
        $hasil = [];
        foreach (self::METODE as $nama => $jml) {
            $hasil[] = ['label' => $nama, 'jumlah' => $jml];
        }

        return $hasil;
    }

    /** Tren paket per bulan (12 bulan tahun anggaran). */
    public static function trenBulanan(): array
    {
        $r = self::rng('tren-2026');
        $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        // Pola realistis: naik menjelang triwulan I, turun, lalu naik lagi di akhir tahun.
        $pola = [0.42, 0.68, 0.83, 0.71, 0.55, 0.48, 0.52, 0.61, 0.74, 0.88, 0.96, 0.63];

        $hasil = [];
        foreach ($bulan as $i => $b) {
            $hasil[] = [
                'label' => $b,
                'nilai' => (int) round(1_336 * $pola[$i] + $r->getInt(-40, 40)),
                'pola' => $pola[$i],
            ];
        }

        return $hasil;
    }

    public static function statusTender(): array
    {
        return [
            ['label' => 'Selesai', 'jumlah' => 74, 'warna' => 'hijau'],
            ['label' => 'Gagal/Batal', 'jumlah' => 19, 'warna' => 'merah'],
            ['label' => 'Berlangsung', 'jumlah' => 1, 'warna' => 'biru'],
        ];
    }

    public static function statusNontender(): array
    {
        return [
            ['label' => 'Selesai', 'jumlah' => 1_400, 'warna' => 'hijau'],
            ['label' => 'Gagal/Batal', 'jumlah' => 232, 'warna' => 'merah'],
            ['label' => 'Berlangsung', 'jumlah' => 49, 'warna' => 'biru'],
        ];
    }

    public static function statusEkatalog(): array
    {
        return [
            ['kode' => 'ON_PROCESS', 'label' => 'Sedang Diproses', 'jumlah' => 3_575, 'warna' => 'biru'],
            ['kode' => 'CANCELLED_ON_NEGOTIATION', 'label' => 'Batal saat Negosiasi', 'jumlah' => 671, 'warna' => 'merah'],
            ['kode' => 'COMPLETED', 'label' => 'Selesai', 'jumlah' => 602, 'warna' => 'hijau'],
            ['kode' => 'PAYMENT_OUTSIDE_SYSTEM', 'label' => 'Bayar di Luar Sistem', 'jumlah' => 512, 'warna' => 'amber'],
            ['kode' => 'CANCELLED_ON_REVIEW', 'label' => 'Batal saat Peninjauan', 'jumlah' => 196, 'warna' => 'merah'],
            ['kode' => 'CANCELLED', 'label' => 'Dibatalkan', 'jumlah' => 121, 'warna' => 'merah'],
            ['kode' => 'ON_NEGOTIATION', 'label' => 'Dalam Negosiasi', 'jumlah' => 43, 'warna' => 'amber'],
            ['kode' => 'WAITING_PPK_REVIEW', 'label' => 'Menunggu Tinjauan PPK', 'jumlah' => 41, 'warna' => 'amber'],
        ];
    }

    /* =====================================================================
       Tabel: Perencanaan (RUP)
       ===================================================================== */

    public static function paketRup(int $jumlah = 50, int $offset = 0, string $kunci = 'rup'): array
    {
        $kunci = $kunci . '|' . $offset;
        if (isset(self::$cache[$kunci])) {
            return self::$cache[$kunci];
        }

        $r = self::rng($kunci);
        $hasil = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $no = $offset + $i + 1;
            $satker = self::pilih(self::SATKER, $r);
            $metode = self::pilih(array_keys(self::METODE), $r);
            $jenis = self::pilih(array_keys(self::JENIS), $r);
            $nama = self::pilih(self::NAMA_PAKET, $r);

            // Paket berstatus terealisasi mengikuti rasio acuan 22,9%.
            $terealisasi = $r->getInt(1, 100) <= 23;

            $hasil[] = [
                'kd_rup' => 66_000_000 + $r->getInt(1, 999_999),
                'nama_paket' => $nama . ' TA 2026',
                'pagu' => self::uang(45_000_000, 4_800_000_000, $r),
                'metode' => $metode,
                'jenis' => $jenis,
                'satker' => $satker,
                'tgl_buat' => sprintf('2026-%02d-%02d', $r->getInt(1, 10), $r->getInt(1, 28)),
                'status' => $terealisasi ? 'terealisasi' : 'belum',
                'sumber' => $terealisasi ? self::pilih(array_keys(self::SUMBER), $r) : null,
                'ppk' => self::pilih(['Drs. Hendra Wijaya, M.Si', 'Yulia Sari, S.E.', 'Ir. Bambang Prasetyo', 'Nofriadi, S.T., M.T.'], $r),
                'no' => $no,
            ];
        }

        return self::$cache[$kunci] = $hasil;
    }

    /* =====================================================================
       Tabel: Realisasi RUP
       ===================================================================== */

    public static function realisasi(int $jumlah = 50, int $offset = 0): array
    {
        $kunci = 'realisasi|' . $offset;
        if (isset(self::$cache[$kunci])) {
            return self::$cache[$kunci];
        }

        $r = self::rng($kunci);
        $hasil = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $sumber = self::pilih(array_keys(self::SUMBER), $r);
            $warisan = $r->getInt(1, 100) <= 8;
            $satker = self::pilih(self::SATKER, $r);

            $hasil[] = [
                'kd_rup' => (string) (66_000_000 + $r->getInt(1, 999_999)),
                'nama_paket' => self::pilih(self::NAMA_PAKET, $r) . ' TA 2026',
                'pagu' => self::uang(35_000_000, 3_600_000_000, $r),
                'satker' => $satker,
                'metode' => self::pilih(array_keys(self::METODE), $r),
                'sumber' => $sumber,
                'jenis' => $warisan ? 'warisan' : 'langsung',
                'depth' => $warisan ? $r->getInt(1, 6) : 0,
                'dari_rup' => $warisan ? (string) (66_000_000 + $r->getInt(1, 999_999)) : null,
                'no' => $offset + $i + 1,
            ];
        }

        return self::$cache[$kunci] = $hasil;
    }

    /* =====================================================================
       Tabel: Klaim Paket
       ===================================================================== */

    public static function klaim(int $jumlah = 25, int $offset = 0): array
    {
        $r = self::rng('klaim|' . $offset);
        $status = ['menunggu', 'diverifikasi', 'ditolak', 'selesai'];
        $hasil = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $st = self::pilih($status, $r);
            $hasil[] = [
                'no_klaim' => 'KLM-2026-' . str_pad((string) ($offset + $i + 1), 4, '0', STR_PAD_LEFT),
                'kd_rup' => (string) (66_000_000 + $r->getInt(1, 999_999)),
                'nama_paket' => self::pilih(self::NAMA_PAKET, $r) . ' TA 2026',
                'penyedia' => self::pilih(self::PENYEDIA, $r),
                'nilai' => self::uang(25_000_000, 1_800_000_000, $r),
                'status' => $st,
                'diajukan' => sprintf('2026-%02d-%02d', $r->getInt(6, 10), $r->getInt(1, 28)),
                'verifikator' => self::pilih(['Pokja Pemilihan', 'Pejabat Pengadaan', 'Kepala PBJ'], $r),
                'no' => $offset + $i + 1,
            ];
        }

        return $hasil;
    }

    /* =====================================================================
       Master
       ===================================================================== */

    public static function klpd(): array
    {
        return [
            ['kode' => 'D462', 'nama' => 'Pemerintah Kota Padang', 'instansi' => 'Dinas Pekerjaan Umum dan Penataan Ruang', 'domain' => 'padang.sipedal.test', 'tema' => 'biru', 'status' => 'aktif', 'token' => 'valid', 'satker' => 148, 'paket' => 16_024],
            ['kode' => 'D463', 'nama' => 'Pemerintah Kota Bukittinggi', 'instansi' => 'Dinas Pendidikan', 'domain' => 'bukittinggi.sipedal.test', 'tema' => 'hijau', 'status' => 'aktif', 'token' => 'valid', 'satker' => 62, 'paket' => 3_214],
            ['kode' => 'D464', 'nama' => 'Pemerintah Kota Payakumbuh', 'instansi' => 'Dinas Kesehatan', 'domain' => 'payakumbuh.sipedal.test', 'tema' => 'oranye', 'status' => 'aktif', 'token' => 'ditolak', 'satker' => 41, 'paket' => 0],
            ['kode' => 'D465', 'nama' => 'Pemerintah Kota Solok', 'instansi' => 'Sekretariat Daerah', 'domain' => 'solok.sipedal.test', 'tema' => 'teal', 'status' => 'aktif', 'token' => 'ditolak', 'satker' => 38, 'paket' => 0],
            ['kode' => 'D466', 'nama' => 'Pemerintah Kota Sawahlunto', 'instansi' => 'Dinas Pariwisata', 'domain' => 'sawahlunto.sipedal.test', 'tema' => 'maroon', 'status' => 'aktif', 'token' => 'ditolak', 'satker' => 29, 'paket' => 0],
            ['kode' => 'D467', 'nama' => 'Pemerintah Kota Padang Panjang', 'instansi' => 'Dinas Perhubungan', 'domain' => 'padangpanjang.sipedal.test', 'tema' => 'biru', 'status' => 'aktif', 'token' => 'valid', 'satker' => 33, 'paket' => 1_876],
            ['kode' => 'D468', 'nama' => 'Pemerintah Kota Pariaman', 'instansi' => 'Dinas Kelautan dan Perikanan', 'domain' => 'pariaman.sipedal.test', 'tema' => 'teal', 'status' => 'aktif', 'token' => 'valid', 'satker' => 36, 'paket' => 2_104],
            ['kode' => 'D469', 'nama' => 'Pemerintah Kabupaten Agam', 'instansi' => 'Dinas Pendidikan', 'domain' => 'agam.sipedal.test', 'tema' => 'hijau', 'status' => 'aktif', 'token' => 'valid', 'satker' => 88, 'paket' => 4_512],
            ['kode' => 'D470', 'nama' => 'Pemerintah Kabupaten Pasaman', 'instansi' => 'Dinas Pekerjaan Umum', 'domain' => 'pasaman.sipedal.test', 'tema' => 'oranye', 'status' => 'aktif', 'token' => 'ditolak', 'satker' => 57, 'paket' => 0],
            ['kode' => 'D471', 'nama' => 'Pemerintah Kabupaten Solok Selatan', 'instansi' => 'Bappeda', 'domain' => 'solokselatan.sipedal.test', 'tema' => 'maroon', 'status' => 'aktif', 'token' => 'ditolak', 'satker' => 44, 'paket' => 0],
            ['kode' => 'D472', 'nama' => 'Pemerintah Kabupaten Agam Barat', 'instansi' => 'Dinas Kesehatan', 'domain' => 'agam-barat.sipedal.test', 'tema' => 'biru', 'status' => 'aktif', 'token' => 'kosong', 'satker' => 31, 'paket' => 0],
            ['kode' => 'D475', 'nama' => 'Pemerintah Kabupaten Pesisir Selatan', 'instansi' => 'Dinas Perikanan', 'domain' => 'pesisirselatan.sipedal.test', 'tema' => 'teal', 'status' => 'aktif', 'token' => 'valid', 'satker' => 74, 'paket' => 3_890],
            ['kode' => 'D477', 'nama' => 'Pemerintah Kabupaten Pasaman Barat', 'instansi' => 'Dinas Pertanian', 'domain' => 'pasamanbarat.sipedal.test', 'tema' => 'hijau', 'status' => 'aktif', 'token' => 'kosong', 'satker' => 52, 'paket' => 0],
            ['kode' => 'D478', 'nama' => 'Pemerintah Kabupaten Tanah Datar', 'instansi' => 'Dinas Pariwisata', 'domain' => 'tanahdatar.sipedal.test', 'tema' => 'oranye', 'status' => 'aktif', 'token' => 'valid', 'satker' => 66, 'paket' => 2_780],
            ['kode' => 'D479', 'nama' => 'Pemerintah Kabupaten Sijunjung', 'instansi' => 'Dinas Pendidikan', 'domain' => 'sijunjung.sipedal.test', 'tema' => 'maroon', 'status' => 'aktif', 'token' => 'valid', 'satker' => 48, 'paket' => 2_012],
            ['kode' => 'D480', 'nama' => 'Pemerintah Kabupaten Dharmasraya', 'instansi' => 'Dinas Kesehatan', 'domain' => 'dharmasraya.sipedal.test', 'tema' => 'biru', 'status' => 'aktif', 'token' => 'valid', 'satker' => 43, 'paket' => 1_645],
            ['kode' => 'D481', 'nama' => 'Pemerintah Kabupaten Kepulauan Mentawai', 'instansi' => 'Dinas Perhubungan', 'domain' => 'mentawai.sipedal.test', 'tema' => 'teal', 'status' => 'aktif', 'token' => 'ditolak', 'satker' => 27, 'paket' => 0],
        ];
    }

    public static function satker(int $jumlah = 40, int $offset = 0): array
    {
        $r = self::rng('satker|' . $offset);
        $hasil = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $nama = self::SATKER[$i % count(self::SATKER)];
            $paket = $r->getInt(24, 1_420);
            $pagu = self::uang(2_400_000_000, 480_000_000_000, $r);
            $realisasi = (int) round($pagu * ($r->getInt(8, 68) / 100));

            $hasil[] = [
                'kd_satker' => 66_000 + $offset + $i,
                'nama' => $nama,
                'jenis' => self::pilih(['Dinas', 'Badan', 'Sekretariat', 'RSUD'], $r),
                'paket' => $paket,
                'pagu' => $pagu,
                'realisasi' => $realisasi,
                'serapan' => $pagu > 0 ? round($realisasi / $pagu * 100, 1) : 0,
                'no' => $offset + $i + 1,
            ];
        }

        return $hasil;
    }

    /* =====================================================================
       Report: ITKP
       ===================================================================== */

    public static function itkpBulanan(): array
    {
        $r = self::rng('itkp-bulanan');
        $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $hasil = [];

        foreach ($bulan as $i => $b) {
            $target = 100;
            $paket = $r->getInt(180, 1_640);
            $tepat = (int) round($paket * ($r->getInt(58, 94) / 100));
            $hasil[] = [
                'bulan' => $b,
                'paket' => $paket,
                'tepat_waktu' => $tepat,
                'terlambat' => $paket - $tepat,
                'capaian' => round($tepat / $paket * 100, 1),
                'target' => $target,
                'status' => ($tepat / $paket * 100) >= 80 ? 'tercapai' : 'perhatian',
            ];
        }

        return $hasil;
    }

    public static function itkpTahunan(): array
    {
        return [
            ['indikator' => 'Persentase paket tepat waktu', 'target' => 85.0, 'capaian' => 78.4, 'bobot' => 25],
            ['indikator' => 'Persentase realisasi pagu', 'target' => 90.0, 'capaian' => 82.1, 'bobot' => 25],
            ['indikator' => 'Persentase paket melalui e-purchasing', 'target' => 60.0, 'capaian' => 46.2, 'bobot' => 15],
            ['indikator' => 'Persentase penyedia lokal', 'target' => 70.0, 'capaian' => 74.8, 'bobot' => 15],
            ['indikator' => 'Persentase paket tanpa revisi RUP', 'target' => 80.0, 'capaian' => 66.5, 'bobot' => 10],
            ['indikator' => 'Persentase pengaduan tertangani', 'target' => 100.0, 'capaian' => 91.7, 'bobot' => 10],
        ];
    }

    /* =====================================================================
       Report: Pantau
       ===================================================================== */

    /** Definisi tujuh modul pantau. */
    public static function pantauJenis(): array
    {
        return [
            'rup' => [
                'label' => 'Pantau RUP',
                'deskripsi' => 'Rencana Umum Pengadaan yang belum dan sudah terumumkan.',
                'kode' => 'kd_rup',
                'kolom' => ['Kode RUP', 'Nama Paket', 'Pagu', 'Metode', 'Satker', 'Status Umum'],
            ],
            'tender' => [
                'label' => 'Pantau Tender',
                'deskripsi' => 'Tender dengan metode seleksi/pelelangan beserta statusnya.',
                'kode' => 'kd_tender',
                'kolom' => ['Kode Tender', 'Nama Paket', 'Pagu', 'HPS', 'Status', 'Satker'],
            ],
            'nontender' => [
                'label' => 'Pantau Nontender',
                'deskripsi' => 'Pengadaan langsung dan penunjukan langsung.',
                'kode' => 'kd_nontender',
                'kolom' => ['Kode Nontender', 'Nama Paket', 'Pagu', 'HPS', 'Status', 'Satker'],
            ],
            'pencatatan-nontender' => [
                'label' => 'Pantau Pencatatan Nontender',
                'deskripsi' => 'Pencatatan pengadaan nontender yang dilakukan mandiri oleh OPD.',
                'kode' => 'kd_pencatatan',
                'kolom' => ['Kode Pencatatan', 'Nama Paket', 'Pagu', 'Penyedia', 'Status', 'Satker'],
            ],
            'pencatatan-swakelola' => [
                'label' => 'Pantau Pencatatan Swakelola',
                'deskripsi' => 'Pencatatan paket swakelola beserta pelaksana kegiatan.',
                'kode' => 'kd_swakelola',
                'kolom' => ['Kode Swakelola', 'Nama Paket', 'Pagu', 'Pelaksana', 'Status', 'Satker'],
            ],
            'e-katalog' => [
                'label' => 'Pantau E-Katalog',
                'deskripsi' => 'Paket e-purchasing melalui katalog elektronik.',
                'kode' => 'order_id',
                'kolom' => ['Order ID', 'Nama Paket', 'Nilai', 'Penyedia', 'Status', 'Satker'],
            ],
            'e-kontrak' => [
                'label' => 'Pantau E-Kontrak',
                'deskripsi' => 'Kontrak elektronik yang telah ditandatangani beserta nilai kontrak.',
                'kode' => 'kd_kontrak',
                'kolom' => ['Kode Kontrak', 'Nama Paket', 'Nilai Kontrak', 'Penyedia', 'Status', 'Satker'],
            ],
        ];
    }

    public static function pantau(string $jenis, int $jumlah = 25, int $offset = 0): array
    {
        $r = self::rng('pantau|' . $jenis . '|' . $offset);
        $hasil = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $pagu = self::uang(40_000_000, 3_200_000_000, $r);
            $satker = self::pilih(self::SATKER, $r);

            $baris = [
                'kode' => match ($jenis) {
                    'tender' => (string) (77_000_000 + $r->getInt(1, 99_999)),
                    'nontender' => (string) (88_000_000 + $r->getInt(1, 99_999)),
                    'pencatatan-nontender' => 'PNT-2026-' . str_pad((string) $r->getInt(1, 9_999), 4, '0', STR_PAD_LEFT),
                    'pencatatan-swakelola' => 'SWK-2026-' . str_pad((string) $r->getInt(1, 9_999), 4, '0', STR_PAD_LEFT),
                    'e-katalog' => 'ORD-' . $r->getInt(100_000, 999_999),
                    'e-kontrak' => 'KTR-2026-' . str_pad((string) $r->getInt(1, 9_999), 4, '0', STR_PAD_LEFT),
                    default => (string) (66_000_000 + $r->getInt(1, 999_999)),
                },
                'nama_paket' => self::pilih(self::NAMA_PAKET, $r) . ' TA 2026',
                'pagu' => $pagu,
                'hps' => (int) round($pagu * 0.98),
                'satker' => $satker,
                'status' => self::pilih(['Berlangsung', 'Selesai', 'Gagal/Batal', 'Draft'], $r),
                'penyedia' => self::pilih(self::PENYEDIA, $r),
                'tanggal' => sprintf('2026-%02d-%02d', $r->getInt(1, 10), $r->getInt(1, 28)),
                'no' => $offset + $i + 1,
            ];

            $hasil[] = $baris;
        }

        return $hasil;
    }

    /* =====================================================================
       Setting
       ===================================================================== */

    public static function logSinkronisasi(int $jumlah = 20, int $offset = 0): array
    {
        $r = self::rng('log|' . $offset);
        $endpoint = [
            ['rup-paket-penyedia', 'rup', 'v1'], ['rup-paket-swakelola', 'rup', 'v1'],
            ['tender-pengumuman', 'tender', 'v1'], ['tender-peserta-tender', 'tender', 'v1'],
            ['non-tender-pengumuman', 'tender', 'v1'], ['non-tender-selesai', 'tender', 'v1'],
            ['ekatalog-paket-e-purchasing', 'ekatalog', 'v1'], ['ekatalog-by-produk', 'ekatalog', 'v1'],
            ['master-program', 'master', 'v1'], ['master-satker', 'rup', 'v1'],
        ];
        $status = ['OK', 'OK', 'OK', 'OK', 'KOSONG', 'GAGAL', 'FORBIDDEN', 'OK', 'OK', 'NONAKTIF'];
        $hasil = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $ep = $endpoint[$i % count($endpoint)];
            $st = $status[($i + $offset) % count($status)];
            $rows = $st === 'OK' ? $r->getInt(120, 16_024) : ($st === 'KOSONG' ? 0 : 0);

            $hasil[] = [
                'waktu' => sprintf('2026-10-%02d %02d:%02d:%02d', 23 - intdiv($offset + $i, 8), $r->getInt(1, 23), $r->getInt(0, 59), $r->getInt(0, 59)),
                'endpoint' => $ep[0],
                'grup' => $ep[1],
                'api' => $ep[2],
                'klpd' => 'D462',
                'tahun' => 2026,
                'status' => $st,
                'rows' => $rows,
                'pages' => $st === 'OK' ? $r->getInt(1, 17) : 0,
                'durasi_ms' => $st === 'OK' ? $r->getInt(180, 12_400) : $r->getInt(90, 800),
                'pesan' => match ($st) {
                    'GAGAL' => 'HTTP 500: Internal Server Error',
                    'FORBIDDEN' => 'Ditolak: token tidak diterima (HTTP 403)',
                    'NONAKTIF' => 'Endpoint dinonaktifkan pada registry',
                    'KOSONG' => 'Tidak ada data untuk tahun ini',
                    default => 'Berhasil',
                },
                'no' => $offset + $i + 1,
            ];
        }

        return $hasil;
    }

    public static function command(): array
    {
        return [
            ['nama' => 'inaproc:pull', 'ket' => 'Menarik data seluruh endpoint aktif untuk satu KLPD.', 'args' => '--klpd=D462 --tahun=2026 --sync', 'bahaya' => 'berat'],
            ['nama' => 'inaproc:pull', 'ket' => 'Menarik satu endpoint saja secara langsung (sinkron).', 'args' => '--klpd=D462 --tahun=2026 --endpoint=paket-penyedia --sync', 'bahaya' => 'ringan'],
            ['nama' => 'inaproc:probe', 'ket' => 'Memeriksa ketersediaan endpoint tanpa menyimpan data.', 'args' => '--klpd=D462', 'bahaya' => 'ringan'],
            ['nama' => 'inaproc:validate-tokens', 'ket' => 'Menguji seluruh token KLPD terhadap endpoint acuan.', 'args' => '--tahun=2026', 'bahaya' => 'ringan'],
            ['nama' => 'inaproc:build-realisasi', 'ket' => 'Menghitung ulang realisasi RUP dari data mentah.', 'args' => '', 'bahaya' => 'berat'],
            ['nama' => 'inaproc:endpoints', 'ket' => 'Menampilkan daftar endpoint beserta status aktifnya.', 'args' => '', 'bahaya' => 'aman'],
            ['nama' => 'inaproc:schema-check', 'ket' => 'Memeriksa kesesuaian skema tabel terhadap registry.', 'args' => '', 'bahaya' => 'aman'],
            ['nama' => 'queue:work', 'ket' => 'Menjalankan pekerja antrean untuk tarik data paralel.', 'args' => '--queue=default', 'bahaya' => 'ringan'],
        ];
    }

    public static function pengaturan(): array
    {
        return [
            'umum' => [
                'nama_aplikasi' => 'Sipedal',
                'nama_instansi' => 'Pemerintah Kota Padang',
                'tahun_aktif' => 2026,
                'zona_waktu' => 'Asia/Jakarta',
                'bahasa' => 'Indonesia',
                'format_angka' => 'Indonesia (1.234.567,89)',
            ],
            'tampilan' => [
                'tema_aktif' => 'biru',
                'ukuran_halaman' => 50,
                'tema_gelap' => false,
                'animasi_login' => true,
                'interval_transisi' => 6,
            ],
            'data' => [
                'ambang_basi_jam' => 48,
                'refresh_otomatis' => true,
                'jadwal_refresh' => '04:30',
                'batas_ekspor' => 50_000,
            ],
        ];
    }
}
