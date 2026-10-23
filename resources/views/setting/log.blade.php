@extends('layouts.app')

@section('judul', 'Log Sinkronisasi')

@section('konten')

    <div class="page-head">
        <div class="page-head__title">
            <h1>Log Sinkronisasi</h1>
            <p>
                Riwayat penarikan data dari API INAPROC. Halaman ini menjadi bukti apakah
                angka pada dashboard masih layak dipercaya.
            </p>
        </div>
        <div class="page-head__aksi">
            <button class="btn btn-ghost btn-sm" type="button">
                <x-ikon nama="download" :ukuran="15" />
                Unduh Log
            </button>
            <button class="btn btn-primary btn-sm" type="button">
                <x-ikon nama="refresh" :ukuran="15" />
                Muat Ulang
            </button>
        </div>
    </div>

    @php
        $jmlOk = collect($baris)->where('status', 'OK')->count();
        $jmlGagal = collect($baris)->whereIn('status', ['GAGAL', 'FORBIDDEN'])->count();
        $jmlKosong = collect($baris)->where('status', 'KOSONG')->count();
        $rataDurasi = (int) round(collect($baris)->avg('durasi_ms') ?? 0);
    @endphp

    <div class="grid grid-4" style="margin-bottom:16px">
        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__label">Berhasil</span>
                <span class="kpi__ikon" style="background:var(--green-50);color:var(--green-700)"><x-ikon nama="check-circle" :ukuran="17" /></span>
            </div>
            <div class="kpi__nilai">{{ angka($jmlOk) }}</div>
            <div class="kpi__sub">Dari {{ count($baris) }} catatan</div>
        </div>
        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__label">Gagal / Ditolak</span>
                <span class="kpi__ikon" style="background:var(--red-50);color:var(--red-700)"><x-ikon nama="alert" :ukuran="17" /></span>
            </div>
            <div class="kpi__nilai">{{ angka($jmlGagal) }}</div>
            <div class="kpi__sub">Perlu penanganan</div>
        </div>
        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__label">Kosong</span>
                <span class="kpi__ikon"><x-ikon nama="inbox" :ukuran="17" /></span>
            </div>
            <div class="kpi__nilai">{{ angka($jmlKosong) }}</div>
            <div class="kpi__sub">Tidak ada data tahun ini</div>
        </div>
        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__label">Rata-rata Durasi</span>
                <span class="kpi__ikon"><x-ikon nama="clock" :ukuran="17" /></span>
            </div>
            <div class="kpi__nilai">{{ durasi($rataDurasi) }}</div>
            <div class="kpi__sub">Per permintaan</div>
        </div>
    </div>

    <div class="card">
        <div class="card__head">
            <div>
                <h2>Riwayat Penarikan Data</h2>
                <p>{{ angka($total) }} catatan tersimpan</p>
            </div>
            <div class="head-aksi">
                <span class="badge badge--hijau">
                    <span class="badge__dot"></span>
                    Terakhir: 23/10/2026 18:56
                </span>
            </div>
        </div>

        <form class="toolbar" method="GET" action="{{ route('setting.log') }}">
            <div class="field field--cari">
                <label for="cari">Cari endpoint</label>
                <div class="input-wrap">
                    <span class="input-ikon"><x-ikon nama="search" :ukuran="14" /></span>
                    <input class="input" type="search" id="cari" name="cari" placeholder="Nama endpoint" value="{{ request('cari') }}">
                </div>
            </div>
            <div class="field field--sm">
                <label for="status">Status</label>
                <select class="select" id="status" name="status">
                    <option value="">Semua</option>
                    <option>OK</option>
                    <option>KOSONG</option>
                    <option>GAGAL</option>
                    <option>FORBIDDEN</option>
                    <option>NONAKTIF</option>
                </select>
            </div>
            <div class="field field--sm">
                <label for="grup">Grup</label>
                <select class="select" id="grup" name="grup">
                    <option value="">Semua</option>
                    <option>rup</option>
                    <option>tender</option>
                    <option>ekatalog</option>
                    <option>master</option>
                </select>
            </div>
            <div class="toolbar__aksi">
                <button class="btn btn-primary btn-sm" type="submit">
                    <x-ikon nama="filter" :ukuran="14" />
                    Terapkan
                </button>
            </div>
        </form>

        <div class="tabel-wrap">
            <table class="tabel">
                <caption class="sr-only">Riwayat penarikan data dari API INAPROC</caption>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Endpoint</th>
                        <th>Grup</th>
                        <th>API</th>
                        <th>KLPD</th>
                        <th>Status</th>
                        <th class="num">Baris</th>
                        <th class="num">Halaman</th>
                        <th class="num">Durasi</th>
                        <th>Pesan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($baris as $b)
                        <tr>
                            <td class="nowrap mono text-xs">{{ $b['waktu'] }}</td>
                            <td class="sel-nama">{{ $b['endpoint'] }}</td>
                            <td><span class="chip">{{ $b['grup'] }}</span></td>
                            <td class="text-xs text-mute">{{ $b['api'] }}</td>
                            <td class="sel-kode">{{ $b['klpd'] }}</td>
                            <td>
                                @php
                                    $badge = match ($b['status']) {
                                        'OK' => 'hijau',
                                        'KOSONG' => 'netral',
                                        'GAGAL', 'FORBIDDEN' => 'merah',
                                        default => 'amber',
                                    };
                                @endphp
                                <span class="badge badge--{{ $badge }}">
                                    <span class="badge__dot"></span>
                                    {{ $b['status'] }}
                                </span>
                            </td>
                            <td class="num">{{ angka($b['rows']) }}</td>
                            <td class="num text-mute">{{ $b['pages'] }}</td>
                            <td class="num text-mute">{{ durasi($b['durasi_ms']) }}</td>
                            <td class="truncate text-xs text-mute" style="max-width:220px" title="{{ $b['pesan'] }}">{{ $b['pesan'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <x-paginasi :halaman="$halaman" :per-halaman="$perHalaman" :total="$total" />
    </div>

@endsection
