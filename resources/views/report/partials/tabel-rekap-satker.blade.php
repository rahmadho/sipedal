{{--
    Tabel Rekapitulasi RUP per Satuan Kerja Partial
    Grouped Thead:
    - Row 1: No (rowspan 2), Satuan Kerja (rowspan 2), Penyedia (colspan 2), Swakelola (colspan 2), Penyedia Dalam Swakelola (colspan 2), Total Paket (rowspan 2), Total Pagu (rowspan 2)
    - Row 2: Paket, Pagu (Penyedia) | Paket, Pagu (Swakelola) | Paket, Pagu (PDS)
--}}
@php
    $sort = $sort ?? 'total_pagu';
    $arah = $arah ?? 'desc';
    $halaman = $halaman ?? 1;
    $perHalaman = $perHalaman ?? 10;
    $offset = ($halaman - 1) * $perHalaman;
    $tab = $tab ?? 'penyedia';

    $indikator = function (string $kolom) use ($sort, $arah) {
        if ($sort !== $kolom) {
            return '<span class="sort-icon" style="opacity:.3;margin-left:4px;font-size:10px">↕</span>';
        }
        return $arah === 'asc'
            ? '<span class="sort-icon" style="color:var(--brand-700);margin-left:4px;font-weight:bold;font-size:11px">↑</span>'
            : '<span class="sort-icon" style="color:var(--brand-700);margin-left:4px;font-weight:bold;font-size:11px">↓</span>';
    };
@endphp

