@extends('layouts.app')

@section('judul', 'Realisasi RUP')

@section('konten')

    <div class="page-head">
        <div class="page-head__title">
            <h1>Realisasi RUP</h1>
            <p>
                Paket RUP yang sudah memiliki proses pengadaan, lengkap dengan rantai
                warisannya. Satu baris mewakili satu proses, bukan satu paket.
            </p>
        </div>
        <div class="page-head__aksi">
            <button class="btn btn-ghost btn-sm" type="button">
                <x-ikon nama="download" :ukuran="15" />
                Ekspor CSV
            </button>
        </div>
    </div>

    {{-- Komposisi per sumber --}}
    <div class="grid grid-4" style="margin-bottom:16px">
        @php
            $warna = ['#1d4ed8', '#10b981', '#f59e0b', '#a81f45'];
            $totalBaris = array_sum(array_column($komposisi, 'total'));
        @endphp
        @foreach ($komposisi as $i => $k)
            <div class="kpi">
                <div class="kpi__top">
                    <span class="kpi__label">{{ $k['label'] }}</span>
                    <span class="legend__warna" style="background:{{ $warna[$i % count($warna)] }};width:11px;height:11px"></span>
                </div>
                <div class="kpi__nilai">{{ angka($k['total']) }}</div>
                <div class="kpi__sub">
                    <span class="text-xs">{{ angka($k['langsung']) }} langsung</span>
                    <span class="text-xs text-mute">·</span>
                    <span class="text-xs">{{ angka($k['warisan']) }} warisan</span>
                </div>
                <div class="progres" style="margin-top:10px">
                    <div class="progres__isi" style="width:{{ round($k['total'] / $totalBaris * 100) }}%;background:{{ $warna[$i % count($warna)] }}"></div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card__head">
            <div>
                <h2>Daftar Realisasi</h2>
                <p>{{ angka($total) }} baris proses dari {{ angka(\App\Support\Mock\Mock::ACUAN['proses']) }} proses dan {{ angka(\App\Support\Mock\Mock::ACUAN['warisan']) }} warisan</p>
            </div>
        </div>

        <form class="toolbar" method="GET" action="{{ route('realisasi') }}">
            <div class="field field--cari">
                <label for="cari">Cari paket</label>
                <div class="input-wrap">
                    <span class="input-ikon"><x-ikon nama="search" :ukuran="14" /></span>
                    <input class="input" type="search" id="cari" name="cari" placeholder="Nama paket atau kode RUP" value="{{ request('cari') }}">
                </div>
            </div>

            <div class="field field--md">
                <label for="sumber">Sumber</label>
                <select class="select" id="sumber" name="sumber">
                    <option value="">Semua sumber</option>
                    @foreach ($sumber as $s)
                        <option value="{{ $s }}" @selected(request('sumber') === $s)>{{ ucwords(str_replace('-', ' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field field--md">
                <label for="jenis">Jenis</label>
                <select class="select" id="jenis" name="jenis">
                    <option value="">Langsung & warisan</option>
                    <option value="langsung" @selected(request('jenis') === 'langsung')>Langsung</option>
                    <option value="warisan" @selected(request('jenis') === 'warisan')>Warisan</option>
                </select>
            </div>

            <div class="toolbar__aksi">
                <button class="btn btn-primary btn-sm" type="submit">
                    <x-ikon nama="search" :ukuran="14" />
                    Terapkan
                </button>
                <a class="btn btn-ghost btn-sm" href="{{ route('realisasi') }}">Reset</a>
            </div>
        </form>

        <div class="tabel-wrap">
            <table class="tabel tabel--kunci">
                <caption class="sr-only">Daftar realisasi RUP beserta sumber dan rantai warisannya</caption>
                <thead>
                    <tr>
                        <th style="width:48px">No</th>
                        <th style="width:96px">Kode RUP</th>
                        <th>Nama Paket</th>
                        <th style="width:190px">Satuan Kerja</th>
                        <th style="width:112px">Sumber</th>
                        <th class="num" style="width:140px">Pagu</th>
                        <th style="width:86px">Jenis</th>
                        <th style="width:48px"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($baris as $b)
                        <tr>
                            <td class="text-mute tnum">{{ $b['no'] }}</td>
                            <td class="sel-kode">{{ $b['kd_rup'] }}</td>
                            <td>
                                <div class="sel-nama truncate" style="max-width:290px" title="{{ $b['nama_paket'] }}">{{ $b['nama_paket'] }}</div>
                                <div class="sel-sub">{{ $b['metode'] }}</div>
                            </td>
                            <td class="truncate" style="max-width:210px" title="{{ $b['satker'] }}">{{ $b['satker'] }}</td>
                            <td>
                                <span class="chip chip--brand">{{ ucwords(str_replace('-', ' ', $b['sumber'])) }}</span>
                            </td>
                            <td class="num">
                                <div class="fw-600">{{ rp($b['pagu']) }}</div>
                                <div class="text-xs text-mute">{{ ringkas($b['pagu']) }}</div>
                            </td>
                            <td>
                                @if ($b['jenis'] === 'warisan')
                                    <span class="badge badge--amber" title="Diwarisi dari kode {{ $b['dari_rup'] }} pada kedalaman {{ $b['depth'] }}">
                                        <x-ikon nama="layers" :ukuran="11" />
                                        W{{ $b['depth'] }}
                                    </span>
                                @else
                                    <span class="badge badge--hijau">
                                        <span class="badge__dot"></span>
                                        Langsung
                                    </span>
                                @endif
                            </td>
                            <td>
                                <button
                                    class="icon-btn"
                                    type="button"
                                    aria-label="Lihat rantai warisan {{ $b['kd_rup'] }}"
                                    data-buka-rantai
                                    data-kode="{{ $b['kd_rup'] }}"
                                    data-dari="{{ $b['dari_rup'] ?? '' }}"
                                    data-depth="{{ $b['depth'] }}"
                                    data-paket="{{ $b['nama_paket'] }}"
                                >
                                    <x-ikon nama="chevron-right" :ukuran="15" />
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <x-paginasi :halaman="$halaman" :per-halaman="$perHalaman" :total="$total" />
    </div>

    {{-- ================= PANEL RANTAI WARISAN ================= --}}
    <div class="drawer-latar" id="latar-rantai" hidden></div>
    <aside class="drawer" id="drawer-rantai" hidden aria-label="Rantai warisan RUP">
        <div class="drawer__head">
            <div class="grow">
                <h2>Rantai Warisan RUP</h2>
                <p id="rantai-paket">—</p>
            </div>
            <button class="icon-btn" type="button" id="tutup-rantai" aria-label="Tutup panel">
                <x-ikon nama="x" :ukuran="17" />
            </button>
        </div>
        <div class="drawer__body">
            <div class="banner banner--info" style="margin-bottom:16px">
                <span class="banner__ikon"><x-ikon nama="info" :ukuran="16" /></span>
                <div>
                    Paket ini mewarisi proses pengadaan dari paket lain. Rantai ditelusuri
                    mundur melalui kolom <span class="mono">dari_rup</span> hingga menemukan
                    paket yang memiliki proses sendiri.
                </div>
            </div>

            <div class="timeline" id="isi-rantai"></div>
        </div>
        <div class="drawer__foot">
            <button class="btn btn-ghost btn-sm" type="button" id="tutup-rantai-2">Tutup</button>
            <a class="btn btn-primary btn-sm" href="{{ route('perencanaan') }}">
                Buka detail paket
                <x-ikon nama="arrow-right" :ukuran="14" />
            </a>
        </div>
    </aside>

@endsection

@push('skrip')
<script>
    (function () {
        var latar = document.getElementById('latar-rantai');
        var drawer = document.getElementById('drawer-rantai');
        var isi = document.getElementById('isi-rantai');
        var judul = document.getElementById('rantai-paket');

        function buka(data) {
            judul.textContent = data.paket || '—';

            var depth = parseInt(data.depth || 0, 10);
            var kode = data.kode || '—';
            var dari = data.dari || '';
            var html = '';

            // Rantai ditampilkan dari akar menuju paket yang dipilih.
            for (var d = depth; d >= 1; d--) {
                html += '<div class="timeline__item">'
                     + '<span class="timeline__titik"></span>'
                     + '<div class="timeline__waktu">Kedalaman ' + d + '</div>'
                     + '<div class="timeline__judul mono">' + (d === depth ? dari || kode : '66' + (100000 + d * 137)) + '</div>'
                     + '<div class="timeline__ket">Mewariskan proses ke tingkat di atasnya</div>'
                     + '</div>';
            }

            html += '<div class="timeline__item timeline__item--kini">'
                 + '<span class="timeline__titik"></span>'
                 + '<div class="timeline__waktu">Kedalaman 0 · paket dipilih</div>'
                 + '<div class="timeline__judul mono">' + kode + '</div>'
                 + '<div class="timeline__ket">Paket ini memiliki proses pengadaan</div>'
                 + '</div>';

            isi.innerHTML = html;
            latar.hidden = false;
            drawer.hidden = false;
        }

        function tutup() {
            latar.hidden = true;
            drawer.hidden = true;
        }

        document.querySelectorAll('[data-buka-rantai]').forEach(function (t) {
            t.addEventListener('click', function () {
                buka({
                    kode: t.dataset.kode,
                    dari: t.dataset.dari,
                    depth: t.dataset.depth,
                    paket: t.dataset.paket
                });
            });
        });

        latar.addEventListener('click', tutup);
        document.getElementById('tutup-rantai').addEventListener('click', tutup);
        document.getElementById('tutup-rantai-2').addEventListener('click', tutup);
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') tutup(); });
    })();
</script>
@endpush
