<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $tenant->warnaUtama() }}">

    <title>@yield('judul', 'Dashboard') · Sipedal {{ $tenant->namaPendek() }}</title>

    <link rel="icon" href="{{ asset($tenant->logo()) }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}">
    <script src="{{ asset('assets/combobox.js') }}" defer></script>

    {{--
        INTI TEMA PER KLPD
        Satu-satunya tempat warna brand ditentukan. Karena seluruh komponen
        membaca CSS custom property, mengganti blok ini mengganti seluruh
        tampilan tanpa menyentuh markup halaman mana pun.
    --}}
    <style>
        :root { {!! $tenant->tokenCss() !!} }
    </style>

    @stack('gaya')
</head>
<body>
<div class="app">

    {{-- ================= SIDEBAR ================= --}}
    <aside class="sidebar" id="sidebar">
        <a href="{{ route('dashboard') }}" class="sidebar__brand" aria-label="Beranda {{ $tenant->nama() }}">
            <img src="{{ asset($tenant->logo()) }}" alt="Logo {{ $tenant->nama() }}">
            <span class="sidebar__brand-text">
                <strong>Sipedal</strong>
                <span>{{ $tenant->namaPendek() }}</span>
            </span>
        </a>

        <nav class="sidebar__nav" aria-label="Navigasi utama">
            @foreach ($menu as $blok)
                <div class="nav-grup">
                    <div class="nav-grup__label">{{ $blok['grup'] }}</div>

                    @foreach ($blok['butir'] as $butir)
                        @php
                            $punyaAnak = ! empty($butir['anak']);

                            // Menu dianggap aktif bila nama rute saat ini cocok,
                            // atau (untuk pantau) parameternya sama.
                            $aktif = request()->routeIs($butir['rute'] ?? '');
                            if (! $aktif && $punyaAnak) {
                                foreach ($butir['anak'] as $anak) {
                                    if (request()->routeIs($anak['rute'] ?? '')) {
                                        $aktif = true;
                                    }
                                }
                            }
                        @endphp

                        @if ($punyaAnak)
                            <button
                                class="nav-item"
                                type="button"
                                aria-expanded="{{ $aktif ? 'true' : 'false' }}"
                                aria-controls="sub-{{ $loop->parent->index }}-{{ $loop->index }}"
                                data-nav-toggle
                            >
                                <span class="nav-item__ikon"><x-ikon :nama="$butir['ikon']" :ukuran="17" /></span>
                                <span class="nav-item__label">{{ $butir['label'] }}</span>
                                <span class="nav-item__caret"><x-ikon nama="chevron-right" :ukuran="13" /></span>
                            </button>

                            <div
                                class="nav-sub"
                                id="sub-{{ $loop->parent->index }}-{{ $loop->index }}"
                                data-nav-sub
                                @if (! $aktif) hidden @endif
                            >
                                <div class="nav-sub__inner">
                                    @foreach ($butir['anak'] as $anak)
                                        @php
                                            $anakAktif = request()->routeIs($anak['rute'] ?? '')
                                                && (empty($anak['param']) || request()->route('jenis') === $anak['param']);
                                        @endphp
                                        <a
                                            class="nav-item {{ $anakAktif ? 'is-active' : '' }}"
                                            href="{{ route($anak['rute'], ! empty($anak['param']) ? ['jenis' => $anak['param']] : []) }}"
                                        >
                                            <span class="nav-sub__dot"></span>
                                            <span class="nav-item__label">{{ $anak['label'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <a class="nav-item {{ $aktif ? 'is-active' : '' }}" href="{{ route($butir['rute']) }}">
                                <span class="nav-item__ikon"><x-ikon :nama="$butir['ikon']" :ukuran="17" /></span>
                                <span class="nav-item__label">{{ $butir['label'] }}</span>
                                @if (! empty($butir['badge']))
                                    <span class="nav-item__badge">{{ $butir['badge'] }}</span>
                                @endif
                            </a>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </nav>

        <div class="sidebar__foot">
            <span class="dot-live"></span>
            <span>{{ $tenant->kode() }} · Data per {{ tgl(now(), true) }}</span>
        </div>
    </aside>

    {{-- ================= AREA UTAMA ================= --}}
    <div class="main">

        {{-- ---------- Topbar ---------- --}}
        <header class="topbar">
            <button class="icon-btn" type="button" @click="sidebar = !sidebar" aria-label="Buka menu" style="display:none" id="tombol-menu">
                <x-ikon nama="menu" :ukuran="19" />
            </button>

            <div class="topbar__crumb">
                <span>{{ $tenant->namaPendek() }}</span>
                <span class="sep">/</span>
                <strong>@yield('judul', 'Dashboard')</strong>
            </div>

            <div class="topbar__search input-wrap">
                <span class="input-ikon"><x-ikon nama="search" :ukuran="15" /></span>
                <input class="input" type="search" placeholder="Cari paket, satker, atau kode…" aria-label="Pencarian global">
            </div>

            <div class="topbar__aksi">
                @php
                    $daftarBulanNavbar = [
                        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
                        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
                    ];
                    $daftarBulanLengkap = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ];
                    $labelPeriode = ($daftarBulanNavbar[$sesiBulan] ?? $sesiBulan) . ' ' . $sesiTahun;
                @endphp

                {{-- Trigger Tombol Pemilih Periode Global --}}
                <div class="periode-picker" style="position:relative">
                    <button class="topbar__periode-btn" type="button" id="periode-btn" aria-haspopup="true" aria-expanded="false" title="Ubah tahun dan bulan acuan data">
                        <x-ikon nama="calendar" :ukuran="15" />
                        <span class="periode-label">{{ $labelPeriode }}</span>
                        <x-ikon nama="chevron-down" :ukuran="12" />
                    </button>

                    {{-- Popover Dropdown Filter Periode --}}
                    <div class="dropdown dropdown--periode" id="periode-panel" hidden style="min-width:240px;padding:12px;right:0">
                        <div class="dropdown__head" style="padding:0 0 8px 0;margin-bottom:10px">
                            <strong style="font-size:12.5px">Filter Periode Data</strong>
                            <span style="font-size:11px">Pilih tahun dan bulan acuan laporan</span>
                        </div>
                        <form method="POST" action="{{ route('dashboard.periode.ganti') }}">
                            @csrf
                            <div class="field" style="margin-bottom:8px">
                                <label for="periode-bulan" style="font-size:11px">Bulan</label>
                                <select name="bulan" id="periode-bulan" class="select select--sm" style="height:32px;padding:4px 24px 4px 8px;font-size:12px">
                                    @foreach ($daftarBulanLengkap as $noBulan => $lblBulan)
                                        <option value="{{ $noBulan }}" @selected($sesiBulan === $noBulan)>{{ $lblBulan }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field" style="margin-bottom:12px">
                                <label for="periode-tahun" style="font-size:11px">Tahun Anggaran</label>
                                <select name="tahun" id="periode-tahun" class="select select--sm" style="height:32px;padding:4px 24px 4px 8px;font-size:12px">
                                    @foreach ([2023, 2024, 2025, 2026, 2027] as $th)
                                        <option value="{{ $th }}" @selected($sesiTahun === $th)>{{ $th }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="btn btn-primary btn-sm btn-block" type="submit" style="width:100%">
                                Terapkan Periode
                            </button>
                        </form>
                    </div>
                </div>

                <div class="topbar__tenant" title="KLPD aktif">
                    <img src="{{ asset($tenant->logoPemda()) }}" alt="">
                    <span>{{ $tenant->kode() }}</span>
                </div>

                <button class="icon-btn" type="button" aria-label="Notifikasi">
                    <x-ikon nama="bell" :ukuran="18" />
                    <span class="icon-btn__dot"></span>
                </button>

                <div class="user-menu" id="user-menu">
                    <button class="user-menu__btn" type="button" aria-haspopup="true" aria-expanded="false" id="user-menu-btn">
                        <span class="avatar">{{ inisial(session('nama', 'Pengguna')) }}</span>
                        <span class="user-menu__meta">
                            <strong>{{ \Illuminate\Support\Str::limit(session('nama', 'Pengguna'), 18) }}</strong>
                            <span>{{ \App\Support\Menu::labelPeran(session('peran', 'operator')) }}</span>
                        </span>
                        <x-ikon nama="chevron-down" :ukuran="14" />
                    </button>

                    <div class="dropdown" id="user-menu-panel" hidden>
                        <div class="dropdown__head">
                            <strong>{{ session('nama', 'Pengguna') }}</strong>
                            <span>{{ session('email') ?: session('username', '—') }}</span>
                        </div>
                        <a class="dropdown__item" href="{{ route('setting.profile') }}">
                            <x-ikon nama="user" :ukuran="15" /> Profil Saya
                        </a>
                        <a class="dropdown__item" href="{{ route('setting.pengaturan') }}">
                            <x-ikon nama="sliders" :ukuran="15" /> Pengaturan
                        </a>
                        <a class="dropdown__item" href="{{ route('setting.log') }}">
                            <x-ikon nama="refresh" :ukuran="15" /> Log Sinkronisasi
                        </a>
                        <div class="dropdown__sep"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown__item is-danger" type="submit">
                                <x-ikon nama="logout" :ukuran="15" /> Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- ---------- Isi halaman ---------- --}}
        <main class="page" id="konten">
            @if (! empty($saringanSatker))
                <div class="banner banner--info" style="margin-bottom:16px">
                    <span class="banner__ikon"><x-ikon nama="filter" :ukuran="16" /></span>
                    <div>
                        Data disaring untuk satuan kerja
                        <strong>{{ $namaSatker ?: ('kode '.$rupStkId) }}</strong>.
                    </div>
                </div>
            @endif

            @yield('konten')
        </main>
    </div>
</div>

<script>
    // Menu submenu buka-tutup. Tanpa pustaka, cukup beberapa baris.
    document.querySelectorAll('[data-nav-toggle]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            var sub = tombol.nextElementSibling;
            if (!sub) return;
            var terbuka = tombol.getAttribute('aria-expanded') === 'true';
            tombol.setAttribute('aria-expanded', terbuka ? 'false' : 'true');
            sub.hidden = terbuka;
        });
    });

    // Dropdown pengguna.
    (function () {
        var tombol = document.getElementById('user-menu-btn');
        var panel = document.getElementById('user-menu-panel');
        var wadah = document.getElementById('user-menu');
        if (!tombol || !panel) return;

        tombol.addEventListener('click', function (e) {
            e.stopPropagation();
            var terbuka = !panel.hidden;
            panel.hidden = terbuka;
            tombol.setAttribute('aria-expanded', terbuka ? 'false' : 'true');
        });

        document.addEventListener('click', function (e) {
            if (!wadah.contains(e.target)) {
                panel.hidden = true;
                tombol.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                panel.hidden = true;
                tombol.setAttribute('aria-expanded', 'false');
            }
        });
    })();

    // Dropdown pemilih periode global.
    (function () {
        var tombol = document.getElementById('periode-btn');
        var panel = document.getElementById('periode-panel');
        var wadah = document.querySelector('.periode-picker');
        if (!tombol || !panel) return;

        tombol.addEventListener('click', function (e) {
            e.stopPropagation();
            var terbuka = !panel.hidden;
            panel.hidden = terbuka;
            tombol.setAttribute('aria-expanded', terbuka ? 'false' : 'true');
        });

        document.addEventListener('click', function (e) {
            if (wadah && !wadah.contains(e.target)) {
                panel.hidden = true;
                tombol.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                panel.hidden = true;
                tombol.setAttribute('aria-expanded', 'false');
            }
        });
    })();

    // Sidebar pada layar sempit.
    (function () {
        var tombol = document.getElementById('tombol-menu');
        var sidebar = document.getElementById('sidebar');
        if (!tombol || !sidebar) return;

        function atur() {
            var sempit = window.innerWidth <= 1024;
            tombol.style.display = sempit ? 'inline-flex' : 'none';
            if (!sempit) sidebar.classList.remove('is-terbuka');
        }
        atur();
        window.addEventListener('resize', atur);

        tombol.addEventListener('click', function () {
            sidebar.classList.toggle('is-terbuka');
        });
    })();
</script>

@stack('skrip')
</body>
</html>
