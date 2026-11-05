@extends('layouts.app')

@section('judul', 'Home')

@section('konten')

    @php
        $daftarBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $namaBulanSesi = $daftarBulan[$sesiBulan] ?? 'Bulan ' . $sesiBulan;
    @endphp

    @if (session('status_periode'))
        <div class="banner banner--sukses" style="margin-bottom:16px">
            <span class="banner__ikon"><x-ikon nama="check" :ukuran="16" /></span>
            <div>{{ session('status_periode') }}</div>
        </div>
    @endif

    {{-- ================= KEPALA HALAMAN ================= --}}
    <div class="page-head">
        <div class="page-head__title">
            <h1>Ringkasan Pengadaan {{ $tenant->namaPendek() }}</h1>
            <p>
                Posisi perencanaan dan realisasi RUP tahun anggaran {{ $sesiTahun }}.
                Angka dihitung dari {{ angka($acuan['paket_total']) }} paket penyedia
                dan {{ angka($acuan['view_baris']) }} baris proses pengadaan.
            </p>
        </div>
        <div class="page-head__aksi">
            {{-- Form Aksi Cepat Beralih Periode (Tahun & Bulan) --}}
            <form method="POST" action="{{ route('dashboard.periode.ganti') }}" class="row gap-2" style="align-items:center;background:var(--surface);padding:4px 8px;border:1px solid var(--border);border-radius:var(--radius-lg)">
                @csrf
                <div class="row gap-1" style="align-items:center;color:var(--text-mute);font-size:12px">
                    <x-ikon nama="calendar" :ukuran="14" />
                    <span class="fw-600 text-xs">Periode:</span>
                </div>
                <select name="bulan" class="select select--sm" style="height:30px;padding:4px 24px 4px 8px;font-size:12px;width:115px" onchange="this.form.submit()">
                    @foreach ($daftarBulan as $noBulan => $lblBulan)
                        <option value="{{ $noBulan }}" @selected($sesiBulan === $noBulan)>{{ $lblBulan }}</option>
                    @endforeach
                </select>
                <select name="tahun" class="select select--sm" style="height:30px;padding:4px 24px 4px 8px;font-size:12px;width:85px" onchange="this.form.submit()">
                    @foreach ([2024, 2025, 2026, 2027] as $th)
                        <option value="{{ $th }}" @selected($sesiTahun === $th)>{{ $th }}</option>
                    @endforeach
                </select>
            </form>

            <button class="btn btn-ghost btn-sm" type="button">
                <x-ikon nama="download" :ukuran="15" />
                Ekspor
            </button>
            <button class="btn btn-primary btn-sm" type="button" onclick="location.reload()">
                <x-ikon nama="refresh" :ukuran="15" />
                Segarkan
            </button>
        </div>
    </div>

    {{-- ================= HERO: KESENJANGAN REALISASI =================
         Angka utama halaman ini adalah yang belum terealisasi, bukan yang
         sudah. Itulah masalah yang perlu ditindaklanjuti PPK. --}}
    <section class="hero" aria-labelledby="hero-judul">
        <div class="hero__utama">
            <p class="hero__eyebrow">Realisasi RUP tahun anggaran 2026</p>
            <h2 class="hero__angka" id="hero-judul">
                <span class="hero__persen">{{ persen($acuan['belum'] / $acuan['paket_total'] * 100) }}</span>
                <span class="hero__ket">paket belum terealisasi</span>
            </h2>
            <p class="hero__rincian">
                <strong>{{ angka($acuan['belum']) }}</strong> dari
                <strong>{{ angka($acuan['paket_total']) }}</strong> paket penyedia belum memiliki
                proses pengadaan. Sisa <strong>{{ angka($acuan['terealisasi']) }}</strong> paket
                sudah terealisasi.
            </p>

            {{-- Bar kesenjangan: satu batang, dua bagian. --}}
            <div class="hero__bar" role="img"
                 aria-label="{{ angka($acuan['terealisasi']) }} paket terealisasi, {{ angka($acuan['belum']) }} belum">
                <span class="hero__bar-isi" style="width: {{ round($acuan['terealisasi'] / $acuan['paket_total'] * 100, 2) }}%"></span>
            </div>
            <div class="hero__legenda">
                <span><i class="hero__tanda hero__tanda--isi"></i>Terealisasi {{ angka($acuan['terealisasi']) }}</span>
                <span><i class="hero__tanda hero__tanda--kosong"></i>Belum {{ angka($acuan['belum']) }}</span>
            </div>

            {{-- Peringatan cakupan diletakkan menempel pada angka, karena
                 inilah yang menentukan seberapa boleh angka itu dipercaya. --}}
            <p class="hero__cakupan">
                <x-ikon nama="info" :ukuran="14" />
                <span>Baru <strong>1 dari {{ $acuan['klpd_terdaftar'] }} KLPD</strong> yang datanya
                lengkap ({{ $acuan['klpd_valid'] }} token valid, {{ $acuan['klpd_403'] }} ditolak,
                {{ $acuan['klpd_kosong'] }} tanpa token). Angka ini belum mewakili seluruh KLPD.</span>
            </p>
        </div>

        <div class="hero__sisi">
            <div class="hero__sisi-item">
                <span class="hero__sisi-label">Total pagu</span>
                <span class="hero__sisi-nilai">{{ rp($acuan['pagu_total']) }}</span>
                <span class="hero__sisi-ket">{{ ringkas($acuan['pagu_total']) }} dari {{ angka($acuan['paket_total']) }} paket</span>
            </div>
            <div class="hero__sisi-item">
                <span class="hero__sisi-label">Pagu rata-rata per paket</span>
                <span class="hero__sisi-nilai">{{ rp($acuan['pagu_rata']) }}</span>
                <span class="hero__sisi-ket">Tertinggi {{ ringkas($acuan['pagu_maks']) }}</span>
            </div>
            <div class="hero__sisi-item">
                <span class="hero__sisi-label">Satuan kerja terlibat</span>
                <span class="hero__sisi-nilai">{{ angka($acuan['satker']) }}</span>
                <span class="hero__sisi-ket">{{ angka($acuan['view_baris']) }} baris proses pengadaan</span>
            </div>
        </div>
    </section>

    {{-- ================= KARTU KPI ================= --}}
    <div class="grid grid-kpi" style="margin-bottom:16px">
        @foreach ($kpi as $k)
            <a class="kpi" href="{{ route($k['rute']) }}">
                <div class="kpi__top">
                    <span class="kpi__label">{{ $k['label'] }}</span>
                    <span class="kpi__ikon"><x-ikon :nama="$k['ikon']" :ukuran="17" /></span>
                </div>
                <div class="kpi__nilai {{ str_starts_with($k['nilai'], 'Rp') ? 'kpi__nilai--panjang' : '' }}">{{ $k['nilai'] }}</div>
                <div class="kpi__sub">
                    <span class="tren tren--{{ $k['tren']['arah'] }}">
                        <x-ikon :nama="$k['tren']['arah'] === 'naik' ? 'arrow-up' : ($k['tren']['arah'] === 'turun' ? 'arrow-down' : 'dot')" :ukuran="11" />
                        {{ $k['tren']['nilai'] }}
                    </span>
                    <span>{{ $k['sub'] }}</span>
                </div>
            </a>
        @endforeach
    </div>

    {{-- ================= GRAFIK UTAMA ================= --}}
    <div class="grid grid-2-1" style="margin-bottom:16px">

        {{-- Tren paket per bulan --}}
        <div class="card">
            <div class="card__head">
                <div>
                    <h2>Tren Paket RUP per Bulan</h2>
                    <p>Jumlah paket yang dibuat pada tiap bulan tahun anggaran 2026</p>
                </div>
                <div class="head-aksi">
                    <span class="badge badge--netral">{{ angka(array_sum(array_column($tren, 'nilai'))) }} paket</span>
                </div>
            </div>
            <div class="card__body">
                @php $maks = max(array_column($tren, 'nilai')); @endphp
                <div class="chart-batang" role="img" aria-label="Grafik tren paket RUP per bulan">
                    @foreach ($tren as $t)
                        <div class="chart-batang__kol" title="{{ $t['label'] }}: {{ angka($t['nilai']) }} paket">
                            <div class="chart-batang__isi" style="height: {{ round($t['nilai'] / $maks * 100) }}%"></div>
                            <span class="chart-batang__label">{{ $t['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Komposisi sumber realisasi --}}
        <div class="card">
            <div class="card__head">
                <div>
                    <h2>Komposisi Realisasi</h2>
                    <p>Per sumber proses pengadaan</p>
                </div>
            </div>
            <div class="card__body">
                @php
                    $total = array_sum(array_column($komposisi, 'total'));
                    $warna = ['#1d4ed8', '#10b981', '#f59e0b', '#a81f45'];
                    $segmen = [];
                    $mulai = 0;
                    foreach ($komposisi as $i => $k) {
                        $bagian = $total > 0 ? $k['total'] / $total * 100 : 0;
                        $segmen[] = $warna[$i % count($warna)] . ' ' . round($mulai, 2) . '% ' . round($mulai + $bagian, 2) . '%';
                        $mulai += $bagian;
                    }
                @endphp

                <div class="row gap-5 wrap" style="align-items:flex-start">
                    <div class="chart-donat" style="--seg: {{ implode(',', $segmen) }}">
                        <div class="chart-donat__tengah">
                            <b>{{ angka($total) }}</b>
                            <span>Baris</span>
                        </div>
                    </div>

                    <div class="legend grow">
                        @foreach ($komposisi as $i => $k)
                            <div class="legend__item">
                                <span class="legend__warna" style="background:{{ $warna[$i % count($warna)] }}"></span>
                                <span class="legend__label">{{ $k['label'] }}</span>
                                <span class="legend__nilai">{{ angka($k['total']) }}</span>
                                <span class="legend__pct">{{ persen($k['total'] / $total * 100, 0) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= SATKER & METODE ================= --}}
    <div class="grid grid-2" style="margin-bottom:16px">

        <div class="card">
            <div class="card__head">
                <div>
                    <h2>Satuan Kerja Teratas</h2>
                    <p>Berdasarkan jumlah baris realisasi</p>
                </div>
                <div class="head-aksi">
                    <a class="btn btn-ghost btn-sm" href="{{ route('master.satker') }}">
                        Semua satker
                        <x-ikon nama="arrow-right" :ukuran="13" />
                    </a>
                </div>
            </div>
            <div class="card__body">
                @php $maksSatker = max(array_column($topSatker, 'jumlah')); @endphp
                <div class="bar-list">
                    @foreach ($topSatker as $s)
                        <div>
                            <div class="bar-item__head">
                                <span class="bar-item__label" title="{{ $s['nama'] }}">{{ $s['nama'] }}</span>
                                <span class="bar-item__nilai">{{ angka($s['jumlah']) }}</span>
                            </div>
                            <div class="bar-track">
                                <div class="bar-fill" style="width: {{ round($s['jumlah'] / $maksSatker * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__head">
                <div>
                    <h2>Pagu per Metode Pengadaan</h2>
                    <p>Sebaran {{ angka($acuan['paket_total']) }} paket penyedia</p>
                </div>
            </div>
            <div class="card__body">
                @php $maksMetode = max(array_column($metode, 'jumlah')); @endphp
                <div class="bar-list">
                    @foreach ($metode as $m)
                        <div>
                            <div class="bar-item__head">
                                <span class="bar-item__label">{{ $m['label'] }}</span>
                                <span class="bar-item__nilai">{{ angka($m['jumlah']) }}</span>
                            </div>
                            <div class="bar-track">
                                <div class="bar-fill bar-fill--netral" style="width: {{ round($m['jumlah'] / $maksMetode * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ================= STATUS & LOG ================= --}}
    <div class="grid grid-2">

        <div class="card">
            <div class="card__head">
                <div>
                    <h2>Status Proses Pengadaan</h2>
                    <p>Tender, non-tender, dan e-katalog</p>
                </div>
            </div>
            <div class="card__body col gap-5">

                <div>
                    <div class="label-bagian">Tender · 94 paket</div>
                    @foreach ($statusTender as $s)
                        <div class="row-between" style="padding:5px 0">
                            <span class="row gap-2 text-sm">
                                <span class="badge__dot" style="width:7px;height:7px;background:var(--{{ $s['warna'] === 'hijau' ? 'green-600' : ($s['warna'] === 'merah' ? 'red-600' : 'brand-600') }})"></span>
                                {{ $s['label'] }}
                            </span>
                            <span class="text-sm fw-600 tnum">{{ angka($s['jumlah']) }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="pemisah" style="margin:0"></div>

                <div>
                    <div class="label-bagian">Non-Tender · 1.681 paket</div>
                    @foreach ($statusNontender as $s)
                        <div class="row-between" style="padding:5px 0">
                            <span class="row gap-2 text-sm">
                                <span class="badge__dot" style="width:7px;height:7px;background:var(--{{ $s['warna'] === 'hijau' ? 'green-600' : ($s['warna'] === 'merah' ? 'red-600' : 'brand-600') }})"></span>
                                {{ $s['label'] }}
                            </span>
                            <span class="text-sm fw-600 tnum">{{ angka($s['jumlah']) }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="pemisah" style="margin:0"></div>

                <div>
                    <div class="label-bagian">E-Katalog · 5.770 paket</div>
                    @php $totalEkat = array_sum(array_column($statusEkatalog, 'jumlah')); @endphp
                    <div class="bar-track" style="height:10px;display:flex;gap:2px;background:none">
                        @foreach ($statusEkatalog as $s)
                            <div
                                style="height:100%;width:{{ round($s['jumlah'] / $totalEkat * 100, 2) }}%;background:var(--{{ $s['warna'] === 'hijau' ? 'green-600' : ($s['warna'] === 'merah' ? 'red-600' : ($s['warna'] === 'amber' ? 'amber-600' : 'brand-600')) }});border-radius:2px"
                                title="{{ $s['label'] }}: {{ angka($s['jumlah']) }}"
                            ></div>
                        @endforeach
                    </div>
                    <div class="row gap-4 wrap" style="margin-top:9px">
                        <span class="text-xs text-mute">Selesai {{ angka(602) }}</span>
                        <span class="text-xs text-mute">Diproses {{ angka(3575) }}</span>
                        <span class="text-xs text-mute">Batal {{ angka(988) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__head">
                <div>
                    <h2>Sinkronisasi Terakhir</h2>
                    <p>Enam catatan tarik data terbaru</p>
                </div>
                <div class="head-aksi">
                    <a class="btn btn-ghost btn-sm" href="{{ route('setting.log') }}">
                        Semua log
                        <x-ikon nama="arrow-right" :ukuran="13" />
                    </a>
                </div>
            </div>
            <div class="card__body card__body--flush">
                <div class="tabel-wrap">
                    <table class="tabel tabel--padat">
                        <caption class="sr-only">Enam catatan sinkronisasi terakhir</caption>
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Endpoint</th>
                                <th>Status</th>
                                <th class="num">Baris</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logTerakhir as $l)
                                <tr>
                                    <td class="nowrap mono text-xs">{{ $l['waktu'] }}</td>
                                    <td class="truncate" style="max-width:180px">{{ $l['endpoint'] }}</td>
                                    <td>
                                        <span class="badge badge--{{ $l['status'] === 'OK' ? 'hijau' : ($l['status'] === 'GAGAL' ? 'merah' : ($l['status'] === 'FORBIDDEN' ? 'merah' : 'netral')) }}">
                                            {{ $l['status'] }}
                                        </span>
                                    </td>
                                    <td class="num">{{ angka($l['rows']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
