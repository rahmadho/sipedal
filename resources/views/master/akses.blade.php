@extends('layouts.app')

@section('judul', 'Akses')

@section('konten')

    <div class="page-head">
        <div class="page-head__title">
            <h1>Akses &amp; Peran</h1>
            <p>
                Sebelas peran pengguna beserta menu yang dapat diakses. Peran menentukan
                menu yang tampil; pembatasan data yang sebenarnya tetap ditegakkan di server.
            </p>
        </div>
        <div class="page-head__aksi">
            <button class="btn btn-primary btn-sm" type="button">
                <x-ikon nama="plus" :ukuran="15" />
                Undang Pengguna
            </button>
        </div>
    </div>

    <div class="banner banner--peringatan">
        <span class="banner__ikon"><x-ikon nama="alert" :ukuran="17" /></span>
        <div>
            <strong>Tahap UI/UX.</strong> Menu disaring berdasarkan peran pada lapisan tampilan.
            Otorisasi sesungguhnya belum aktif — jangan gunakan untuk data nyata sebelum
            tahap API selesai.
        </div>
    </div>

    <div class="grid grid-2">
        @foreach ($peran as $kunci => $p)
            @php
                $akses = $p['akses'] ?? [];
                $semua = in_array('*', $akses, true);
            @endphp

            <div class="peran-item">
                <span class="peran-item__kode">{{ $p['ringkas'] }}</span>
                <div class="grow" style="min-width:0">
                    <div class="row-between gap-2">
                        <div>
                            <div class="peran-item__nama">{{ $p['label'] }}</div>
                            <div class="peran-item__lingkup">{{ $p['lingkup'] }}</div>
                        </div>
                        @if ($kunci === 'super_admin')
                            <span class="badge badge--merah">Akses penuh</span>
                        @endif
                    </div>

                    <div class="peran-item__akses">
                        @if ($semua)
                            <span class="chip chip--brand">
                                <x-ikon nama="star" :ukuran="11" />
                                Seluruh menu
                            </span>
                        @else
                            @foreach (array_slice($akses, 0, 6) as $a)
                                <span class="chip">{{ str_replace(['.', '_'], [' · ', ' '], $a) }}</span>
                            @endforeach
                            @if (count($akses) > 6)
                                <span class="chip">+{{ count($akses) - 6 }} lagi</span>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

@endsection