<div class="tabel-wrap">
    <table class="tabel" aria-label="Rekapitulasi RUP per Satuan Kerja">
        <thead>
            {{-- BARIS 1: Grup Header Kolom --}}
            <tr>
                <th rowspan="2" style="width:46px;text-align:center;vertical-align:middle" class="num">No</th>
                <th rowspan="2" style="vertical-align:middle;cursor:pointer;user-select:none;min-width:220px" class="th-sortable" data-sort="nama_satker" title="Urutkan Satuan Kerja">
                    <div style="display:inline-flex;align-items:center">
                        Satuan Kerja {!! $indikator('nama_satker') !!}
                    </div>
                </th>
                <th colspan="2" class="num" style="text-align:center;border-left:1px solid var(--border);background:var(--blue-50);color:var(--brand-900)">
                    Penyedia
                </th>
                <th colspan="2" class="num" style="text-align:center;border-left:1px solid var(--border);background:var(--green-50);color:var(--green-900)">
                    Swakelola
                </th>
                <th colspan="2" class="num" style="text-align:center;border-left:1px solid var(--border);background:var(--amber-50);color:var(--amber-900)">
                    Penyedia Dalam Swakelola
                </th>
                <th rowspan="2" class="num th-sortable" data-sort="total_paket" style="vertical-align:middle;text-align:right;border-left:1px solid var(--border);background:var(--gray-100);cursor:pointer;user-select:none;min-width:90px" title="Urutkan Total Paket">
                    <div style="display:inline-flex;align-items:center;justify-content:flex-end">
                        Total Paket {!! $indikator('total_paket') !!}
                    </div>
                </th>
                <th rowspan="2" class="num th-sortable" data-sort="total_pagu" style="vertical-align:middle;text-align:right;background:var(--gray-100);cursor:pointer;user-select:none;min-width:115px" title="Urutkan Total Pagu">
                    <div style="display:inline-flex;align-items:center;justify-content:flex-end">
                        Total Pagu {!! $indikator('total_pagu') !!}
                    </div>
                </th>
            </tr>

            {{-- BARIS 2: Detail Paket & Pagu di Bawah Masing-masing Grup --}}
            <tr>
                <th class="num th-sortable" data-sort="penyedia_paket" style="text-align:right;border-left:1px solid var(--border);background:var(--blue-50);cursor:pointer;user-select:none;font-size:11px" title="Urutkan Paket Penyedia">
                    <div style="display:inline-flex;align-items:center;justify-content:flex-end">
                        Paket {!! $indikator('penyedia_paket') !!}
                    </div>
                </th>
                <th class="num th-sortable" data-sort="penyedia_pagu" style="text-align:right;background:var(--blue-50);cursor:pointer;user-select:none;font-size:11px" title="Urutkan Pagu Penyedia">
                    <div style="display:inline-flex;align-items:center;justify-content:flex-end">
                        Pagu {!! $indikator('penyedia_pagu') !!}
                    </div>
                </th>

                <th class="num th-sortable" data-sort="swakelola_paket" style="text-align:right;border-left:1px solid var(--border);background:var(--green-50);cursor:pointer;user-select:none;font-size:11px" title="Urutkan Paket Swakelola">
                    <div style="display:inline-flex;align-items:center;justify-content:flex-end">
                        Paket {!! $indikator('swakelola_paket') !!}
                    </div>
                </th>
                <th class="num th-sortable" data-sort="swakelola_pagu" style="text-align:right;background:var(--green-50);cursor:pointer;user-select:none;font-size:11px" title="Urutkan Pagu Swakelola">
                    <div style="display:inline-flex;align-items:center;justify-content:flex-end">
                        Pagu {!! $indikator('swakelola_pagu') !!}
                    </div>
                </th>

                <th class="num th-sortable" data-sort="pds_paket" style="text-align:right;border-left:1px solid var(--border);background:var(--amber-50);cursor:pointer;user-select:none;font-size:11px" title="Urutkan Paket PDS">
                    <div style="display:inline-flex;align-items:center;justify-content:flex-end">
                        Paket {!! $indikator('pds_paket') !!}
                    </div>
                </th>
                <th class="num th-sortable" data-sort="pds_pagu" style="text-align:right;background:var(--amber-50);cursor:pointer;user-select:none;font-size:11px" title="Urutkan Pagu PDS">
                    <div style="display:inline-flex;align-items:center;justify-content:flex-end">
                        Pagu {!! $indikator('pds_pagu') !!}
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($baris as $idx => $s)
                <tr>
                    <td class="num" style="color:var(--text-mute);text-align:center">{{ $offset + $idx + 1 }}</td>
                    <td>
                        <a
                            href="{{ route('report.pantau', ['jenis' => 'rup', 'tab' => $tab, 'satker' => $s['kd_satker']]) }}#daftar-paket"
                            style="font-weight:600;color:var(--brand-700)"
                            title="Klik untuk memfilter daftar paket RUP satuan kerja ini"
                        >
                            {{ $s['nama_satker'] }}
                        </a>
                        @if (!empty($s['kd_satker_str']))
                            <div style="font-size:11px;color:var(--text-mute)">{{ $s['kd_satker_str'] }}</div>
                        @else
                            <div style="font-size:11px;color:var(--text-mute)">Kode: {{ $s['kd_satker'] }}</div>
                        @endif
                    </td>

                    {{-- Penyedia --}}
                    <td class="num" style="border-left:1px solid var(--border)">{{ angka($s['penyedia']['paket']) }}</td>
                    <td class="num" title="{{ rp($s['penyedia']['pagu']) }}">{{ ringkas($s['penyedia']['pagu']) }}</td>

                    {{-- Swakelola --}}
                    <td class="num" style="border-left:1px solid var(--border)">{{ angka($s['swakelola']['paket']) }}</td>
                    <td class="num" title="{{ rp($s['swakelola']['pagu']) }}">{{ ringkas($s['swakelola']['pagu']) }}</td>

                    {{-- PDS --}}
                    <td class="num" style="border-left:1px solid var(--border)">{{ angka($s['pds']['paket']) }}</td>
                    <td class="num" title="{{ rp($s['pds']['pagu']) }}">{{ ringkas($s['pds']['pagu']) }}</td>

                    {{-- Total Satker --}}
                    <td class="num fw-bold" style="border-left:1px solid var(--border);color:var(--brand-800);background:var(--gray-50)">
                        {{ angka($s['total']['paket']) }}
                    </td>
                    <td class="num fw-bold" style="color:var(--brand-800);background:var(--gray-50)" title="{{ rp($s['total']['pagu']) }}">
                        {{ ringkas($s['total']['pagu']) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align:center;padding:32px;color:var(--text-mute)">
                        Tidak ada data satuan kerja yang sesuai filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if (!empty($grandTotal))
            <tfoot style="background:var(--gray-100);font-weight:700">
                <tr>
                    <td colspan="2" style="text-align:right">Total Keseluruhan:</td>
                    <td class="num" style="border-left:1px solid var(--border)">{{ angka($grandTotal['penyedia']['paket']) }}</td>
                    <td class="num" title="{{ rp($grandTotal['penyedia']['pagu']) }}">{{ ringkas($grandTotal['penyedia']['pagu']) }}</td>
                    <td class="num" style="border-left:1px solid var(--border)">{{ angka($grandTotal['swakelola']['paket']) }}</td>
                    <td class="num" title="{{ rp($grandTotal['swakelola']['pagu']) }}">{{ ringkas($grandTotal['swakelola']['pagu']) }}</td>
                    <td class="num" style="border-left:1px solid var(--border)">{{ angka($grandTotal['pds']['paket']) }}</td>
                    <td class="num" title="{{ rp($grandTotal['pds']['pagu']) }}">{{ ringkas($grandTotal['pds']['pagu']) }}</td>
                    <td class="num" style="border-left:1px solid var(--border);color:var(--brand-800)">{{ angka($grandTotal['total']['paket']) }}</td>
                    <td class="num" style="color:var(--brand-800)" title="{{ rp($grandTotal['total']['pagu']) }}">{{ ringkas($grandTotal['total']['pagu']) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
