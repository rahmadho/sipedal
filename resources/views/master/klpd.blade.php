@extends('layouts.app')

@section('judul', 'KLPD / Instansi')

@section('konten')

    <div class="page-head">
        <div class="page-head__title">
            <h1>KLPD / Instansi</h1>
            <p>
                Daftar Pemerintah Daerah (Provinsi, Kabupaten, dan Kota) di wilayah Sumatera Barat beserta integrasi SPSE dan pengaturan tampilannya.
            </p>
        </div>
        <div class="page-head__aksi">
            <span class="badge badge--netral" style="padding:6px 12px;font-size:12px">
                Total: <strong>{{ $total }}</strong> Instansi
            </span>
        </div>
    </div>

    {{-- Pemberitahuan khusus Super Admin --}}
    @if (session('peran') === 'super_admin')
        <div class="banner banner--info" style="margin-bottom:16px">
            <span class="banner__ikon"><x-ikon nama="palette" :ukuran="17" /></span>
            <div>
                <strong>Pengaturan Lintas KLPD (Super Admin).</strong>
                Anda dapat melihat status integrasi LPSE, memeriksa ketersediaan token API, serta mengatur skema tema warna instansi terkait.
            </div>
        </div>
    @endif

    <div class="card card--tabel">
        {{-- Toolbar Pencarian & Filter --}}
        <form class="toolbar" method="GET" action="{{ route('master.klpd') }}">
            <div class="field field--cari">
                <label for="filter-cari" class="sr-only">Cari KLPD</label>
                <input
                    class="input"
                    id="filter-cari"
                    name="cari"
                    type="search"
                    placeholder="Cari kode, nama, atau ibukota…"
                    value="{{ $cari }}"
                >
            </div>
            <div class="field field--sm">
                <label for="filter-jenis" class="sr-only">Jenis Wilayah</label>
                <select class="select js-combobox-asal" id="filter-jenis" name="jenis">
                    <option value="">Semua Jenis</option>
                    <option value="PROVINSI" @selected($jenisTerpilih === 'PROVINSI')>Provinsi</option>
                    <option value="KABUPATEN" @selected($jenisTerpilih === 'KABUPATEN')>Kabupaten</option>
                    <option value="KOTA" @selected($jenisTerpilih === 'KOTA')>Kota</option>
                </select>
            </div>
            <div class="toolbar__aksi">
                <button class="btn btn-primary btn-sm" type="submit">
                    <x-ikon nama="search" :ukuran="14" />
                    Filter
                </button>
                @if ($cari !== '' || $jenisTerpilih !== '')
                    <a class="btn btn-ghost btn-sm" href="{{ route('master.klpd') }}">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        {{-- Tabel Daftar KLPD (Tampilan List) --}}
        <div class="tabel-wrap">
            <table class="tabel">
                <caption class="sr-only">Daftar KLPD dan Instansi Pemerintah Daerah</caption>
                <thead>
                    <tr>
                        <th style="width:50px">No</th>
                        <th style="width:90px">Kode</th>
                        <th>Nama Pemerintah Daerah</th>
                        <th style="width:110px">Jenis</th>
                        <th>Ibukota & Alamat</th>
                        <th>SPSE / LPSE</th>
                        <th style="width:110px">Token API</th>
                        <th style="width:110px">Tema</th>
                        @if (session('peran') === 'super_admin')
                            <th style="width:80px;text-align:center">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($baris as $idx => $k)
                        @php
                            $temaAktif = config('sipedal.tema.' . $k['tema'], config('sipedal.tema.biru'));
                            $warna = $temaAktif['tokens'] ?? [];
                            $badgeJenis = match ($k['jenis']) {
                                'PROVINSI' => 'brand',
                                'KOTA' => 'biru',
                                default => 'netral',
                            };
                        @endphp
                        <tr>
                            <td class="text-mute tnum">{{ $idx + 1 }}</td>
                            <td>
                                <span class="badge badge--brand mono fw-700">{{ $k['kode'] }}</span>
                            </td>
                            <td>
                                <div class="fw-600 text-sm" style="color:var(--text)">{{ $k['nama'] }}</div>
                                <div class="text-xs text-mute mono" style="margin-top:2px">{{ $k['domain'] }}</div>
                            </td>
                            <td>
                                <span class="badge badge--{{ $badgeJenis }}">
                                    {{ ucfirst(strtolower($k['jenis'])) }}
                                </span>
                            </td>
                            <td>
                                <div class="text-sm fw-500">{{ $k['ibukota'] }}</div>
                                @if ($k['alamat'] && $k['alamat'] !== '—')
                                    <div class="text-xs text-mute truncate" style="max-width:240px" title="{{ $k['alamat'] }}">
                                        {{ $k['alamat'] }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if ($k['spse_url'])
                                    <a href="{{ $k['spse_url'] }}" target="_blank" rel="noopener noreferrer" class="row gap-1" style="color:var(--brand-700);font-size:12px;text-decoration:none;align-items:center">
                                        <x-ikon nama="external-link" :ukuran="13" />
                                        <span class="mono">ID: {{ $k['spse_id'] ?? '—' }}</span>
                                        @if ($k['spse_versi'] && $k['spse_versi'] !== '—')
                                            <span class="chip" style="font-size:10px;padding:1px 5px">v{{ $k['spse_versi'] }}</span>
                                        @endif
                                    </a>
                                @else
                                    <span class="text-mute text-xs">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($k['has_token'])
                                    <span class="badge badge--hijau">
                                        <span class="badge__dot"></span>
                                        Tersedia
                                    </span>
                                @else
                                    <span class="badge badge--netral">Belum Ada</span>
                                @endif
                            </td>
                            <td>
                                <div class="row gap-2" style="align-items:center">
                                    <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:{{ $warna['--brand-600'] ?? 'var(--brand)' }};border:1px solid rgba(0,0,0,0.1);flex:none"></span>
                                    <span class="text-xs fw-500">{{ $temaAktif['nama'] ?? ucfirst($k['tema']) }}</span>
                                </div>
                            </td>
                            @if (session('peran') === 'super_admin')
                                <td style="text-align:center">
                                    <button
                                        class="btn btn-ghost btn-sm"
                                        type="button"
                                        title="Ubah Tema KLPD"
                                        data-atur-tema
                                        data-kode="{{ $k['kode'] }}"
                                        data-nama="{{ $k['nama'] }}"
                                        data-tema="{{ $k['tema'] }}"
                                        data-domain="{{ $k['domain'] }}"
                                    >
                                        <x-ikon nama="palette" :ukuran="14" />
                                        Tema
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ session('peran') === 'super_admin' ? 9 : 8 }}" style="text-align:center;padding:36px">
                                <div class="text-mute" style="font-size:13px">
                                    Tidak ada data KLPD yang sesuai dengan filter pencarian.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ================= MODAL PENGATURAN TEMA ================= --}}
    <div class="modal-latar" id="latar-tema" hidden>
        <div class="modal modal--lebar" role="dialog" aria-modal="true" aria-labelledby="judul-tema">
            <div class="modal__head">
                <h2 id="judul-tema">Pengaturan Tampilan KLPD</h2>
                <p id="sub-tema">—</p>
            </div>
            <div class="modal__body">
                <p class="text-sm text-soft" style="margin-bottom:14px">
                    Pilih skema warna utama untuk instansi ini. Tema akan menjadi warna dasar saat pengguna masuk ke konteks KLPD tersebut:
                </p>

                <div class="grid grid-3 gap-3">
                    @foreach (config('sipedal.tema') as $kunci => $t)
                        <button
                            class="tema-kartu"
                            type="button"
                            data-tema-kartu="{{ $kunci }}"
                        >
                            <div class="tema-kartu__contoh">
                                <span style="background:{{ $t['tokens']['--brand-600'] }}"></span>
                                <span style="background:{{ $t['tokens']['--brand-400'] }}"></span>
                                <span style="background:{{ $t['tokens']['--brand-200'] }}"></span>
                                <span style="background:{{ $t['tokens']['--brand-50'] }}"></span>
                            </div>
                            <div class="tema-kartu__nama">{{ $t['nama'] }}</div>
                            <div class="tema-kartu__ket">{{ $t['keterangan'] ?? '' }}</div>
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="modal__foot">
                <button class="btn btn-ghost" type="button" id="batal-tema">Batal</button>
                <button class="btn btn-primary" type="button" id="simpan-tema">
                    <x-ikon nama="check" :ukuran="14" />
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </div>

