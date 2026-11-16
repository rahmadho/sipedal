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
        <div class="card__head" style="border-bottom:none;padding-bottom:12px">
            <div style="flex:1">
                <h2>Rekapitulasi per Satuan Kerja</h2>
                <p>Distribusi paket dan pagu Penyedia, Swakelola, dan Penyedia Dalam Swakelola (PDS) per Satker</p>
            </div>
        </div>

        <x-datatable
            id="dt-rekap-satker"
            :action="route('report.pantau', ['jenis' => 'rup'])"
            :cari="$satkerFilters['cari']"
            cari-placeholder="Cari nama atau kode satuan kerja..."
            :per-halaman="$satkerFilters['per_halaman']"
            :per-halaman-opsi="[10, 25, 50, 100]"
            :halaman="$satkerFilters['halaman']"
            :total="$rekapSatker['total']"
            :sort="$satkerFilters['sort']"
            :arah="$satkerFilters['arah']"
        >
            <x-slot:filter>
                <input type="hidden" name="tabel" value="satker">
            </x-slot:filter>

            @include('report.partials.tabel-rekap-satker', [
                'baris' => $rekapSatker['baris'],
                'grandTotal' => $rekapSatker['grand_total'],
                'sort' => $satkerFilters['sort'],
                'arah' => $satkerFilters['arah'],
                'halaman' => $satkerFilters['halaman'],
                'perHalaman' => $satkerFilters['per_halaman'],
                'tab' => $tab,
            ])
        </x-datatable>
    </div>

    {{-- ================= BAGIAN 4: TABEL DAFTAR PAKET ================= --}}
    <div class="card" id="daftar-paket">
        <div class="card__head" style="border-bottom:none;padding-bottom:12px">
            <div style="flex:1">
                <h2>Daftar Paket RUP Terumumkan</h2>
                <p>Hanya menampilkan paket dengan status Terumumkan atau Sudah Diproses</p>
            </div>
        </div>

        <x-datatable
            id="dt-paket-rup"
            :action="route('report.pantau', ['jenis' => 'rup'])"
            :cari="$filters['cari']"
            cari-placeholder="Cari kode RUP / nama paket..."
            :per-halaman="$perHalaman"
            :per-halaman-opsi="[10, 25, 50, 100]"
            :halaman="$halaman"
            :total="$total"
            :sort="$filters['sort'] ?? 'kd_rup'"
            :arah="$filters['arah'] ?? 'desc'"
        >
            {{-- Tabs Pengalih: Penyedia & PDS vs Swakelola --}}
            <x-slot:tabs>
                <input type="hidden" name="tab" value="{{ $tab }}">
                <a
                    href="{{ route('report.pantau', ['jenis' => 'rup', 'tab' => 'penyedia']) }}#daftar-paket"
                    class="btn dt-tab-btn {{ $tab === 'penyedia' ? 'btn-primary' : 'btn-ghost' }} btn-sm"
                    data-tab="penyedia"
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
                    class="btn dt-tab-btn {{ $tab === 'swakelola' ? 'btn-primary' : 'btn-ghost' }} btn-sm"
                    data-tab="swakelola"
                    style="gap:6px"
                >
                    <x-ikon nama="layers" :ukuran="14" />
                    Swakelola
                    <span class="badge {{ $tab === 'swakelola' ? 'badge--brand' : 'badge--netral' }}">
                        {{ angka($ringkasan['swakelola']['total']['jumlah']) }}
                    </span>
                </a>
            </x-slot:tabs>

            {{-- Filter Kustom Toolbar --}}
            <x-slot:filter>
                <input type="hidden" name="tabel" value="paket">

                {{-- Filter Satker Combobox dengan Filter Pencarian & Multi-Select --}}
                <div class="field" style="display:flex;flex-direction:column;gap:4px;min-width:240px;max-width:300px">
                    <label style="font-size:11px;font-weight:600;color:var(--text-soft);text-transform:uppercase;letter-spacing:.3px">Satker</label>
                    <x-combobox
                        name="satker"
                        :options="$satkerPilihan"
                        :selected="$filters['satker']"
                        :multiple="true"
                        placeholder="Semua Satuan Kerja"
                        cari="Cari satuan kerja..."
                    />
                </div>

                {{-- Filter Khusus Tab Penyedia --}}
                <div data-tab-target="penyedia" style="display:{{ $tab === 'penyedia' ? 'contents' : 'none' }}">
                    <div class="field" style="display:flex;flex-direction:column;gap:4px">
                        <label style="font-size:11px;font-weight:600;color:var(--text-soft);text-transform:uppercase;letter-spacing:.3px">Tipe</label>
                        <select name="tipe_paket" class="input input-sm" style="height:32px">
                            <option value="">Semua Tipe</option>
                            <option value="penyedia" @selected(($filters['tipe_paket'] ?? '') === 'penyedia')>Penyedia (Murni)</option>
                            <option value="pds" @selected(($filters['tipe_paket'] ?? '') === 'pds')>PDS</option>
                        </select>
                    </div>

                    <div class="field" style="display:flex;flex-direction:column;gap:4px">
                        <label style="font-size:11px;font-weight:600;color:var(--text-soft);text-transform:uppercase;letter-spacing:.3px">PDN</label>
                        <select name="status_pdn" class="input input-sm" style="height:32px">
                            <option value="">Semua PDN</option>
                            <option value="PDN" @selected(($filters['status_pdn'] ?? '') === 'PDN')>PDN</option>
                            <option value="Non-PDN" @selected(($filters['status_pdn'] ?? '') === 'Non-PDN')>Non-PDN</option>
                        </select>
                    </div>

                    <div class="field" style="display:flex;flex-direction:column;gap:4px">
                        <label style="font-size:11px;font-weight:600;color:var(--text-soft);text-transform:uppercase;letter-spacing:.3px">Proses</label>
                        <select name="status_proses" class="input input-sm" style="height:32px">
                            <option value="">Semua Proses</option>
                            <option value="belum" @selected(($filters['status_proses'] ?? '') === 'belum')>Belum Diproses</option>
                            <option value="diproses" @selected(($filters['status_proses'] ?? '') === 'diproses')>Semua Diproses</option>
                            <option value="e-katalog" @selected(($filters['status_proses'] ?? '') === 'e-katalog')>E-Katalog</option>
                            <option value="tender" @selected(($filters['status_proses'] ?? '') === 'tender')>Tender</option>
                            <option value="non-tender" @selected(($filters['status_proses'] ?? '') === 'non-tender')>Non Tender</option>
                            <option value="pencatatan-non-tender" @selected(($filters['status_proses'] ?? '') === 'pencatatan-non-tender')>Pencatatan Nontender</option>
                            <option value="pencatatan-swakelola" @selected(($filters['status_proses'] ?? '') === 'pencatatan-swakelola')>Pencatatan Swakelola</option>
                            <option value="e-kontrak" @selected(($filters['status_proses'] ?? '') === 'e-kontrak')>E-Kontrak</option>
                        </select>
                    </div>

                    {{-- Filter Jenis Pengadaan Combobox dengan Filter & Multi-Select --}}
                    <div class="field" style="display:flex;flex-direction:column;gap:4px;min-width:180px;max-width:240px">
                        <label style="font-size:11px;font-weight:600;color:var(--text-soft);text-transform:uppercase;letter-spacing:.3px">Jenis Pengadaan</label>
                        <x-combobox
                            name="jenis_pengadaan"
                            :options="$jenisOpsi"
                            :selected="$filters['jenis_pengadaan']"
                            :multiple="true"
                            placeholder="Semua Jenis"
                            cari="Cari jenis..."
                        />
                    </div>
                </div>

                {{-- Filter Khusus Tab Swakelola --}}
                <div data-tab-target="swakelola" style="display:{{ $tab === 'swakelola' ? 'contents' : 'none' }}">
                    <div class="field" style="display:flex;flex-direction:column;gap:4px">
                        <label style="font-size:11px;font-weight:600;color:var(--text-soft);text-transform:uppercase;letter-spacing:.3px">Tipe Swakelola</label>
                        <select name="tipe_swakelola" class="input input-sm" style="height:32px">
                            <option value="">Semua Tipe Swakelola</option>
                            <option value="1" @selected(($filters['tipe_swakelola'] ?? '') == 1)>Tipe I</option>
                            <option value="2" @selected(($filters['tipe_swakelola'] ?? '') == 2)>Tipe II</option>
                            <option value="3" @selected(($filters['tipe_swakelola'] ?? '') == 3)>Tipe III</option>
                            <option value="4" @selected(($filters['tipe_swakelola'] ?? '') == 4)>Tipe IV</option>
                        </select>
                    </div>
                </div>
            </x-slot:filter>

            {{-- Initial Table Partial --}}
            @include('report.partials.tabel-paket-rup', [
                'tab' => $tab,
                'baris' => $baris,
                'sort' => $filters['sort'] ?? 'kd_rup',
                'arah' => $filters['arah'] ?? 'desc',
            ])
        </x-datatable>
    </div>

@endsection
