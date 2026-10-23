@extends('layouts.app')

@section('judul', 'Satuan Kerja')

@section('konten')

    <div class="page-head">
        <div class="page-head__title">
            <h1>Satuan Kerja</h1>
            <p>
                Daftar satuan kerja pada {{ $tenant->nama() }} beserta pagu, jumlah paket,
                dan rasio serapannya.
            </p>
        </div>
        <div class="page-head__aksi">
            <button class="btn btn-ghost btn-sm" type="button">
                <x-ikon nama="download" :ukuran="15" />
                Ekspor
            </button>
        </div>
    </div>

    <div class="grid grid-3" style="margin-bottom:16px">
        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__label">Total Satuan Kerja</span>
                <span class="kpi__ikon"><x-ikon nama="layers" :ukuran="17" /></span>
            </div>
            <div class="kpi__nilai">{{ angka($total) }}</div>
            <div class="kpi__sub">Terdaftar pada KLPD ini</div>
        </div>
        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__label">Total Pagu</span>
                <span class="kpi__ikon"><x-ikon nama="wallet" :ukuran="17" /></span>
            </div>
            <div class="kpi__nilai">{{ ringkas(\App\Support\Mock\Mock::ACUAN['pagu_total']) }}</div>
            <div class="kpi__sub">{{ rp(\App\Support\Mock\Mock::ACUAN['pagu_total']) }}</div>
        </div>
        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__label">Rata-rata Serapan</span>
                <span class="kpi__ikon"><x-ikon nama="trending" :ukuran="17" /></span>
            </div>
            <div class="kpi__nilai">{{ persen(round(array_sum(array_column($baris, 'serapan')) / max(1, count($baris)))) }}</div>
            <div class="kpi__sub">Dari {{ count($baris) }} satker pada halaman ini</div>
        </div>
    </div>

    <div class="card">
        <div class="card__head">
            <div>
                <h2>Daftar Satuan Kerja</h2>
                <p>{{ angka($total) }} satker terdaftar</p>
            </div>
        </div>

        <form class="toolbar" method="GET" action="{{ route('master.satker') }}">
            <div class="field field--cari">
                <label for="cari">Cari satker</label>
                <div class="input-wrap">
                    <span class="input-ikon"><x-ikon nama="search" :ukuran="14" /></span>
                    <input class="input" type="search" id="cari" name="cari" placeholder="Nama satuan kerja" value="{{ request('cari') }}">
                </div>
            </div>
            <div class="field field--md">
                <label for="jenis">Jenis</label>
                <select class="select" id="jenis" name="jenis">
                    <option value="">Semua jenis</option>
                    <option>Dinas</option>
                    <option>Badan</option>
                    <option>Sekretariat</option>
                    <option>RSUD</option>
                </select>
            </div>
            <div class="toolbar__aksi">
                <button class="btn btn-primary btn-sm" type="submit">
                    <x-ikon nama="search" :ukuran="14" />
                    Terapkan
                </button>
            </div>
        </form>

        <div class="tabel-wrap">
            <table class="tabel">
                <caption class="sr-only">Daftar satuan kerja beserta pagu dan rasio serapan</caption>
                <thead>
                    <tr>
                        <th style="width:52px">No</th>
                        <th>Kode</th>
                        <th>Nama Satuan Kerja</th>
                        <th>Jenis</th>
                        <th class="num">Paket</th>
                        <th class="num">Pagu</th>
                        <th class="num">Realisasi</th>
                        <th style="width:160px">Serapan</th>
                        <th style="width:56px"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($baris as $b)
                        <tr>
                            <td class="text-mute tnum">{{ $b['no'] }}</td>
                            <td class="sel-kode">{{ $b['kd_satker'] }}</td>
                            <td>
                                <div class="sel-nama truncate" style="max-width:300px" title="{{ $b['nama'] }}">{{ $b['nama'] }}</div>
                            </td>
                            <td><span class="chip">{{ $b['jenis'] }}</span></td>
                            <td class="num">{{ angka($b['paket']) }}</td>
                            <td class="num">
                                <div class="fw-600">{{ ringkas($b['pagu']) }}</div>
                                <div class="text-xs text-mute">{{ rp($b['pagu']) }}</div>
                            </td>
                            <td class="num">{{ ringkas($b['realisasi']) }}</td>
                            <td>
                                <div class="row gap-2">
                                    <div class="progres grow {{ $b['serapan'] < 30 ? 'progres--merah' : ($b['serapan'] < 60 ? 'progres--amber' : 'progres--hijau') }}">
                                        <div class="progres__isi" style="width:{{ min(100, $b['serapan']) }}%"></div>
                                    </div>
                                    <span class="text-xs fw-600 tnum" style="width:42px;text-align:right">{{ persen($b['serapan']) }}</span>
                                </div>
                            </td>
                            <td>
                                <button class="icon-btn" type="button" aria-label="Lihat detail satker {{ $b['nama'] }}">
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

@endsection
