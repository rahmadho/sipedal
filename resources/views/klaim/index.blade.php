@extends('layouts.app')

@section('judul', 'Klaim Paket')

@section('konten')

    <div class="page-head">
        <div class="page-head__title">
            <h1>Klaim Paket</h1>
            <p>
                Pengajuan klaim paket oleh penyedia atau PPK beserta status verifikasinya.
                Halaman ini menjadi titik temu antara pengajuan dan keputusan.
            </p>
        </div>
        <div class="page-head__aksi">
            <button class="btn btn-ghost btn-sm" type="button">
                <x-ikon nama="download" :ukuran="15" />
                Ekspor
            </button>
            <button class="btn btn-primary btn-sm" type="button">
                <x-ikon nama="plus" :ukuran="15" />
                Ajukan Klaim
            </button>
        </div>
    </div>

    {{-- Ringkasan status --}}
    <div class="grid grid-4" style="margin-bottom:16px">
        @php
            $ringkasStatus = [
                ['label' => 'Menunggu Verifikasi', 'jumlah' => 42, 'ikon' => 'clock', 'warna' => 'amber'],
                ['label' => 'Diverifikasi', 'jumlah' => 136, 'ikon' => 'check-circle', 'warna' => 'hijau'],
                ['label' => 'Ditolak', 'jumlah' => 18, 'ikon' => 'x', 'warna' => 'merah'],
                ['label' => 'Selesai', 'jumlah' => 52, 'ikon' => 'check', 'warna' => 'brand'],
            ];
        @endphp
        @foreach ($ringkasStatus as $s)
            <div class="kpi">
                <div class="kpi__top">
                    <span class="kpi__label">{{ $s['label'] }}</span>
                    <span class="kpi__ikon" style="background:var(--{{ $s['warna'] === 'amber' ? 'amber-50' : ($s['warna'] === 'hijau' ? 'green-50' : ($s['warna'] === 'merah' ? 'red-50' : 'brand-50')) }});color:var(--{{ $s['warna'] === 'amber' ? 'amber-700' : ($s['warna'] === 'hijau' ? 'green-700' : ($s['warna'] === 'merah' ? 'red-700' : 'brand-700')) }})">
                        <x-ikon :nama="$s['ikon']" :ukuran="17" />
                    </span>
                </div>
                <div class="kpi__nilai">{{ angka($s['jumlah']) }}</div>
                <div class="kpi__sub">dari {{ angka($total) }} klaim</div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card__head">
            <div>
                <h2>Daftar Klaim</h2>
                <p>{{ angka($total) }} klaim terdaftar</p>
            </div>
        </div>

        <div class="tabs">
            <a class="tab is-active" href="#">Semua <span class="tab__jml">{{ angka($total) }}</span></a>
            <a class="tab" href="#">Menunggu <span class="tab__jml">42</span></a>
            <a class="tab" href="#">Diverifikasi <span class="tab__jml">136</span></a>
            <a class="tab" href="#">Ditolak <span class="tab__jml">18</span></a>
            <a class="tab" href="#">Selesai <span class="tab__jml">52</span></a>
        </div>

        <form class="toolbar" method="GET" action="{{ route('klaim') }}">
            <div class="field field--cari">
                <label for="cari">Cari klaim</label>
                <div class="input-wrap">
                    <span class="input-ikon"><x-ikon nama="search" :ukuran="14" /></span>
                    <input class="input" type="search" id="cari" name="cari" placeholder="Nomor klaim, paket, atau penyedia" value="{{ request('cari') }}">
                </div>
            </div>
            <div class="field field--md">
                <label for="status">Status</label>
                <select class="select" id="status" name="status">
                    <option value="">Semua status</option>
                    <option>Menunggu</option>
                    <option>Diverifikasi</option>
                    <option>Ditolak</option>
                    <option>Selesai</option>
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
                <caption class="sr-only">Daftar klaim paket beserta status verifikasi</caption>
                <thead>
                    <tr>
                        <th>No Klaim</th>
                        <th>Paket</th>
                        <th>Penyedia</th>
                        <th class="num">Nilai Klaim</th>
                        <th>Diajukan</th>
                        <th>Verifikator</th>
                        <th>Status</th>
                        <th style="width:56px"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($baris as $b)
                        <tr>
                            <td class="sel-kode">{{ $b['no_klaim'] }}</td>
                            <td>
                                <div class="sel-nama truncate" style="max-width:250px" title="{{ $b['nama_paket'] }}">{{ $b['nama_paket'] }}</div>
                                <div class="sel-sub mono">{{ $b['kd_rup'] }}</div>
                            </td>
                            <td class="truncate" style="max-width:180px" title="{{ $b['penyedia'] }}">{{ $b['penyedia'] }}</td>
                            <td class="num">
                                <div class="fw-600">{{ rp($b['nilai']) }}</div>
                                <div class="text-xs text-mute">{{ ringkas($b['nilai']) }}</div>
                            </td>
                            <td class="nowrap text-sm">{{ $b['diajukan'] }}</td>
                            <td class="text-sm">{{ $b['verifikator'] }}</td>
                            <td>
                                @php
                                    $badge = match ($b['status']) {
                                        'menunggu' => ['amber', 'Menunggu'],
                                        'diverifikasi' => ['biru', 'Diverifikasi'],
                                        'ditolak' => ['merah', 'Ditolak'],
                                        default => ['hijau', 'Selesai'],
                                    };
                                @endphp
                                <span class="badge badge--{{ $badge[0] }}">
                                    <span class="badge__dot"></span>
                                    {{ $badge[1] }}
                                </span>
                            </td>
                            <td>
                                <button class="icon-btn" type="button" aria-label="Buka klaim {{ $b['no_klaim'] }}">
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
