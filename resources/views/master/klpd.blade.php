@extends('layouts.app')

@section('judul', 'KLPD / Instansi')

@section('konten')

    <div class="page-head">
        <div class="page-head__title">
            <h1>KLPD / Instansi</h1>
            <p>
                Setiap KLPD memiliki domain, logo, dan skema warna sendiri.
                Penetapan tema dan gambar latar login hanya dapat dilakukan Super Admin.
            </p>
        </div>
        <div class="page-head__aksi">
            <button class="btn btn-ghost btn-sm" type="button">
                <x-ikon nama="download" :ukuran="15" />
                Ekspor
            </button>
            @if (session('peran') === 'super_admin')
                <button class="btn btn-primary btn-sm" type="button" id="tombol-tambah">
                    <x-ikon nama="plus" :ukuran="15" />
                    Tambah KLPD
                </button>
            @endif
        </div>
    </div>

    {{-- Pemberitahuan khusus Super Admin --}}
    @if (session('peran') === 'super_admin')
        <div class="banner banner--info">
            <span class="banner__ikon"><x-ikon nama="palette" :ukuran="17" /></span>
            <div>
                <strong>Anda melihat sebagai Super Admin.</strong>
                Tema yang Anda tetapkan di sini akan berlaku untuk seluruh pengguna pada
                domain KLPD tersebut — bukan hanya tampilan Anda.
            </div>
        </div>
    @endif

    <div class="grid grid-3">
        @foreach ($baris as $k)
            @php
                $temaAktif = config('sipedal.tema.' . $k['tema'], config('sipedal.tema.biru'));
                $warna = $temaAktif['tokens'];
            @endphp

            <div class="card">
                <div class="card__head" style="border-bottom:none;padding-bottom:0">
                    <div class="grow" style="min-width:0">
                        <div class="row gap-2" style="margin-bottom:2px">
                            <span class="badge badge--brand">{{ $k['kode'] }}</span>
                            <span class="badge badge--{{ $k['status'] === 'aktif' ? 'hijau' : 'netral' }}">
                                {{ ucfirst($k['status']) }}
                            </span>
                        </div>
                        <h3 class="truncate" title="{{ $k['nama'] }}">{{ $k['nama'] }}</h3>
                        <p class="truncate" title="{{ $k['instansi'] }}">{{ $k['instansi'] }}</p>
                    </div>

                    {{-- Pratinjau tema: tiga garis warna brand KLPD --}}
                    <div style="width:44px;flex:none">
                        <div class="tema-kartu__contoh" style="height:34px;margin:0">
                            <span style="background:{{ $warna['--brand-600'] }}"></span>
                            <span style="background:{{ $warna['--brand-400'] }}"></span>
                            <span style="background:{{ $warna['--brand-200'] }}"></span>
                        </div>
                    </div>
                </div>

                <div class="card__body" style="padding-top:14px">
                    <dl class="dl">
                        <dt>Domain</dt>
                        <dd class="mono text-xs">{{ $k['domain'] }}</dd>
                        <dt>Tema</dt>
                        <dd>{{ $temaAktif['nama'] }}</dd>
                        <dt>Token API</dt>
                        <dd>
                            <span class="badge badge--{{ $k['token'] === 'valid' ? 'hijau' : ($k['token'] === 'ditolak' ? 'merah' : 'netral') }}">
                                {{ $k['token'] === 'valid' ? 'Valid' : ($k['token'] === 'ditolak' ? 'Ditolak 403' : 'Belum ada') }}
                            </span>
                        </dd>
                        <dt>Satuan kerja</dt>
                        <dd>{{ angka($k['satker']) }}</dd>
                        <dt>Paket RUP</dt>
                        <dd>{{ angka($k['paket']) }}</dd>
                    </dl>
                </div>

                <div class="card__foot" style="justify-content:flex-end">
                    <a class="btn btn-ghost btn-sm" href="{{ route('dashboard', ['klpd' => strtolower($k['kode'])]) }}">
                        <x-ikon nama="eye" :ukuran="14" />
                        Lihat
                    </a>
                    @if (session('peran') === 'super_admin')
                        <button
                            class="btn btn-subtle btn-sm"
                            type="button"
                            data-atur-tema
                            data-kode="{{ $k['kode'] }}"
                            data-nama="{{ $k['nama'] }}"
                            data-tema="{{ $k['tema'] }}"
                            data-domain="{{ $k['domain'] }}"
                        >
                            <x-ikon nama="palette" :ukuran="14" />
                            Atur Tema
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- ================= MODAL PENGATURAN TEMA ================= --}}
    <div class="modal-latar" id="latar-tema" hidden>
        <div class="modal modal--lebar" role="dialog" aria-modal="true" aria-labelledby="judul-tema">
            <div class="modal__head">
                <h2 id="judul-tema">Pengaturan Tampilan KLPD</h2>
                <p id="sub-tema">—</p>
            </div>

            <div class="modal__body">
                <div class="label-bagian">Skema Warna</div>
                <div class="tema-pilih" id="pilih-tema">
                    @foreach (config('sipedal.tema') as $kunci => $t)
                        <button class="tema-kartu" type="button" data-tema-kartu="{{ $kunci }}">
                            <div class="tema-kartu__contoh">
                                <span style="background:{{ $t['tokens']['--brand-600'] }}"></span>
                                <span style="background:{{ $t['tokens']['--brand-400'] }}"></span>
                                <span style="background:{{ $t['tokens']['--brand-200'] }}"></span>
                                <span style="background:{{ $t['tokens']['--brand-50'] }}"></span>
                            </div>
                            <div class="tema-kartu__nama">{{ $t['nama'] }}</div>
                            <div class="tema-kartu__ket">{{ $t['keterangan'] }}</div>
                        </button>
                    @endforeach
                </div>

                <div class="pemisah"></div>

                <div class="label-bagian">Logo Instansi</div>
                <div class="row gap-4 wrap">
                    <div style="width:96px;height:96px;border:1px solid var(--border);border-radius:var(--radius);display:flex;align-items:center;justify-content:center;background:var(--gray-50);padding:10px">
                        <img id="pratinjau-logo" src="{{ asset($tenant->logo()) }}" alt="Pratinjau logo" style="max-height:100%">
                    </div>
                    <div class="grow">
                        <div class="unggah">
                            <div class="unggah__ikon"><x-ikon nama="upload" :ukuran="22" /></div>
                            <p><strong>Klik untuk mengunggah</strong> atau tarik berkas ke sini</p>
                            <small>SVG atau PNG transparan · maksimal 512 KB · rasio 1:1</small>
                        </div>
                    </div>
                </div>

                <div class="pemisah"></div>

                <div class="label-bagian">Gambar Latar Halaman Login</div>
                <p class="text-sm text-mute" style="margin-bottom:10px">
                    Gambar ditampilkan bergantian dengan transisi halus. Urutan dapat diubah
                    dengan menarik gambar.
                </p>
                <div class="galeri-bg">
                    @foreach ($tenant->latarLogin() as $i => $g)
                        <div class="galeri-bg__item is-aktif" style="background-image:url('{{ asset($g) }}')">
                            <button type="button" aria-label="Hapus gambar {{ $i + 1 }}">×</button>
                        </div>
                    @endforeach
                    <div class="galeri-bg__item" style="display:flex;align-items:center;justify-content:center;background:var(--gray-50);border-style:dashed">
                        <span class="text-mute"><x-ikon nama="plus" :ukuran="20" /></span>
                    </div>
                </div>

                <div class="pemisah"></div>

                <div class="row-between wrap gap-3">
                    <div>
                        <div class="fw-600 text-sm">Animasi transisi</div>
                        <div class="text-xs text-mute">Matikan bila perangkat pengguna cenderung lambat</div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" checked>
                        <span class="switch__track"></span>
                    </label>
                </div>

                <div class="row-between wrap gap-3" style="margin-top:12px">
                    <div>
                        <div class="fw-600 text-sm">Jeda antar gambar</div>
                        <div class="text-xs text-mute">Detik</div>
                    </div>
                    <input class="input" type="number" value="6" min="2" max="20" style="width:88px">
                </div>
            </div>

            <div class="modal__foot">
                <button class="btn btn-ghost" type="button" id="batal-tema">Batal</button>
                <button class="btn btn-primary" type="button" id="simpan-tema">
                    <x-ikon nama="check" :ukuran="15" />
                    Simpan
                </button>
            </div>
        </div>
    </div>

    {{-- ================= MODAL TAMBAH KLPD ================= --}}
    <div class="modal-latar" id="latar-tambah" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="judul-tambah">
            <div class="modal__head">
                <h2 id="judul-tambah">Tambah KLPD</h2>
                <p>KLPD baru akan memperoleh domain dan tema tersendiri.</p>
            </div>
            <div class="modal__body">
                <div class="grid grid-2 gap-4">
                    <div class="field">
                        <label for="kode-baru">Kode KLPD</label>
                        <input class="input" id="kode-baru" placeholder="D482">
                    </div>
                    <div class="field">
                        <label for="domain-baru">Domain</label>
                        <input class="input" id="domain-baru" placeholder="klpd.sipedal.id">
                    </div>
                </div>
                <div class="field" style="margin-top:14px">
                    <label for="nama-baru">Nama Instansi</label>
                    <input class="input" id="nama-baru" placeholder="Pemerintah Kabupaten …">
                </div>
                <div class="field" style="margin-top:14px">
                    <label for="tema-baru">Skema Warna</label>
                    <select class="select" id="tema-baru">
                        @foreach (config('sipedal.tema') as $kunci => $t)
                            <option value="{{ $kunci }}">{{ $t['nama'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal__foot">
                <button class="btn btn-ghost" type="button" id="batal-tambah">Batal</button>
                <button class="btn btn-primary" type="button">Simpan</button>
            </div>
        </div>
    </div>

@endsection

@push('skrip')
<script>
    (function () {
        // ---------- Modal tema ----------
        var latarTema = document.getElementById('latar-tema');
        var subTema = document.getElementById('sub-tema');
        var kartu = Array.prototype.slice.call(document.querySelectorAll('[data-tema-kartu]'));
        var terpilih = null;

        function bukaTema(data) {
            subTema.textContent = data.nama + ' · ' + data.domain;
            terpilih = data.tema;
            kartu.forEach(function (k) {
                k.classList.toggle('is-dipilih', k.dataset.temaKartu === data.tema);
            });
            latarTema.hidden = false;
        }

        function tutupTema() { latarTema.hidden = true; }

        document.querySelectorAll('[data-atur-tema]').forEach(function (t) {
            t.addEventListener('click', function () {
                bukaTema({ nama: t.dataset.nama, tema: t.dataset.tema, domain: t.dataset.domain });
            });
        });

        kartu.forEach(function (k) {
            k.addEventListener('click', function () {
                terpilih = k.dataset.temaKartu;
                kartu.forEach(function (x) { x.classList.toggle('is-dipilih', x === k); });
            });
        });

        document.getElementById('batal-tema').addEventListener('click', tutupTema);
        latarTema.addEventListener('click', function (e) { if (e.target === latarTema) tutupTema(); });

        // Pratinjau langsung: terapkan token tema ke :root saat disimpan.
        document.getElementById('simpan-tema').addEventListener('click', function () {
            var token = window.__temaToken || {};
            if (token[terpilih]) {
                var root = document.documentElement;
                Object.keys(token[terpilih]).forEach(function (k) {
                    root.style.setProperty(k, token[terpilih][k]);
                });
            }
            tutupTema();
        });

        // ---------- Modal tambah ----------
        var latarTambah = document.getElementById('latar-tambah');
        var tombolTambah = document.getElementById('tombol-tambah');

        if (tombolTambah) {
            tombolTambah.addEventListener('click', function () { latarTambah.hidden = false; });
        }
        document.getElementById('batal-tambah').addEventListener('click', function () { latarTambah.hidden = true; });
        latarTambah.addEventListener('click', function (e) { if (e.target === latarTambah) latarTambah.hidden = true; });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { tutupTema(); latarTambah.hidden = true; }
        });
    })();

    // Token tema disuntikkan dari server agar pratinjau memakai nilai yang sama
    // dengan yang nanti disimpan. Tidak ada duplikasi nilai warna di JavaScript.
    window.__temaToken = @json(collect(config('sipedal.tema'))->map(fn ($t) => $t['tokens'])->all());
</script>
@endpush
