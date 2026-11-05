@extends('layouts.app')

@section('judul', $meta['label'])

@section('konten')

    <div class="page-head">
        <div class="page-head__title">
            <h1>{{ $meta['label'] }}</h1>
            <p>{{ $meta['deskripsi'] }}</p>
        </div>
        <div class="page-head__aksi">
            <div class="chip chip--brand">
                <x-ikon nama="calendar" :ukuran="13" />
                Tahun 2026
            </div>
            <button class="btn btn-ghost btn-sm" type="button">
                <x-ikon nama="download" :ukuran="15" />
                Ekspor
            </button>
        </div>
    </div>

    {{-- Tab perpindahan antar modul pantau --}}
    <div class="tabs" style="margin-bottom:16px">
        @foreach ($daftar as $kunci => $d)
            <a
                class="tab {{ $kunci === $jenis ? 'is-active' : '' }}"
                href="{{ route('report.pantau', ['jenis' => $kunci]) }}"
            >
                {{ str_replace(['Pantau ', 'Pencatatan '], ['', ''], $d['label']) }}
            </a>
        @endforeach
    </div>

    {{-- Ringkasan status --}}
    @php
        $jmlBerlangsung = collect($baris)->where('status', 'Berlangsung')->count();
        $jmlSelesai = collect($baris)->where('status', 'Selesai')->count();
        $jmlGagal = collect($baris)->where('status', 'Gagal/Batal')->count();
        $nilaiTotal = array_sum(array_column($baris, 'pagu'));
    @endphp

    <div class="grid grid-4" style="margin-bottom:16px">
        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__label">Total Data</span>
                <span class="kpi__ikon"><x-ikon nama="database" :ukuran="17" /></span>
            </div>
            <div class="kpi__nilai">{{ angka($total) }}</div>
            <div class="kpi__sub">Baris pada {{ $meta['label'] }}</div>
        </div>
        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__label">Berlangsung</span>
                <span class="kpi__ikon"><x-ikon nama="activity" :ukuran="17" /></span>
            </div>
            <div class="kpi__nilai">{{ angka($jmlBerlangsung) }}</div>
            <div class="kpi__sub">Pada halaman ini</div>
        </div>
        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__label">Selesai</span>
                <span class="kpi__ikon"><x-ikon nama="check-circle" :ukuran="17" /></span>
            </div>
            <div class="kpi__nilai">{{ angka($jmlSelesai) }}</div>
            <div class="kpi__sub">Pada halaman ini</div>
        </div>
        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__label">Nilai Halaman</span>
                <span class="kpi__ikon"><x-ikon nama="wallet" :ukuran="17" /></span>
            </div>
            <div class="kpi__nilai">{{ ringkas($nilaiTotal) }}</div>
            <div class="kpi__sub">{{ angka(count($baris)) }} baris ditampilkan</div>
        </div>
    </div>

    <div class="card">
        <div class="card__head">
            <div>
                <h2>Daftar {{ $meta['label'] }}</h2>
                <p>{{ angka($total) }} baris · halaman {{ $halaman }}</p>
            </div>
        </div>

        <form class="toolbar" method="GET" action="{{ route('report.pantau', ['jenis' => $jenis]) }}">
            <div class="field field--cari">
                <label for="cari">Cari</label>
                <div class="input-wrap">
                    <span class="input-ikon"><x-ikon nama="search" :ukuran="14" /></span>
                    <input class="input" type="search" id="cari" name="cari" placeholder="Nama paket atau kode" value="{{ request('cari') }}">
                </div>
            </div>
            <div class="field field--md">
                <label for="status">Status</label>
                <select class="select js-combobox-asal" id="status" name="status">
                    <option value="">Semua status</option>
                    <option>Berlangsung</option>
                    <option>Selesai</option>
                    <option>Gagal/Batal</option>
                    <option>Draft</option>
                </select>
            </div>
            <div class="field field--md">
                <label for="satker">Satuan Kerja</label>
                <select class="select js-combobox-asal" id="satker" name="satker">
                    <option value="">Semua satker</option>
                    @foreach (array_slice(\App\Support\Mock\Mock::SATKER, 0, 8) as $s)
                        <option>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="toolbar__aksi">
                <button class="btn btn-primary btn-sm" type="submit">
                    <x-ikon nama="search" :ukuran="14" />
                    Terapkan
                </button>
                <a class="btn btn-ghost btn-sm" href="{{ route('report.pantau', ['jenis' => $jenis]) }}">Reset</a>
            </div>
        </form>

        <div class="tabel-wrap">
            <table class="tabel">
                <caption class="sr-only">Daftar {{ $meta['label'] }} tahun 2026</caption>
                <thead>
                    <tr>
                        <th style="width:52px">No</th>
                        <th>{{ $meta['kolom'][0] }}</th>
                        <th>{{ $meta['kolom'][1] }}</th>
                        <th>Satuan Kerja</th>
                        <th class="num">Pagu</th>
                        <th class="num">HPS</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th style="width:56px"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($baris as $b)
                        <tr>
                            <td class="text-mute tnum">{{ $b['no'] }}</td>
                            <td class="sel-kode">{{ $b['kode'] }}</td>
                            <td>
                                <div class="sel-nama truncate" style="max-width:250px" title="{{ $b['nama_paket'] }}">{{ $b['nama_paket'] }}</div>
                                @if ($jenis === 'e-katalog' || $jenis === 'e-kontrak')
                                    <div class="sel-sub">{{ $b['penyedia'] }}</div>
                                @endif
                            </td>
                            <td class="truncate" style="max-width:190px" title="{{ $b['satker'] }}">{{ $b['satker'] }}</td>
                            <td class="num">
                                <div class="fw-600">{{ ringkas($b['pagu']) }}</div>
                                <div class="text-xs text-mute">{{ rp($b['pagu']) }}</div>
                            </td>
                            <td class="num text-mute">{{ ringkas($b['hps']) }}</td>
                            <td>
                                @php
                                    $badge = match ($b['status']) {
                                        'Selesai' => ['hijau', 'Selesai'],
                                        'Berlangsung' => ['biru', 'Berlangsung'],
                                        'Gagal/Batal' => ['merah', 'Gagal/Batal'],
                                        default => ['netral', 'Draft'],
                                    };
                                @endphp
                                <span class="badge badge--{{ $badge[0] }}">
                                    <span class="badge__dot"></span>
                                    {{ $badge[1] }}
                                </span>
                            </td>
                            <td class="nowrap text-sm">{{ $b['tanggal'] }}</td>
                            <td>
                                <button class="icon-btn" type="button" aria-label="Lihat detail {{ $b['kode'] }}">
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
