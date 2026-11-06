<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ $tenant->warnaUtama() }}">
    <title>Masuk · Sipedal {{ $tenant->namaPendek() }}</title>

    <link rel="icon" href="{{ asset($tenant->logo()) }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}">
    <script src="{{ asset('assets/combobox.js') }}" defer></script>

    {{-- Tema mengikuti KLPD pemilik domain. Lihat layouts/app.blade.php. --}}
    <style>
        :root { {!! $tenant->tokenCss() !!} }
    </style>
</head>
<body>

<main class="login-page">

    {{-- ================= PANEL VISUAL ================= --}}
    <section class="login-visual" aria-label="Gambar ilustrasi">
        @foreach ($latar as $i => $gambar)
            <div
                class="login-visual__slide {{ $i === 0 ? 'is-active' : '' }}"
                style="background-image: url('{{ asset($gambar) }}')"
                data-slide="{{ $i }}"
                role="img"
                aria-label="Ilustrasi {{ $i + 1 }} dari {{ count($latar) }}"
            ></div>
        @endforeach

        <div class="login-visual__scrim"></div>

        <div class="login-visual__content">
            <div class="login-visual__brand">
                <img src="{{ asset($tenant->logoPemda()) }}" alt="Logo {{ $tenant->nama() }}">
                <span class="login-visual__brand-text">
                    <strong>{{ $tenant->nama() }}</strong>
                    <span>{{ $tenant->instansi() }}</span>
                </span>
            </div>

            <div>
                <h1 class="login-visual__headline">
                    Satu pintu data pengadaan<br>untuk keputusan yang lebih cepat.
                </h1>
                <p class="login-visual__sub">
                    Sipedal menghimpun RUP, tender, non-tender, dan e-katalog dari
                    seluruh satuan kerja, lalu menyajikannya sebagai angka yang
                    dapat ditelusuri sampai ke paket.
                </p>
            </div>

            <div class="login-visual__footer">
                <span>© {{ date('Y') }} {{ $tenant->namaPendek() }} · Sipedal</span>
                <div class="login-visual__dots" role="tablist" aria-label="Pilih gambar latar">
                    @foreach ($latar as $i => $gambar)
                        <button
                            type="button"
                            class="{{ $i === 0 ? 'is-active' : '' }}"
                            data-dot="{{ $i }}"
                            role="tab"
                            aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                            aria-label="Gambar {{ $i + 1 }}"
                        ></button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ================= PANEL FORMULIR ================= --}}
    <section class="login-panel">
        <div class="login-panel__tenant">
            <img src="{{ asset($tenant->logo()) }}" alt="">
            <span class="tenant-chip">{{ $tenant->kode() }}</span>
        </div>

        <div class="login-form-wrap">
            <h1>Masuk ke Sipedal</h1>
            <p>Gunakan akun dinas Anda untuk mengakses dashboard pengadaan {{ $tenant->namaPendek() }}.</p>

            @if (session('galat'))
                <div class="banner banner--bahaya" style="margin-top:20px">
                    <span class="banner__ikon"><x-ikon nama="alert" :ukuran="17" /></span>
                    <div>{{ session('galat') }}</div>
                </div>
            @endif

            <form class="login-form" method="POST" action="{{ route('login.aksi') }}">
                @csrf

                <div class="field">
                    <label for="username">Nama pengguna</label>
                    <div class="input-wrap">
                        <span class="input-ikon"><x-ikon nama="user" :ukuran="16" /></span>
                        <input
                            class="input"
                            type="text"
                            id="username"
                            name="username"
                            value="{{ old('username', 'rahmat.hidayat') }}"
                            placeholder="nama.pengguna"
                            autocomplete="username"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <div class="field">
                    <label for="sandi">Kata sandi</label>
                    <div class="input-wrap">
                        <span class="input-ikon"><x-ikon nama="lock" :ukuran="16" /></span>
                        <input
                            class="input has-aksi"
                            type="password"
                            id="sandi"
                            name="sandi"
                            value="demo"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                        >
                        <button class="input-aksi" type="button" id="tombol-sandi" aria-label="Tampilkan kata sandi">
                            <x-ikon nama="eye" :ukuran="16" />
                        </button>
                    </div>
                    <span class="field__hint">Tahap UI/UX: kata sandi tidak diverifikasi.</span>
                </div>

                <div class="row-between">
                    <label class="checkbox">
                        <input type="checkbox" name="ingat" checked>
                        <span>Ingat saya di perangkat ini</span>
                    </label>
                    <a href="#" class="text-sm fw-600" style="color:var(--brand-700)">Lupa sandi?</a>
                </div>

                <button class="btn btn-primary btn-lg btn-block" type="submit">
                    Masuk
                    <x-ikon nama="arrow-right" :ukuran="16" />
                </button>
            </form>

            <div class="pemisah"></div>

            {{-- Pemilih peran: memudahkan menilai tampilan untuk setiap peran. --}}
            <form method="POST" action="{{ route('login.aksi') }}" id="form-peran">
                @csrf
                <input type="hidden" name="username" value="rahmat.hidayat">
                <input type="hidden" name="sandi" value="demo">

                <label class="label-bagian" for="peran-cepat">Masuk cepat sebagai</label>
                <select class="select js-combobox-asal" id="peran-cepat" name="peran">
                    @foreach (config('sipedal.roles') as $kunci => $peran)
                        <option value="{{ $kunci }}">{{ $peran['label'] }} — {{ $peran['lingkup'] }}</option>
                    @endforeach
                </select>
                <p class="field__hint" style="margin-top:6px">
                    Pilihan ini hanya untuk meninjau tampilan tiap peran pada tahap UI/UX.
                </p>

                {{--
                    Satuan kerja. Peran kepala_opd dan ppk wajib memilihnya karena
                    data yang mereka lihat disaring per satuan kerja.
                --}}
                <div class="field" style="margin-top:14px" id="bidang-satker">
                    <label for="rup_stk_id">Satuan kerja</label>
                    <select class="select js-combobox-asal" id="rup_stk_id" name="rup_stk_id"
                            data-placeholder="Pilih satuan kerja" data-cari="Cari satuan kerja"
                            data-keterangan="1">
                        <option value="">Pilih satuan kerja</option>
                        @foreach ($satker as $s)
                            <option value="{{ $s['nilai'] }}" data-keterangan="{{ $s['keterangan'] }}">{{ $s['label'] }}</option>
                        @endforeach
                    </select>
                    <span class="field__hint">
                        Wajib untuk Kepala OPD dan PPK. Bagi peran lain boleh dikosongkan.
                    </span>
                </div>
            </form>

            <p class="text-xs text-mute" style="margin-top:22px;text-align:center">
                Domain ini melayani <strong>{{ $tenant->nama() }}</strong> ·
                tema <strong>{{ $tenant->labelTema() }}</strong>
            </p>
        </div>
    </section>
