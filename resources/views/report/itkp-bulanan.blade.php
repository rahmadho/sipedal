@extends('layouts.app')

@section('judul', 'ITKP Bulanan')

@section('konten')

    <div class="page-head">
        <div class="page-head__title">
            <h1>ITKP Bulanan</h1>
            <p>
                Indikator Tingkat Kematangan Pengadaan per bulan. Menunjukkan berapa paket
                yang diproses tepat waktu dibandingkan target.
            </p>
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

    @php
        $totalPaket = array_sum(array_column($baris, 'paket'));
        $totalTepat = array_sum(array_column($baris, 'tepat_waktu'));
        $capaianRata = $totalPaket > 0 ? round($totalTepat / $totalPaket * 100, 1) : 0;
        $terbaik = collect($baris)->sortByDesc('capaian')->first();
        $terburuk = collect($baris)->sortBy('capaian')->first();
    @endphp

    <div class="grid grid-4" style="margin-bottom:16px">
        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__label">Capaian Tahunan</span>
                <span class="kpi__ikon"><x-ikon nama="target" :ukuran="17" /></span>
            </div>
            <div class="kpi__nilai">{{ persen($capaianRata) }}</div>
            <div class="kpi__sub">Target 85%</div>
        </div>
        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__label">Total Paket</span>
                <span class="kpi__ikon"><x-ikon nama="clipboard" :ukuran="17" /></span>
            </div>
            <div class="kpi__nilai">{{ angka($totalPaket) }}</div>
            <div class="kpi__sub">Sepanjang tahun</div>
        </div>
        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__label">Bulan Terbaik</span>
                <span class="kpi__ikon"><x-ikon nama="trending" :ukuran="17" /></span>
            </div>
            <div class="kpi__nilai">{{ $terbaik['bulan'] }}</div>
            <div class="kpi__sub">{{ persen($terbaik['capaian']) }} tepat waktu</div>
        </div>
        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__label">Perlu Perhatian</span>
                <span class="kpi__ikon"><x-ikon nama="alert" :ukuran="17" /></span>
            </div>
            <div class="kpi__nilai">{{ $terburuk['bulan'] }}</div>
            <div class="kpi__sub">{{ persen($terburuk['capaian']) }} tepat waktu</div>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px">
        <div class="card__head">
            <div>
                <h2>Capaian per Bulan</h2>
                <p>Persentase paket tepat waktu dibandingkan target 85%</p>
            </div>
            <div class="head-aksi">
                <span class="badge badge--netral">Target 85%</span>
            </div>
        </div>
        <div class="card__body">
            @php $maksCapaian = 100; @endphp
            <div class="chart-batang" style="height:180px" role="img" aria-label="Grafik capaian ITKP per bulan">
                @foreach ($baris as $b)
                    <div class="chart-batang__kol" title="{{ $b['bulan'] }}: {{ persen($b['capaian']) }}">
                        <div
                            class="chart-batang__isi {{ $b['capaian'] < 80 ? 'bar-fill--amber' : '' }}"
                            style="height:{{ round($b['capaian'] / $maksCapaian * 100) }}%"
                        ></div>
                        <span class="chart-batang__label">{{ mb_substr($b['bulan'], 0, 3) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card__head">
            <div>
                <h2>Rincian Bulanan</h2>
                <p>Angka penyusun capaian tahunan</p>
            </div>
        </div>
        <div class="tabel-wrap">
            <table class="tabel">
                <caption class="sr-only">Rincian ITKP bulanan tahun 2026</caption>
                <thead>
                    <tr>
                        <th>Bulan</th>
                        <th class="num">Jumlah Paket</th>
                        <th class="num">Tepat Waktu</th>
                        <th class="num">Terlambat</th>
                        <th class="num">Capaian</th>
                        <th class="num">Target</th>
                        <th style="width:170px">Terhadap Target</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($baris as $b)
                        <tr>
                            <td class="fw-500">{{ $b['bulan'] }}</td>
                            <td class="num">{{ angka($b['paket']) }}</td>
                            <td class="num">{{ angka($b['tepat_waktu']) }}</td>
                            <td class="num">{{ angka($b['terlambat']) }}</td>
                            <td class="num fw-600">{{ persen($b['capaian']) }}</td>
                            <td class="num text-mute">{{ persen($b['target'], 0) }}</td>
                            <td>
                                <div class="row gap-2">
                                    <div class="progres grow {{ $b['capaian'] < 80 ? 'progres--amber' : 'progres--hijau' }}">
                                        <div class="progres__isi" style="width:{{ min(100, $b['capaian']) }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($b['status'] === 'tercapai')
                                    <span class="badge badge--hijau"><span class="badge__dot"></span> Tercapai</span>
                                @else
                                    <span class="badge badge--amber"><span class="badge__dot"></span> Perhatian</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

@endsection