@endsection

@push('skrip')
<script>
    (function () {
        var latarTema = document.getElementById('latar-tema');
        var subTema = document.getElementById('sub-tema');
        var kartu = Array.prototype.slice.call(document.querySelectorAll('[data-tema-kartu]'));
        var terpilih = null;
        var kodeKlpdAktif = null;

        function bukaTema(data) {
            kodeKlpdAktif = data.kode;
            subTema.textContent = data.nama + ' (' + data.kode + ')';
            terpilih = data.tema;
            kartu.forEach(function (k) {
                k.classList.toggle('is-dipilih', k.dataset.temaKartu === data.tema);
            });
            latarTema.hidden = false;
        }

        function tutupTema() {
            latarTema.hidden = true;
            kodeKlpdAktif = null;
        }

        document.querySelectorAll('[data-atur-tema]').forEach(function (t) {
            t.addEventListener('click', function () {
                bukaTema({
                    kode: t.dataset.kode,
                    nama: t.dataset.nama,
                    tema: t.dataset.tema,
                    domain: t.dataset.domain
                });
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

        var btnSimpan = document.getElementById('simpan-tema');
        btnSimpan.addEventListener('click', function () {
            if (!kodeKlpdAktif || !terpilih) return;
            btnSimpan.disabled = true;

            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            fetch('{{ route("master.klpd.tema") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    kode: kodeKlpdAktif,
                    tema: terpilih
                })
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                btnSimpan.disabled = false;
                if (data.ok) {
                    window.location.reload();
                } else {
                    alert(data.pesan || 'Gagal menyimpan tema.');
                }
            })
            .catch(function (err) {
                btnSimpan.disabled = false;
                alert('Terjadi kesalahan jaringan: ' + err.message);
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { tutupTema(); }
        });
    })();
</script>
@endpush
