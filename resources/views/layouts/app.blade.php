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
