@extends('layouts.app')

@section('judul', 'Realisasi RUP')

@section('konten')

    <div class="page-head">
        <div class="page-head__title">
            <h1>Realisasi RUP</h1>
            <p>
                Paket RUP yang sudah memiliki proses pengadaan berbasis data INAPROC riil, lengkap dengan rantai warisan kaji ulangnya.
            </p>
        </div>
        <div class="page-head__aksi">
            <span class="badge badge--brand" style="padding:6px 12px;font-size:12px">
                Tahun Anggaran {{ $tahun }} ({{ $kdKlpd }})
            </span>
        </div>
    </div>

    {{-- Komposisi per sumber --}}
    @php
        $warna = [
            'e-katalog' => '#1d4ed8',
            'tender' => '#10b981',
            'non-tender' => '#f59e0b',
            'pencatatan-non-tender' => '#8b5cf6',
        ];
        $totalSemua = array_sum(array_column($komposisi, 'total'));
    @endphp
    <div class="grid grid-4" style="margin-bottom:20px">
        @foreach ($komposisi as $k)
            @php
                $w = $warna[$k['sumber']] ?? '#6b7280';
                $persen = $totalSemua > 0 ? round(($k['total'] / $totalSemua) * 100, 1) : 0;
            @endphp
            <div class="kpi">
                <div class="kpi__top">
                    <span class="kpi__label">{{ $k['label'] }}</span>
                    <span class="legend__warna" style="background:{{ $w }};width:11px;height:11px;border-radius:50%"></span>
                </div>
                <div class="kpi__nilai">{{ angka($k['total']) }}</div>
                <div class="kpi__sub">
                    <span class="text-xs">{{ angka($k['langsung']) }} langsung</span>
                    <span class="text-xs text-mute">·</span>
                    <span class="text-xs">{{ angka($k['warisan']) }} warisan</span>
                </div>
                @if ($k['pagu'] > 0)
                    <div style="font-size:11px;color:var(--text-mute);margin-top:4px" title="{{ rp($k['pagu']) }}">
                        Pagu: <strong style="color:var(--text)">{{ ringkas($k['pagu']) }}</strong>
                    </div>
                @endif
                <div class="progres" style="margin-top:10px">
                    <div class="progres__isi" style="width:{{ $persen }}%;background:{{ $w }}"></div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Daftar Realisasi DataTable --}}
    <div class="card" id="daftar-realisasi">
        <div class="card__head" style="border-bottom:none;padding-bottom:12px">
            <div style="flex:1">
                <h2>Daftar Realisasi RUP</h2>
                <p>Menampilkan seluruh paket RUP berproses langsung dan hasil kaji ulang (warisan)</p>
            </div>
        </div>

        <x-datatable
            id="dt-realisasi"
            :action="route('realisasi')"
            :cari="$filters['cari']"
            cari-placeholder="Cari kode RUP, nama paket, atau satker..."
            :per-halaman="$perHalaman"
            :per-halaman-opsi="[10, 25, 50, 100]"
            :halaman="$halaman"
            :total="$total"
            :sort="$filters['sort']"
            :arah="$filters['arah']"
        >
            {{-- Filter Kustom Toolbar --}}
            <x-slot:filter>
                {{-- Filter Satker Combobox dengan Filter Pencarian & Multi-Select --}}
                <div class="field" style="display:flex;flex-direction:column;gap:4px;min-width:240px;max-width:300px">
                    <label style="font-size:11px;font-weight:600;color:var(--text-soft);text-transform:uppercase;letter-spacing:.3px">Satuan Kerja</label>
                    <x-combobox
                        name="satker"
                        :options="$satkerPilihan"
                        :selected="$filters['satker']"
                        :multiple="true"
                        placeholder="Semua Satuan Kerja"
                        cari="Cari satuan kerja..."
                    />
                </div>

                {{-- Filter Sumber --}}
                <div class="field" style="display:flex;flex-direction:column;gap:4px">
                    <label style="font-size:11px;font-weight:600;color:var(--text-soft);text-transform:uppercase;letter-spacing:.3px">Sumber</label>
                    <select name="sumber" class="input input-sm" style="min-width:160px;height:32px">
                        <option value="">Semua Sumber</option>
                        @foreach ($sumber as $key => $lbl)
                            <option value="{{ $key }}" @selected($filters['sumber'] === $key)>
                                {{ $lbl }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Jenis --}}
                <div class="field" style="display:flex;flex-direction:column;gap:4px">
                    <label style="font-size:11px;font-weight:600;color:var(--text-soft);text-transform:uppercase;letter-spacing:.3px">Jenis</label>
                    <select name="jenis" class="input input-sm" style="min-width:150px;height:32px">
                        <option value="">Semua Jenis</option>
                        <option value="langsung" @selected($filters['jenis'] === 'langsung')>Langsung</option>
                        <option value="warisan" @selected($filters['jenis'] === 'warisan')>Warisan</option>
                    </select>
                </div>
            </x-slot:filter>

            @include('realisasi.partials.tabel-realisasi', [
                'baris' => $baris,
                'sort' => $filters['sort'],
                'arah' => $filters['arah'],
            ])
        </x-datatable>
    </div>

    {{-- ================= PANEL DRAWER RANTAI WARISAN ================= --}}
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
                    Paket ini mewarisi proses pengadaan dari revisi kaji ulang paket sebelumnya. Rantai ditelusuri
                    hingga paket leluhur pertama yang memiliki proses riil di INAPROC.
                </div>
            </div>

            <div class="timeline" id="isi-rantai"></div>
        </div>
        <div class="drawer__foot">
            <button class="btn btn-ghost btn-sm" type="button" id="tutup-rantai-2">Tutup</button>
            <a class="btn btn-primary btn-sm" href="{{ route('report.pantau', ['jenis' => 'rup']) }}">
                Lihat di Pantau RUP
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

            for (var d = depth; d >= 1; d--) {
                html += '<div class="timeline__item">'
                     + '<span class="timeline__titik"></span>'
                     + '<div class="timeline__waktu">Kedalaman ' + d + '</div>'
                     + '<div class="timeline__judul mono">' + (d === depth ? (dari || kode) : 'Leluhur RUP') + '</div>'
                     + '<div class="timeline__ket">Mewariskan proses ke kaji ulang berikutnya</div>'
                     + '</div>';
            }

            html += '<div class="timeline__item timeline__item--kini">'
                 + '<span class="timeline__titik"></span>'
                 + '<div class="timeline__waktu">Paket Terpilih (Kini)</div>'
                 + '<div class="timeline__judul mono">' + kode + '</div>'
                 + '<div class="timeline__ket">Paket RUP aktif terumumkan</div>'
                 + '</div>';

            isi.innerHTML = html;
            latar.hidden = false;
            drawer.hidden = false;
        }

        function tutup() {
            if (latar) latar.hidden = true;
            if (drawer) drawer.hidden = true;
        }

        // Delegasi event agar tetap berfungsi setelah tabel diperbarui via AJAX DataTable
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-buka-rantai]');
            if (btn) {
                buka({
                    kode: btn.dataset.kode,
                    dari: btn.dataset.dari,
                    depth: btn.dataset.depth,
                    paket: btn.dataset.paket
                });
            }
        });

        if (latar) latar.addEventListener('click', tutup);
        var tutup1 = document.getElementById('tutup-rantai');
        if (tutup1) tutup1.addEventListener('click', tutup);
        var tutup2 = document.getElementById('tutup-rantai-2');
        if (tutup2) tutup2.addEventListener('click', tutup);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') tutup();
        });
    })();
</script>
@endpush