</main>

<script>
    // ---------- Latar bergantian dengan transisi ----------
    (function () {
        var animasiAktif = {{ !empty($animasi) ? 'true' : 'false' }};
        var slide = Array.prototype.slice.call(document.querySelectorAll('[data-slide]'));
        var dot = Array.prototype.slice.call(document.querySelectorAll('[data-dot]'));
        if (slide.length < 2 || !animasiAktif) return;

        var aktif = 0;
        var timer = null;
        var JEDA = 6000;

        function tampilkan(i) {
            slide.forEach(function (s, n) { s.classList.toggle('is-active', n === i); });
            dot.forEach(function (d, n) {
                d.classList.toggle('is-active', n === i);
                d.setAttribute('aria-selected', n === i ? 'true' : 'false');
            });
            aktif = i;
        }

        function mulai() {
            berhenti();
            timer = setInterval(function () {
                tampilkan((aktif + 1) % slide.length);
            }, JEDA);
        }

        function berhenti() {
            if (timer) clearInterval(timer);
            timer = null;
        }

        dot.forEach(function (d, n) {
            d.addEventListener('click', function () {
                tampilkan(n);
                mulai();
            });
        });

        // Hormati preferensi pengguna yang tidak menyukai animasi.
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            mulai();
        }
    })();

    // ---------- Tampilkan / sembunyikan kata sandi ----------
    (function () {
        var tombol = document.getElementById('tombol-sandi');
        var input = document.getElementById('sandi');
        if (!tombol || !input) return;

        tombol.addEventListener('click', function () {
            var terbuka = input.type === 'text';
            input.type = terbuka ? 'password' : 'text';
            tombol.setAttribute('aria-label', terbuka ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi');
            tombol.innerHTML = terbuka
                ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>'
                : '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9.9 5.2A9.9 9.9 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3 3.9M6.2 6.3A16.7 16.7 0 0 0 2 12s3.6 7 10 7a9.8 9.8 0 0 0 4.2-.9"/><path d="M3 3l18 18"/></svg>';
        });
    })();

    // ---------- Masuk cepat: kirim formulir setelah peran dipilih ----------
    // Ditempel pada select, bukan pada atribut onchange, supaya tetap bekerja
    // baik sebelum maupun sesudah combobox menaikkan kontrol ini.
    (function () {
        var pilih = document.getElementById('peran-cepat');
        var form = document.getElementById('form-peran');
        var bidang = document.getElementById('bidang-satker');
        if (!pilih || !form) return;

        function tandai() {
            if (!bidang) return;
            // Peran ini mengelola satu satuan kerja, jadi pilihannya ditonjolkan.
            var wajib = ['kepala_opd', 'ppk'].indexOf(pilih.value) > -1;
            bidang.classList.toggle('is-wajib', wajib);
        }

        tandai();
        pilih.addEventListener('change', function () {
            tandai();
            form.submit();
        });
    })();
</script>
</body>
</html>
