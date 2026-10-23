@extends('layouts.app')

@section('judul', 'ITKP Tahunan')

@section('konten')

    <div class="page-head">
        <div class="page-head__title">
            <h1>ITKP Tahunan</h1>
            <p>
                Rekapitulasi Indikator Tingkat Kematangan Pengadaan untuk tahun anggaran 2026,
                dihitung dari enam indikator berbobot.
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
        // Nilai akhir = jumlah (capaian/target × bobot), dibatasi 100 per indikator.
        $nilaiAkhir = 0;
        foreach ($baris as $b) {
            $nilaiAkhir += min(100, $b['capaian'] / max(1, $b['target']) * 100) * ($b['bobot'] / 100);
        }
        $nilaiAkhir = round($nilaiAkhir, 1);

        $predikat = $nilaiAkhir >= 90 ? ['Sangat Baik', 'hijau']
            : ($nilaiAkhir >= 75 ? ['Baik', 'biru']
            : ($nilaiAkhir >= 60 ? ['Cukup', 'amber'] : ['Kurang', 'merah']));
    @endphp

    <div class="grid grid-2-1" style="margin-bottom:16px">
        <div class="card">
            <div class="card__head">
                <div>
                    <h2>Nilai Kematangan Pengadaan</h2>
                    <p>Agregasi berbobot dari enam indikator</p>
                </div>
            </div>
            <div class="card__body">
                <div class="row gap-6 wrap">
                    <div style="text-align:center;flex:none">
                        <div style="font-size:46px;font-weight:700;letter-spacing:-2px;line-height:1;color:var(--brand-700)">{{ angka($nilaiAkhir, 1) }}</div>
                        <div class="text-xs text-mute" style="margin-top:4px">dari 100</div>
                    </div>
                    <div class="grow">
                        <span class="badge badge--{{ $predikat[1] }}" style="font-size:12px;padding:4px 11px">
                            Predikat {{ $predikat[0] }}
                        </span>
                        <p class="text-sm text-soft" style="margin-top:10px;line-height:1.6">
                            Nilai dihitung dari capaian tiap indikator terhadap targetnya,
                            dikalikan bobot masing-masing. Indikator dengan capaian di bawah
                            target menurunkan nilai akhir secara proporsional.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__head">
                <div>
                    <h2>Sebaran Bobot</h2>
                    <p>Kontribusi tiap indikator</p>
                </div>
            </div>
            <div class="card__body">
                <div class="bar-list">
                    @foreach ($baris as $b)
                        <div>
                            <div class="bar-item__head">
                                <span class="bar-item__label" title="{{ $b['indikator'] }}">{{ \Illuminate\Support\Str::limit($b['indikator'], 26) }}</span>
                                <span class="bar-item__nilai">{{ $b['bobot'] }}%</span>
                            </div>
                            <div class="bar-track" style="height:5px">
                                <div class="bar-fill bar-fill--netral" style="width:{{ $b['bobot'] * 4 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card__head">
            <div>
                <h2>Rincian Indikator</h2>
                <p>Perbandingan capaian terhadap target</p>
            </div>
        </div>
        <div class="tabel-wrap">
            <table class="tabel">
                <caption class="sr-only">Rincian indikator ITKP tahunan 2026</caption>
                <thead>
                    <tr>
                        <th style="width:52px">No</th>
                        <th>Indikator</th>
                        <th class="num">Target</th>
                        <th class="num">Capaian</th>
                        <th class="num">Selisih</th>
                        <th class="num">Bobot</th>
                        <th style="width:180px">Terhadap Target</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($baris as $i => $b)
                        @php
                            $selisih = round($b['capaian'] - $b['target'], 1);
                            $rasio = min(100, $b['capaian'] / max(0.01, $b['target']) * 100);
                        @endphp
                        <tr>
                            <td class="text-mute tnum">{{ $i + 1 }}</td>
                            <td class="fw-500">{{ $b['indikator'] }}</td>
                            <td class="num text-mute">{{ persen($b['target']) }}</td>
                            <td class="num fw-600">{{ persen($b['capaian']) }}</td>
                            <td class="num">
                                <span class="tren tren--{{ $selisih >= 0 ? 'naik' : 'turun' }}">
                                    <x-ikon :nama="$selisih >= 0 ? 'arrow-up' : 'arrow-down'" :ukuran="11" />
                                    {{ persen(abs($selisih)) }}
                                </span>
                            </td>
                            <td class="num">{{ $b['bobot'] }}%</td>
                            <td>
                                <div class="progres {{ $rasio < 80 ? 'progres--amber' : 'progres--hijau' }}">
                                    <div class="progres__isi" style="width:{{ $rasio }}%"></div>
                                </div>
                            </td>
                            <td>
                                @if ($selisih >= 0)
                                    <span class="badge badge--hijau"><span class="badge__dot"></span> Tercapai</span>
                                @else
                                    <span class="badge badge--amber"><span class="badge__dot"></span> Di bawah target</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

@endsection
