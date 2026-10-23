<?php

/**
 * Konfigurasi aplikasi Sipedal — lapisan UI.
 *
 * Tahap ini UI/UX belum tersambung ke basis data: seluruh angka berasal dari
 * App\Support\Mock\Mock. Konfigurasi di bawah mengatur hal-hal yang memang
 * bersifat presentasional (tenant, tema, menu, peran) sehingga nanti dapat
 * dipindahkan ke tabel tanpa mengubah tampilan.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Tenant (KLPD)
    |--------------------------------------------------------------------------
    | Satu baris = satu KLPD = satu domain = satu identitas visual.
    | `domain` dipakai ResolveTenant untuk mencocokkan host permintaan.
    */
    'default_tenant' => 'd462',

    'tenants' => [
        'd462' => [
            'kode' => 'D462',
            'nama' => 'Pemerintah Kota Padang',
            'nama_pendek' => 'Pemko Padang',
            'instansi' => 'Dinas Pekerjaan Umum dan Penataan Ruang',
            'domain' => 'padang.sipedal.test',
            'logo' => 'img/logo/tenant-padang.svg',
            'logo_pemda' => 'img/logo/pemda-padang.svg',
            'tema' => 'biru',
            'login_bg' => [
                'img/login/bg-01.svg',
                'img/login/bg-02.svg',
                'img/login/bg-03.svg',
            ],
            'aktif' => true,
        ],
        'd463' => [
            'kode' => 'D463',
            'nama' => 'Pemerintah Kota Bukittinggi',
            'nama_pendek' => 'Pemko Bukittinggi',
            'instansi' => 'Dinas Pendidikan',
            'domain' => 'bukittinggi.sipedal.test',
            'logo' => 'img/logo/tenant-bukittinggi.svg',
            'logo_pemda' => 'img/logo/pemda-bukittinggi.svg',
            'tema' => 'hijau',
            'login_bg' => [
                'img/login/bg-02.svg',
                'img/login/bg-03.svg',
                'img/login/bg-01.svg',
            ],
            'aktif' => true,
        ],
        'd464' => [
            'kode' => 'D464',
            'nama' => 'Pemerintah Kota Payakumbuh',
            'nama_pendek' => 'Pemko Payakumbuh',
            'instansi' => 'Dinas Kesehatan',
            'domain' => 'payakumbuh.sipedal.test',
            'logo' => 'img/logo/tenant-payakumbuh.svg',
            'logo_pemda' => 'img/logo/pemda-payakumbuh.svg',
            'tema' => 'oranye',
            'login_bg' => [
                'img/login/bg-03.svg',
                'img/login/bg-01.svg',
                'img/login/bg-02.svg',
            ],
            'aktif' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Skema Warna
    |--------------------------------------------------------------------------
    | Setiap tema hanyalah sekumpulan CSS custom property. Karena seluruh
    | komponen membaca variabel ini, mengganti tema tidak menyentuh satu pun
    | markup halaman. Hanya Super Admin yang boleh menetapkan tema per KLPD.
    */
    'tema' => [
        'biru' => [
            'nama' => 'Biru Nusantara',
            'keterangan' => 'Tenang, netral, lazim dipakai instansi pusat.',
            'tokens' => [
                '--brand-50' => '#eff4ff',
                '--brand-100' => '#dbe6fe',
                '--brand-200' => '#bfd3fe',
                '--brand-300' => '#93b4fd',
                '--brand-400' => '#6090fa',
                '--brand-500' => '#3b6df6',
                '--brand-600' => '#1d4ed8',
                '--brand-700' => '#1a3fb0',
                '--brand-800' => '#1b368c',
                '--brand-900' => '#1a316f',
                '--sidebar-bg' => '#0c1a33',
                '--sidebar-hover' => 'rgba(255,255,255,.06)',
                '--sidebar-active' => 'rgba(255,255,255,.11)',
                '--sidebar-text' => 'rgba(255,255,255,.68)',
                '--sidebar-text-strong' => '#ffffff',
                '--accent' => '#1d4ed8',
            ],
        ],
        'hijau' => [
            'nama' => 'Hijau Andalas',
            'keterangan' => 'Menyiratkan pertumbuhan dan pengawasan anggaran.',
            'tokens' => [
                '--brand-50' => '#ecfdf5',
                '--brand-100' => '#d1fae5',
                '--brand-200' => '#a7f3d0',
                '--brand-300' => '#6ee7b7',
                '--brand-400' => '#34d399',
                '--brand-500' => '#10b981',
                '--brand-600' => '#047857',
                '--brand-700' => '#036647',
                '--brand-800' => '#05543c',
                '--brand-900' => '#064534',
                '--sidebar-bg' => '#07231b',
                '--sidebar-hover' => 'rgba(255,255,255,.06)',
                '--sidebar-active' => 'rgba(255,255,255,.11)',
                '--sidebar-text' => 'rgba(255,255,255,.68)',
                '--sidebar-text-strong' => '#ffffff',
                '--accent' => '#047857',
            ],
        ],
        'oranye' => [
            'nama' => 'Oranye Marawa',
            'keterangan' => 'Hangat, kontras tinggi untuk tabel padat.',
            'tokens' => [
                '--brand-50' => '#fff7ed',
                '--brand-100' => '#ffedd5',
                '--brand-200' => '#fed7aa',
                '--brand-300' => '#fdba74',
                '--brand-400' => '#fb923c',
                '--brand-500' => '#f97316',
                '--brand-600' => '#c2410c',
                '--brand-700' => '#9a3412',
                '--brand-800' => '#7c2d12',
                '--brand-900' => '#652a11',
                '--sidebar-bg' => '#29150a',
                '--sidebar-hover' => 'rgba(255,255,255,.06)',
                '--sidebar-active' => 'rgba(255,255,255,.11)',
                '--sidebar-text' => 'rgba(255,255,255,.68)',
                '--sidebar-text-strong' => '#ffffff',
                '--accent' => '#c2410c',
            ],
        ],
        'teal' => [
            'nama' => 'Teal Pesisir',
            'keterangan' => 'Sejuk dan tenang, cocok untuk data keuangan.',
            'tokens' => [
                '--brand-50' => '#f0fdfa',
                '--brand-100' => '#ccfbf1',
                '--brand-200' => '#99f6e4',
                '--brand-300' => '#5eead4',
                '--brand-400' => '#2dd4bf',
                '--brand-500' => '#14b8a6',
                '--brand-600' => '#0f766e',
                '--brand-700' => '#115e59',
                '--brand-800' => '#134e4a',
                '--brand-900' => '#134e4a',
                '--sidebar-bg' => '#062320',
                '--sidebar-hover' => 'rgba(255,255,255,.06)',
                '--sidebar-active' => 'rgba(255,255,255,.11)',
                '--sidebar-text' => 'rgba(255,255,255,.68)',
                '--sidebar-text-strong' => '#ffffff',
                '--accent' => '#0f766e',
            ],
        ],
        'maroon' => [
            'nama' => 'Maroon Klasik',
            'keterangan' => 'Berwibawa, dipakai untuk dokumen resmi.',
            'tokens' => [
                '--brand-50' => '#fdf2f4',
                '--brand-100' => '#fce7ea',
                '--brand-200' => '#f9d0d7',
                '--brand-300' => '#f2a9b7',
                '--brand-400' => '#e77a90',
                '--brand-500' => '#d64d6b',
                '--brand-600' => '#a81f45',
                '--brand-700' => '#8c1a3b',
                '--brand-800' => '#751936',
                '--brand-900' => '#631a31',
                '--sidebar-bg' => '#2a0b15',
                '--sidebar-hover' => 'rgba(255,255,255,.06)',
                '--sidebar-active' => 'rgba(255,255,255,.11)',
                '--sidebar-text' => 'rgba(255,255,255,.68)',
                '--sidebar-text-strong' => '#ffffff',
                '--accent' => '#a81f45',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Peran Pengguna
    |--------------------------------------------------------------------------
    | `akses` menentukan menu yang tampil. Ini hanya lapisan tampilan; aturan
    | otorisasi sebenarnya tetap harus ditegakkan di server pada tahap API.
    */
    'roles' => [
        'super_admin' => [
            'label' => 'Super Admin',
            'ringkas' => 'SA',
            'lingkup' => 'Lintas KLPD',
            'akses' => ['*'],
        ],
        'admin_klpd' => [
            'label' => 'Admin Instansi/KLPD',
            'ringkas' => 'AK',
            'lingkup' => 'Satu KLPD',
            'akses' => ['dashboard', 'perencanaan', 'realisasi', 'klaim', 'akses', 'master.satker',
                'report.itkp', 'report.pantau', 'setting.log', 'setting.command', 'setting.pengaturan', 'setting.profile'],
        ],
        'kepala_opd' => [
            'label' => 'Kepala OPD',
            'ringkas' => 'KO',
            'lingkup' => 'Satu OPD',
            'akses' => ['dashboard', 'perencanaan', 'realisasi', 'klaim', 'report.itkp', 'report.pantau', 'setting.profile'],
        ],
        'ppk' => [
            'label' => 'Pejabat Pembuat Komitmen',
            'ringkas' => 'PPK',
            'lingkup' => 'Satu OPD',
            'akses' => ['dashboard', 'perencanaan', 'realisasi', 'klaim', 'report.pantau', 'setting.profile'],
        ],
        'kepala_pbj' => [
            'label' => 'Kepala PBJ',
            'ringkas' => 'KP',
            'lingkup' => 'Satu KLPD',
            'akses' => ['dashboard', 'perencanaan', 'realisasi', 'report.itkp', 'report.pantau', 'setting.profile'],
        ],
        'pokja' => [
            'label' => 'Pokja Pemilihan',
            'ringkas' => 'PK',
            'lingkup' => 'Satu KLPD',
            'akses' => ['dashboard', 'realisasi', 'report.pantau', 'setting.profile'],
        ],
        'pejabat_pengadaan' => [
            'label' => 'Pejabat Pengadaan',
            'ringkas' => 'PP',
            'lingkup' => 'Satu OPD',
            'akses' => ['dashboard', 'realisasi', 'klaim', 'report.pantau', 'setting.profile'],
        ],
        'monev' => [
            'label' => 'Monitoring dan Evaluasi',
            'ringkas' => 'ME',
            'lingkup' => 'Satu KLPD',
            'akses' => ['dashboard', 'perencanaan', 'realisasi', 'report.itkp', 'report.pantau', 'setting.log', 'setting.profile'],
        ],
        'operator' => [
            'label' => 'Operator',
            'ringkas' => 'OP',
            'lingkup' => 'Satu OPD',
            'akses' => ['dashboard', 'perencanaan', 'realisasi', 'setting.log', 'setting.command', 'setting.profile'],
        ],
        'admin_pbj' => [
            'label' => 'Admin PBJ',
            'ringkas' => 'AP',
            'lingkup' => 'Satu KLPD',
            'akses' => ['dashboard', 'perencanaan', 'realisasi', 'klaim', 'akses', 'master.satker',
                'report.itkp', 'report.pantau', 'setting.log', 'setting.profile'],
        ],
        'penyedia' => [
            'label' => 'Penyedia Viewer',
            'ringkas' => 'PV',
            'lingkup' => 'Baca terbatas',
            'akses' => ['dashboard', 'report.pantau', 'setting.profile'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Navigasi
    |--------------------------------------------------------------------------
    | `anak` menjadikan item dapat dibuka-tutup (submenu). `rute` harus berupa
    | nama rute; `aktif` adalah pola nama rute untuk penanda menu aktif.
    */
    'menu' => [
        [
            'grup' => 'Main',
            'butir' => [
                ['kunci' => 'dashboard', 'label' => 'Home', 'ikon' => 'home', 'rute' => 'dashboard'],
                ['kunci' => 'perencanaan', 'label' => 'Perencanaan', 'ikon' => 'clipboard', 'rute' => 'perencanaan'],
                ['kunci' => 'realisasi', 'label' => 'Realisasi RUP', 'ikon' => 'check-circle', 'rute' => 'realisasi'],
                ['kunci' => 'klaim', 'label' => 'Klaim Paket', 'ikon' => 'inbox', 'rute' => 'klaim'],
                ['kunci' => 'akses', 'label' => 'Akses', 'ikon' => 'shield', 'rute' => 'akses'],
            ],
        ],
        [
            'grup' => 'Master',
            'butir' => [
                ['kunci' => 'master.klpd', 'label' => 'KLPD/Instansi', 'ikon' => 'building', 'rute' => 'master.klpd'],
                ['kunci' => 'master.satker', 'label' => 'Satuan Kerja', 'ikon' => 'layers', 'rute' => 'master.satker'],
            ],
        ],
        [
            'grup' => 'Report',
            'butir' => [
                [
                    'kunci' => 'report.itkp',
                    'label' => 'ITKP',
                    'ikon' => 'chart',
                    'anak' => [
                        ['kunci' => 'report.itkp', 'label' => 'ITKP Bulanan', 'rute' => 'report.itkp.bulanan'],
                        ['kunci' => 'report.itkp', 'label' => 'ITKP Tahunan', 'rute' => 'report.itkp.tahunan'],
                    ],
                ],
                [
                    'kunci' => 'report.pantau',
                    'label' => 'Pantau',
                    'ikon' => 'radar',
                    'anak' => [
                        ['kunci' => 'report.pantau', 'label' => 'Pantau RUP', 'rute' => 'report.pantau', 'param' => 'rup'],
                        ['kunci' => 'report.pantau', 'label' => 'Pantau Tender', 'rute' => 'report.pantau', 'param' => 'tender'],
                        ['kunci' => 'report.pantau', 'label' => 'Pantau Nontender', 'rute' => 'report.pantau', 'param' => 'nontender'],
                        ['kunci' => 'report.pantau', 'label' => 'Pantau Pencatatan Nontender', 'rute' => 'report.pantau', 'param' => 'pencatatan-nontender'],
                        ['kunci' => 'report.pantau', 'label' => 'Pantau Pencatatan Swakelola', 'rute' => 'report.pantau', 'param' => 'pencatatan-swakelola'],
                        ['kunci' => 'report.pantau', 'label' => 'Pantau E-Katalog', 'rute' => 'report.pantau', 'param' => 'e-katalog'],
                        ['kunci' => 'report.pantau', 'label' => 'Pantau E-Kontrak', 'rute' => 'report.pantau', 'param' => 'e-kontrak'],
                    ],
                ],
            ],
        ],
        [
            'grup' => 'Setting',
            'butir' => [
                ['kunci' => 'setting.log', 'label' => 'Log Sinkronisasi', 'ikon' => 'refresh', 'rute' => 'setting.log'],
                ['kunci' => 'setting.command', 'label' => 'Command', 'ikon' => 'terminal', 'rute' => 'setting.command'],
                ['kunci' => 'setting.pengaturan', 'label' => 'Pengaturan', 'ikon' => 'sliders', 'rute' => 'setting.pengaturan'],
                ['kunci' => 'setting.profile', 'label' => 'Profile', 'ikon' => 'user', 'rute' => 'setting.profile'],
            ],
        ],
    ],
];
