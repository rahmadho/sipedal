@extends('layouts.app')

@section('judul', 'Pantau RUP')

@section('konten')

    {{-- Tabs jenis pantau --}}
    <nav class="tabs" aria-label="Kategori pantau" style="margin-bottom:16px">
        @foreach ($daftar as $k => $item)
            <a
                class="tab {{ $k === $jenis ? 'is-active' : '' }}"
                href="{{ route('report.pantau', ['jenis' => $k]) }}"
                @if ($k === $jenis) aria-current="page" @endif
            >
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    {{-- Kepala halaman --}}
    <div class="page-head">
        <div class="page-head__title">
            <h1>Pantau RUP (Rencana Umum Pengadaan)</h1>
            <p>
                Monitoring Rencana Umum Pengadaan terumumkan Tahun Anggaran {{ $tahun }}
                lingkup {{ $tenant->nama() }}. Data bersumber langsung dari SIRUP INAPROC.
            </p>
        </div>
        <div class="page-head__aksi">
            <span class="chip chip--brand">
                <x-ikon nama="clipboard" :ukuran="14" />
                {{ angka($ringkasan['total_keseluruhan']['jumlah']) }} Paket Terumumkan
            </span>
            <span class="chip">
                <x-ikon nama="wallet" :ukuran="14" />
                {{ ringkas($ringkasan['total_keseluruhan']['pagu']) }}
            </span>
        </div>
    </div>

    {{-- ================= BAGIAN 1: CARD PENYEDIA ================= --}}
    <div style="margin-bottom:8px">
        <h2 style="font-size:14px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px">
            <x-ikon nama="building" :ukuran="16" />
            Rekapitulasi Paket Penyedia (Penyedia & PDS)
        </h2>
    </div>

    <div class="grid grid-3" style="margin-bottom:20px">
        {{-- Card 1: Rekap Status PDN --}}
        <div class="card">
            <div class="card__head">
                <div>
                    <h2>Rekap Status PDN</h2>
                    <p>Proporsi Penggunaan Produk Dalam Negeri</p>
                </div>
                <span class="badge badge--hijau head-aksi">{{ persen($ringkasan['pdn']['pdn']['persen']) }} PDN</span>
            </div>
            <div class="card__body">
                <div style="display:flex;flex-direction:column;gap:12px">
                    {{-- Bar visual --}}
                    <div style="height:8px;background:var(--gray-200);border-radius:999px;overflow:hidden;display:flex">
                        <div style="width:{{ $ringkasan['pdn']['pdn']['persen'] }}%;background:var(--green-600);transition:width .3s" title="PDN: {{ persen($ringkasan['pdn']['pdn']['persen']) }}"></div>
                        <div style="width:{{ $ringkasan['pdn']['non_pdn']['persen'] }}%;background:var(--amber-500);transition:width .3s" title="Non-PDN: {{ persen($ringkasan['pdn']['non_pdn']['persen']) }}"></div>
                    </div>

                    {{-- Item PDN --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;background:var(--gray-50);border-radius:var(--radius-md)">
                        <div>
                            <span class="badge badge--hijau" style="margin-bottom:2px">
                                <span class="badge__dot"></span> PDN
                            </span>
                            <div style="font-size:11px;color:var(--text-mute)">{{ angka($ringkasan['pdn']['pdn']['jumlah']) }} paket</div>
                        </div>
                        <div style="text-align:right">
                            <div style="font-weight:700;font-size:13px">{{ ringkas($ringkasan['pdn']['pdn']['pagu']) }}</div>
                            <div style="font-size:11px;color:var(--text-mute)">{{ persen($ringkasan['pdn']['pdn']['persen']) }}</div>
                        </div>
                    </div>

                    {{-- Item Non-PDN --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;background:var(--gray-50);border-radius:var(--radius-md)">
                        <div>
                            <span class="badge badge--amber" style="margin-bottom:2px">
                                <span class="badge__dot"></span> Non-PDN
                            </span>
                            <div style="font-size:11px;color:var(--text-mute)">{{ angka($ringkasan['pdn']['non_pdn']['jumlah']) }} paket</div>
                        </div>
                        <div style="text-align:right">
                            <div style="font-weight:700;font-size:13px">{{ ringkas($ringkasan['pdn']['non_pdn']['pagu']) }}</div>
                            <div style="font-size:11px;color:var(--text-mute)">{{ persen($ringkasan['pdn']['non_pdn']['persen']) }}</div>
                        </div>
                    </div>

                    {{-- Total PDN --}}
                    <div style="display:flex;justify-content:space-between;align-items:center;padding-top:6px;border-top:1px dashed var(--border);font-size:12px">
                        <span style="font-weight:600;color:var(--text-mute)">Total Penyedia:</span>
                        <span style="font-weight:700">{{ angka($ringkasan['pdn']['total']['jumlah']) }} paket · {{ ringkas($ringkasan['pdn']['total']['pagu']) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2: Berdasarkan Tipe Paket (Statistik Diproses vs Belum) --}}
        <div class="card">
            <div class="card__head">
                <div>
                    <h2>Berdasarkan Tipe Paket</h2>
                    <p>Proses Pengadaan RUP Terumumkan</p>
                </div>
                @php
                    $persenDiproses = $ringkasan['tipe_paket']['total']['jumlah'] > 0
                        ? round(($ringkasan['tipe_paket']['total']['diproses'] / $ringkasan['tipe_paket']['total']['jumlah']) * 100, 1)
                        : 0;
                @endphp
                <span class="badge badge--biru head-aksi">{{ persen($persenDiproses) }} Diproses</span>
            </div>
            <div class="card__body">
                <div style="display:flex;flex-direction:column;gap:12px">
                    {{-- Bar visual diproses vs belum --}}
                    <div style="height:8px;background:var(--gray-200);border-radius:999px;overflow:hidden;display:flex">
                        <div style="width:{{ $persenDiproses }}%;background:var(--green-600);transition:width .3s" title="Diproses: {{ persen($persenDiproses) }}"></div>
                        <div style="width:{{ 100 - $persenDiproses }}%;background:var(--gray-400);transition:width .3s" title="Belum: {{ persen(100 - $persenDiproses) }}"></div>
                    </div>

                    {{-- Penyedia Murni --}}
                    <div style="padding:8px 10px;background:var(--gray-50);border-radius:var(--radius-md)">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                            <strong style="font-size:12.5px">Penyedia</strong>
                            <span style="font-weight:700;font-size:12.5px">{{ angka($ringkasan['tipe_paket']['penyedia']['jumlah']) }} paket</span>
                        </div>
                        <div style="display:flex;gap:6px;font-size:11px">
                            <span class="badge badge--hijau" title="Sudah Masuk Proses Pengadaan">
                                <span class="badge__dot"></span> Diproses: {{ angka($ringkasan['tipe_paket']['penyedia']['diproses']) }}
                            </span>
                            <span class="badge badge--netral" title="Belum Ada Proses Pengadaan">
                                <span class="badge__dot"></span> Belum: {{ angka($ringkasan['tipe_paket']['penyedia']['belum_diproses']) }}
                            </span>
                        </div>
                    </div>

                    {{-- PDS --}}
                    <div style="padding:8px 10px;background:var(--gray-50);border-radius:var(--radius-md)">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                            <strong style="font-size:12.5px">Penyedia Dalam Swakelola (PDS)</strong>
                            <span style="font-weight:700;font-size:12.5px">{{ angka($ringkasan['tipe_paket']['pds']['jumlah']) }} paket</span>
                        </div>
                        <div style="display:flex;gap:6px;font-size:11px">
                            <span class="badge badge--hijau">
                                <span class="badge__dot"></span> Diproses: {{ angka($ringkasan['tipe_paket']['pds']['diproses']) }}
                            </span>
                            <span class="badge badge--netral">
                                <span class="badge__dot"></span> Belum: {{ angka($ringkasan['tipe_paket']['pds']['belum_diproses']) }}
                            </span>
                        </div>
                    </div>

                    {{-- Total Diproses --}}
                    <div style="display:flex;justify-content:space-between;align-items:center;padding-top:6px;border-top:1px dashed var(--border);font-size:12px">
                        <span style="font-weight:600;color:var(--text-mute)">Total Realisasi Proses:</span>
                        <span style="font-weight:700;color:var(--green-700)">{{ angka($ringkasan['tipe_paket']['total']['diproses']) }} dari {{ angka($ringkasan['tipe_paket']['total']['jumlah']) }} paket</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 3: Berdasarkan Jenis Pengadaan --}}
        <div class="card">
            <div class="card__head">
                <div>
                    <h2>Jenis Pengadaan</h2>
                    <p>Jumlah, Pagu & Persentase Pagu</p>
                </div>
                <span class="badge badge--netral head-aksi">{{ count($ringkasan['jenis_pengadaan']) }} Kategori</span>
            </div>
            <div class="card__body" style="padding-top:12px;padding-bottom:12px">
                <div style="display:flex;flex-direction:column;gap:10px">
                    @foreach ($ringkasan['jenis_pengadaan'] as $j)
                        <div>
                            <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;margin-bottom:3px">
                                <span style="font-weight:600;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:160px" title="{{ $j['jenis'] }}">
                                    {{ $j['jenis'] }}
                                </span>
                                <span style="font-variant-numeric:tabular-nums;font-weight:600">
                                    {{ ringkas($j['pagu']) }}
                                    <span style="color:var(--text-mute);font-weight:normal;font-size:11px">({{ persen($j['persen']) }})</span>
                                </span>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px">
                                <div style="flex:1;height:5px;background:var(--gray-200);border-radius:999px;overflow:hidden">
                                    <div style="width:{{ min(100, $j['persen']) }}%;height:100%;background:var(--brand-600);border-radius:999px"></div>
                                </div>
                                <span style="font-size:10.5px;color:var(--text-mute);white-space:nowrap">{{ angka($j['jumlah']) }} pkt</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ================= BAGIAN 2: CARD PAKET SWAKELOLA ================= --}}
    <div style="margin-bottom:8px">
        <h2 style="font-size:14px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px">
            <x-ikon nama="layers" :ukuran="16" />
            Rekapitulasi Paket Swakelola Berdasarkan Tipe
        </h2>
    </div>

    <div class="grid grid-4" style="margin-bottom:24px">
        @foreach ($ringkasan['swakelola']['tipe'] as $noTipe => $t)
            <div class="kpi">
                <div class="kpi__top">
                    <span class="badge badge--brand">{{ $t['label'] }}</span>
                    <span class="kpi__ikon" style="font-size:11.5px;font-weight:700;color:var(--brand-700)">
                        {{ persen($t['persen']) }}
                    </span>
                </div>
                <div class="kpi__nilai" style="font-size:22px;margin-top:6px">
                    {{ angka($t['jumlah']) }} <span style="font-size:12px;font-weight:normal;color:var(--text-mute)">paket</span>
                </div>
                <div style="font-size:13px;font-weight:700;color:var(--text);margin-top:2px">
                    {{ ringkas($t['pagu']) }}
                </div>
                <div class="kpi__sub" style="margin-top:4px;font-size:11px;line-height:1.3" title="{{ $t['sub'] }}">
                    {{ $t['sub'] }}
                </div>
            </div>
        @endforeach
    </div>

    {{-- ================= BAGIAN 3: TABEL REKAP PER SATUAN KERJA ================= --}}
    <div class="card" style="margin-bottom:24px" id="rekap-satker">
        <div class="card__head">
            <div>
                <h2>Rekapitulasi per Satuan Kerja</h2>
                <p>Distribusi paket dan pagu Penyedia, Swakelola, dan Penyedia Dalam Swakelola (PDS) per Satker</p>
            </div>
            <div class="head-aksi">
                <form method="GET" action="{{ route('report.pantau', ['jenis' => 'rup']) }}#rekap-satker" style="display:flex;gap:6px">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <div style="position:relative">
                        <input
                            type="search"
                            name="cari_satker"
                            value="{{ $cariSatker }}"
                            placeholder="Cari satker..."
                            class="input input-sm"
                            style="width:200px;padding-left:26px"
                        >
                        <span style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:var(--text-mute);pointer-events:none">
                            <x-ikon nama="search" :ukuran="13" />
                        </span>
                    </div>
                    @if ($cariSatker !== '')
                        <a href="{{ route('report.pantau', ['jenis' => 'rup', 'tab' => $tab]) }}#rekap-satker" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="tabel-wrap">
            <table class="tabel" aria-label="Rekapitulasi RUP per Satker">
                <thead>
                    <tr>
                        <th style="width:44px" class="num">No</th>
                        <th>Satuan Kerja</th>
                        <th class="num" style="background:var(--blue-50);border-left:1px solid var(--border)">Penyedia (Pkt)</th>
                        <th class="num" style="background:var(--blue-50)">Penyedia (Pagu)</th>
                        <th class="num" style="background:var(--green-50);border-left:1px solid var(--border)">Swakelola (Pkt)</th>
                        <th class="num" style="background:var(--green-50)">Swakelola (Pagu)</th>
                        <th class="num" style="background:var(--amber-50);border-left:1px solid var(--border)">PDS (Pkt)</th>
                        <th class="num" style="background:var(--amber-50)">PDS (Pagu)</th>
                        <th class="num" style="background:var(--gray-100);border-left:1px solid var(--border)">Total Paket</th>
                        <th class="num" style="background:var(--gray-100)">Total Pagu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rekapSatker['baris'] as $idx => $s)
                        <tr>
                            <td class="num" style="color:var(--text-mute)">{{ $idx + 1 }}</td>
                            <td>
                                <a
                                    href="{{ route('report.pantau', ['jenis' => 'rup', 'tab' => $tab, 'satker' => $s['kd_satker']]) }}#daftar-paket"
                                    style="font-weight:600;color:var(--brand-700)"
                                    title="Klik untuk menyaring paket satker ini"
                                >
                                    {{ $s['nama_satker'] }}
                                </a>
                                @if ($s['kd_satker_str'] !== '')
                                    <div style="font-size:11px;color:var(--text-mute)">{{ $s['kd_satker_str'] }}</div>
                                @endif
                            </td>
                            <td class="num" style="border-left:1px solid var(--border)">{{ angka($s['penyedia']['paket']) }}</td>
                            <td class="num" title="{{ rp($s['penyedia']['pagu']) }}">{{ ringkas($s['penyedia']['pagu']) }}</td>
                            <td class="num" style="border-left:1px solid var(--border)">{{ angka($s['swakelola']['paket']) }}</td>
                            <td class="num" title="{{ rp($s['swakelola']['pagu']) }}">{{ ringkas($s['swakelola']['pagu']) }}</td>
                            <td class="num" style="border-left:1px solid var(--border)">{{ angka($s['pds']['paket']) }}</td>
                            <td class="num" title="{{ rp($s['pds']['pagu']) }}">{{ ringkas($s['pds']['pagu']) }}</td>
                            <td class="num" style="border-left:1px solid var(--border);font-weight:700">{{ angka($s['total']['paket']) }}</td>
                            <td class="num" style="font-weight:700;color:var(--brand-700)" title="{{ rp($s['total']['pagu']) }}">{{ ringkas($s['total']['pagu']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align:center;padding:24px;color:var(--text-mute)">
                                Tidak ada data satuan kerja yang sesuai dengan kata kunci pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background:var(--gray-100);font-weight:700">
                        <td colspan="2" style="text-align:right;padding-right:16px">GRAND TOTAL:</td>
                        <td class="num" style="border-left:1px solid var(--border)">{{ angka($rekapSatker['grand_total']['penyedia']['paket']) }}</td>
                        <td class="num" title="{{ rp($rekapSatker['grand_total']['penyedia']['pagu']) }}">{{ ringkas($rekapSatker['grand_total']['penyedia']['pagu']) }}</td>
                        <td class="num" style="border-left:1px solid var(--border)">{{ angka($rekapSatker['grand_total']['swakelola']['paket']) }}</td>
                        <td class="num" title="{{ rp($rekapSatker['grand_total']['swakelola']['pagu']) }}">{{ ringkas($rekapSatker['grand_total']['swakelola']['pagu']) }}</td>
                        <td class="num" style="border-left:1px solid var(--border)">{{ angka($rekapSatker['grand_total']['pds']['paket']) }}</td>
                        <td class="num" title="{{ rp($rekapSatker['grand_total']['pds']['pagu']) }}">{{ ringkas($rekapSatker['grand_total']['pds']['pagu']) }}</td>
                        <td class="num" style="border-left:1px solid var(--border);color:var(--brand-800)">{{ angka($rekapSatker['grand_total']['total']['paket']) }}</td>
                        <td class="num" style="color:var(--brand-800)" title="{{ rp($rekapSatker['grand_total']['total']['pagu']) }}">{{ ringkas($rekapSatker['grand_total']['total']['pagu']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ================= BAGIAN 4: TABEL DAFTAR PAKET ================= --}}
    <div class="card" id="daftar-paket">
        {{-- Header Tab: Penyedia / PDS vs Swakelola --}}
        <div class="card__head" style="border-bottom:none;padding-bottom:0">
            <div style="flex:1">
                <h2>Daftar Paket RUP Terumumkan</h2>
                <p>Hanya menampilkan paket dengan status Terumumkan atau Sudah Diproses</p>
            </div>
        </div>

        {{-- Segmented Sub-tab Switcher --}}
        <div style="padding:0 18px 12px;border-bottom:1px solid var(--border);display:flex;gap:8px">
            <a
                href="{{ route('report.pantau', ['jenis' => 'rup', 'tab' => 'penyedia']) }}#daftar-paket"
                class="btn {{ $tab === 'penyedia' ? 'btn-primary' : 'btn-ghost' }} btn-sm"
                style="gap:6px"
            >
                <x-ikon nama="building" :ukuran="14" />
                Penyedia & PDS
                <span class="badge {{ $tab === 'penyedia' ? 'badge--brand' : 'badge--netral' }}">
                    {{ angka($ringkasan['tipe_paket']['total']['jumlah']) }}
                </span>
            </a>
            <a
                href="{{ route('report.pantau', ['jenis' => 'rup', 'tab' => 'swakelola']) }}#daftar-paket"
                class="btn {{ $tab === 'swakelola' ? 'btn-primary' : 'btn-ghost' }} btn-sm"
                style="gap:6px"
            >
                <x-ikon nama="layers" :ukuran="14" />
                Swakelola
                <span class="badge {{ $tab === 'swakelola' ? 'badge--brand' : 'badge--netral' }}">
                    {{ angka($ringkasan['swakelola']['total']['jumlah']) }}
                </span>
            </a>
        </div>

        {{-- Filter toolbar --}}
        <div class="card__body" style="padding-top:14px;padding-bottom:14px;background:var(--gray-50);border-bottom:1px solid var(--border)">
            <form method="GET" action="{{ route('report.pantau', ['jenis' => 'rup']) }}#daftar-paket" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center">
                <input type="hidden" name="tab" value="{{ $tab }}">

                {{-- Cari Teks --}}
                <div style="position:relative;flex:1;min-width:220px">
                    <input
                        type="search"
                        name="cari"
                        value="{{ $filters['cari'] }}"
                        placeholder="Cari kode RUP / nama paket..."
                        class="input input-sm"
                        style="width:100%;padding-left:28px"
                    >
                    <span style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--text-mute);pointer-events:none">
                        <x-ikon nama="search" :ukuran="13" />
                    </span>
                </div>

                {{-- Filter Satker --}}
                <select name="satker" class="input input-sm" style="max-width:240px">
                    <option value="">Semua Satuan Kerja</option>
                    @foreach ($satkerPilihan as $stk)
                        <option value="{{ $stk['nilai'] }}" @selected($filters['satker'] == $stk['nilai'])>
                            {{ $stk['label'] }}
                        </option>
                    @endforeach
                </select>

                @if ($tab === 'penyedia')
                    {{-- Filter Tipe Paket --}}
                    <select name="tipe_paket" class="input input-sm">
                        <option value="">Semua Tipe</option>
                        <option value="penyedia" @selected($filters['tipe_paket'] === 'penyedia')>Penyedia (Murni)</option>
                        <option value="pds" @selected($filters['tipe_paket'] === 'pds')>Penyedia Dalam Swakelola (PDS)</option>
                    </select>

                    {{-- Filter Status PDN --}}
                    <select name="status_pdn" class="input input-sm">
                        <option value="">Semua PDN</option>
                        <option value="PDN" @selected($filters['status_pdn'] === 'PDN')>PDN</option>
                        <option value="Non-PDN" @selected($filters['status_pdn'] === 'Non-PDN')>Non-PDN</option>
                    </select>

                    {{-- Filter Status Proses --}}
                    <select name="status_proses" class="input input-sm">
                        <option value="">Semua Status Proses</option>
                        <option value="diproses" @selected($filters['status_proses'] === 'diproses')>Sudah Diproses</option>
                        <option value="belum" @selected($filters['status_proses'] === 'belum')>Belum Diproses</option>
                    </select>

                    {{-- Filter Jenis Pengadaan --}}
                    <select name="jenis_pengadaan" class="input input-sm">
                        <option value="">Semua Jenis Pengadaan</option>
                        @foreach ($jenisOpsi as $jo)
                            <option value="{{ $jo }}" @selected($filters['jenis_pengadaan'] === $jo)>{{ $jo }}</option>
                        @endforeach
                    </select>
                @else
                    {{-- Filter Tipe Swakelola --}}
                    <select name="tipe_swakelola" class="input input-sm">
                        <option value="">Semua Tipe Swakelola</option>
                        <option value="1" @selected($filters['tipe_swakelola'] == 1)>Tipe I</option>
                        <option value="2" @selected($filters['tipe_swakelola'] == 2)>Tipe II</option>
                        <option value="3" @selected($filters['tipe_swakelola'] == 3)>Tipe III</option>
                        <option value="4" @selected($filters['tipe_swakelola'] == 4)>Tipe IV</option>
                    </select>
                @endif

                <button type="submit" class="btn btn-primary btn-sm">Terapkan</button>
                <a href="{{ route('report.pantau', ['jenis' => 'rup', 'tab' => $tab]) }}#daftar-paket" class="btn btn-ghost btn-sm">Reset</a>
            </form>
        </div>

        {{-- Konten Tabel Berdasarkan Tab --}}
        @if ($tab === 'penyedia')
            {{-- Tabel Penyedia & PDS --}}
            <div class="tabel-wrap">
                <table class="tabel" aria-label="Daftar Paket RUP Penyedia dan PDS">
                    <thead>
                        <tr>
                            <th style="width:44px" class="num">No</th>
                            <th style="width:110px">Kode RUP</th>
                            <th>Nama Paket</th>
                            <th>Satuan Kerja</th>
                            <th>Tipe</th>
                            <th>Jenis Pengadaan</th>
                            <th>PDN</th>
                            <th>Status Proses</th>
                            <th class="num">Pagu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($baris as $p)
                            <tr>
                                <td class="num" style="color:var(--text-mute)">{{ $p['no'] }}</td>
                                <td>
                                    <code style="font-weight:600;font-size:11.5px">{{ $p['kd_rup'] }}</code>
                                </td>
                                <td>
                                    <div style="font-weight:600;max-width:320px;line-height:1.35">{{ $p['nama_paket'] }}</div>
                                    <div style="font-size:11px;color:var(--text-mute);margin-top:2px">
                                        Metode: {{ $p['metode_pengadaan'] }}
                                        @if (! empty($p['tgl_pengumuman']))
                                            · {{ tgl($p['tgl_pengumuman']) }}
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size:12px;max-width:200px">{{ $p['nama_satker'] }}</div>
                                </td>
                                <td>
                                    @if ($p['is_pds'])
                                        <span class="badge badge--amber" title="Penyedia Dalam Swakelola">PDS</span>
                                    @else
                                        <span class="badge badge--netral">Penyedia</span>
                                    @endif
                                </td>
                                <td style="font-size:12px">{{ $p['jenis_pengadaan'] }}</td>
                                <td>
                                    @if ($p['status_pdn'] === 'PDN')
                                        <span class="badge badge--hijau">PDN</span>
                                    @else
                                        <span class="badge badge--amber">Non-PDN</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($p['status_proses'] === 'Diproses')
                                        <span class="badge badge--hijau" title="Sumber: {{ $p['sumber_proses'] }}">
                                            <span class="badge__dot"></span> Diproses
                                        </span>
                                    @else
                                        <span class="badge badge--netral" title="Belum ada proses pengadaan">
                                            <span class="badge__dot"></span> Belum
                                        </span>
                                    @endif
                                </td>
                                <td class="num" style="font-weight:700">
                                    <span title="{{ rp($p['pagu']) }}">{{ ringkas($p['pagu']) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="text-align:center;padding:28px;color:var(--text-mute)">
                                    Tidak ada data paket penyedia yang memenuhi kriteria pencarian.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            {{-- Tabel Swakelola --}}
            <div class="tabel-wrap">
                <table class="tabel" aria-label="Daftar Paket RUP Swakelola">
                    <thead>
                        <tr>
                            <th style="width:44px" class="num">No</th>
                            <th style="width:110px">Kode RUP</th>
                            <th>Nama Paket</th>
                            <th>Satuan Kerja</th>
                            <th>Tipe Swakelola</th>
                            <th>Penyelenggara</th>
                            <th class="num">Pagu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($baris as $s)
                            <tr>
                                <td class="num" style="color:var(--text-mute)">{{ $s['no'] }}</td>
                                <td>
                                    <code style="font-weight:600;font-size:11.5px">{{ $s['kd_rup'] }}</code>
                                </td>
                                <td>
                                    <div style="font-weight:600;max-width:340px;line-height:1.35">{{ $s['nama_paket'] }}</div>
                                    @if (! empty($s['tgl_pengumuman']))
                                        <div style="font-size:11px;color:var(--text-mute);margin-top:2px">
                                            Diumumkan: {{ tgl($s['tgl_pengumuman']) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div style="font-size:12px;max-width:220px">{{ $s['nama_satker'] }}</div>
                                </td>
                                <td>
                                    <span class="badge badge--brand">{{ $s['label_tipe'] }}</span>
                                </td>
                                <td style="font-size:12px;max-width:200px">
                                    {{ $s['penyelenggara'] }}
                                </td>
                                <td class="num" style="font-weight:700">
                                    <span title="{{ rp($s['pagu']) }}">{{ ringkas($s['pagu']) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align:center;padding:28px;color:var(--text-mute)">
                                    Tidak ada data paket swakelola yang memenuhi kriteria pencarian.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Footer Paginasi --}}
        <div class="card__foot">
            <x-paginasi :halaman="$halaman" :per-halaman="$perHalaman" :total="$total" />
        </div>
    </div>

@endsection
