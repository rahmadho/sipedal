@extends('layouts.app')

@section('judul', 'Perencanaan')

@section('konten')

    <div class="page-head">
        <div class="page-head__title">
            <h1>Perencanaan RUP</h1>
            <p>
                Rencana Umum Pengadaan seluruh satuan kerja. Kolom status menunjukkan
                apakah paket sudah memiliki proses pengadaan atau belum.
            </p>
        </div>
        <div class="page-head__aksi">
            <button class="btn btn-ghost btn-sm" type="button">
                <x-ikon nama="filter" :ukuran="15" />
                Filter lanjutan
            </button>
            <button class="btn btn-ghost btn-sm" type="button">
                <x-ikon nama="download" :ukuran="15" />
                Ekspor CSV
            </button>
        </div>
    </div>

    {{-- Ringkasan cepat --}}
    <div class="grid grid-4" style="margin-bottom:16px">
        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__label">Total Paket</span>
                <span class="kpi__ikon"><x-ikon nama="clipboard" :ukuran="17" /></span>
            </div>
            <div class="kpi__nilai">{{ angka($total) }}</div>
            <div class="kpi__sub">Seluruh paket penyedia</div>
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
                <span class="kpi__label">Terealisasi</span>
                <span class="kpi__ikon"><x-ikon nama="check-circle" :ukuran="17" /></span>
            </div>
            <div class="kpi__nilai">{{ angka(\App\Support\Mock\Mock::ACUAN['terealisasi']) }}</div>
            <div class="kpi__sub">{{ persen(\App\Support\Mock\Mock::ACUAN['terealisasi'] / $total * 100) }} cakupan</div>
        </div>
        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__label">Belum Terealisasi</span>
                <span class="kpi__ikon"><x-ikon nama="clock" :ukuran="17" /></span>
            </div>
            <div class="kpi__nilai">{{ angka(\App\Support\Mock\Mock::ACUAN['belum']) }}</div>
            <div class="kpi__sub">{{ persen(\App\Support\Mock\Mock::ACUAN['belum'] / $total * 100) }} dari total</div>
        </div>
    </div>

    <div class="card">
        <div class="card__head">
            <div>
                <h2>Daftar Paket RUP</h2>
                <p>{{ angka($total) }} paket, halaman {{ $halaman }} dari {{ angka(ceil($total / $perHalaman)) }}</p>
            </div>
            <div class="head-aksi">
                <span class="badge badge--brand">TA 2026</span>
            </div>
        </div>

        {{-- Toolbar filter --}}
        <form class="toolbar" method="GET" action="{{ route('perencanaan') }}">
            <div class="field field--cari">
                <label for="cari">Cari paket</label>
                <div class="input-wrap">
                    <span class="input-ikon"><x-ikon nama="search" :ukuran="14" /></span>
                    <input class="input" type="search" id="cari" name="cari" placeholder="Nama paket atau kode RUP" value="{{ request('cari') }}">
                </div>
            </div>

            <div class="field field--md">
                <label for="metode">Metode</label>
                <select class="select js-combobox-asal" id="metode" name="metode">
                    <option value="">Semua metode</option>
                    @foreach ($metode as $m)
                        <option value="{{ $m }}" @selected(request('metode') === $m)>{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field field--md">
                <label for="jenis">Jenis</label>
                <select class="select js-combobox-asal" id="jenis" name="jenis">
                    <option value="">Semua jenis</option>
                    @foreach ($jenis as $j)
                        <option value="{{ $j }}" @selected(request('jenis') === $j)>{{ $j }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field field--md">
                <label for="status">Status</label>
                <select class="select js-combobox-asal" id="status" name="status">
                    <option value="">Semua status</option>
                    <option value="terealisasi" @selected(request('status') === 'terealisasi')>Terealisasi</option>
                    <option value="belum" @selected(request('status') === 'belum')>Belum terealisasi</option>
                </select>
            </div>

            <div class="toolbar__aksi">
                <button class="btn btn-primary btn-sm" type="submit">
                    <x-ikon nama="search" :ukuran="14" />
                    Terapkan
                </button>
                <a class="btn btn-ghost btn-sm" href="{{ route('perencanaan') }}">Reset</a>
            </div>
        </form>

        <div class="tabel-wrap">
            <table class="tabel">
                <caption class="sr-only">Daftar paket Rencana Umum Pengadaan tahun anggaran 2026</caption>
                <thead>
                    <tr>
                        <th style="width:52px">No</th>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery(['urut' => 'kd_rup']) }}">
                                Kode RUP
                                <x-ikon nama="chevron-down" :ukuran="11" />
                            </a>
                        </th>
                        <th>Nama Paket</th>
                        <th>Satuan Kerja</th>
                        <th>Metode</th>
                        <th class="num">
                            <a href="{{ request()->fullUrlWithQuery(['urut' => 'pagu']) }}">
                                Pagu
                                <x-ikon nama="chevron-down" :ukuran="11" />
                            </a>
                        </th>
                        <th>Status</th>
                        <th style="width:56px"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($baris as $b)
                        <tr>
                            <td class="text-mute tnum">{{ $b['no'] }}</td>
                            <td class="sel-kode">{{ $b['kd_rup'] }}</td>
                            <td>
                                <div class="sel-nama truncate" style="max-width:300px" title="{{ $b['nama_paket'] }}">{{ $b['nama_paket'] }}</div>
                                <div class="sel-sub">{{ $b['jenis'] }} · {{ $b['tgl_buat'] }}</div>
                            </td>
                            <td class="truncate" style="max-width:220px" title="{{ $b['satker'] }}">{{ $b['satker'] }}</td>
                            <td>
                                <span class="chip">{{ $b['metode'] }}</span>
                            </td>
                            <td class="num">
                                <div class="fw-600">{{ rp($b['pagu']) }}</div>
                                <div class="text-xs text-mute">{{ ringkas($b['pagu']) }}</div>
                            </td>
                            <td>
                                @if ($b['status'] === 'terealisasi')
                                    <span class="badge badge--hijau">
                                        <span class="badge__dot"></span>
                                        Terealisasi
                                    </span>
                                @else
                                    <span class="badge badge--amber">
                                        <span class="badge__dot"></span>
                                        Belum
                                    </span>
                                @endif
                            </td>
                            <td>
                                <button class="icon-btn" type="button" aria-label="Lihat detail paket {{ $b['kd_rup'] }}">
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
