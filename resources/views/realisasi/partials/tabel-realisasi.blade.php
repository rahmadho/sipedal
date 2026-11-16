@php
    $sort = $sort ?? 'kd_rup';
    $arah = $arah ?? 'desc';

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
    <table class="tabel tabel--kunci" aria-label="Daftar Realisasi RUP">
        <thead>
            <tr>
                <th style="width:48px;text-align:center" class="num">No</th>
                <th style="width:110px;cursor:pointer;user-select:none" class="th-sortable" data-sort="kd_rup" title="Urutkan Kode RUP">
                    <div style="display:inline-flex;align-items:center">
                        Kode RUP {!! $indikator('kd_rup') !!}
                    </div>
                </th>
                <th style="cursor:pointer;user-select:none;min-width:260px" class="th-sortable" data-sort="nama_paket" title="Urutkan Nama Paket">
                    <div style="display:inline-flex;align-items:center">
                        Nama Paket {!! $indikator('nama_paket') !!}
                    </div>
                </th>
                <th style="width:200px;cursor:pointer;user-select:none" class="th-sortable" data-sort="nama_satker" title="Urutkan Satuan Kerja">
                    <div style="display:inline-flex;align-items:center">
                        Satuan Kerja {!! $indikator('nama_satker') !!}
                    </div>
                </th>
                <th style="width:130px;cursor:pointer;user-select:none" class="th-sortable" data-sort="sumber" title="Urutkan Sumber Realisasi">
                    <div style="display:inline-flex;align-items:center">
                        Sumber {!! $indikator('sumber') !!}
                    </div>
                </th>
                <th class="num th-sortable" data-sort="pagu" style="width:140px;text-align:right;cursor:pointer;user-select:none" title="Urutkan Pagu">
                    <div style="display:inline-flex;align-items:center;justify-content:flex-end">
                        Pagu {!! $indikator('pagu') !!}
                    </div>
                </th>
                <th style="width:100px;text-align:center;cursor:pointer;user-select:none" class="th-sortable" data-sort="jenis" title="Urutkan Jenis">
                    <div style="display:inline-flex;align-items:center;justify-content:center">
                        Jenis {!! $indikator('jenis') !!}
                    </div>
                </th>
                <th style="width:48px;text-align:center"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($baris as $b)
                <tr>
                    <td class="text-mute tnum" style="text-align:center">{{ $b['no'] }}</td>
                    <td class="sel-kode fw-600">{{ $b['kd_rup'] }}</td>
                    <td>
                        <div class="sel-nama truncate" style="max-width:380px;font-weight:600" title="{{ $b['nama_paket'] }}">
                            {{ $b['nama_paket'] }}
                        </div>
                        @if ($b['metode'] && $b['metode'] !== '-')
                            <div class="sel-sub text-xs text-mute" style="margin-top:2px">{{ $b['metode'] }}</div>
                        @endif
                    </td>
                    <td class="truncate" style="max-width:210px" title="{{ $b['satker'] }}">
                        <div style="font-weight:500">{{ $b['satker'] }}</div>
                        @if ($b['kd_satker'])
                            <div class="text-xs text-mute">Kode: {{ $b['kd_satker'] }}</div>
                        @endif
                    </td>
                    <td>
                        @php
                            $badgeClass = match ($b['sumber']) {
                                'e-katalog' => 'chip--brand',
                                'tender' => 'chip--info',
                                'non-tender' => 'chip--warning',
                                default => '',
                            };
                        @endphp
                        <span class="chip {{ $badgeClass }}" style="font-weight:600">
                            {{ \App\Support\RealisasiRepository::labelSumber($b['sumber']) }}
                        </span>
                    </td>
                    <td class="num">
                        <div class="fw-600">{{ rp($b['pagu']) }}</div>
                        <div class="text-xs text-mute">{{ ringkas($b['pagu']) }}</div>
                    </td>
                    <td style="text-align:center">
                        @if ($b['jenis'] === 'warisan')
                            <span class="badge badge--amber" title="Diwarisi dari kode {{ $b['dari_rup'] }} pada kedalaman {{ $b['depth'] }}">
                                <x-ikon nama="layers" :ukuran="11" />
                                W{{ $b['depth'] }}
                            </span>
                        @else
                            <span class="badge badge--hijau">
                                <span class="badge__dot"></span>
                                Langsung
                            </span>
                        @endif
                    </td>
                    <td style="text-align:center">
                        @if ($b['jenis'] === 'warisan' || !empty($b['dari_rup']))
                            <button
                                class="icon-btn"
                                type="button"
                                aria-label="Lihat rantai warisan {{ $b['kd_rup'] }}"
                                data-buka-rantai
                                data-kode="{{ $b['kd_rup'] }}"
                                data-dari="{{ $b['dari_rup'] ?? '' }}"
                                data-depth="{{ $b['depth'] }}"
                                data-paket="{{ $b['nama_paket'] }}"
                                title="Lihat rantai warisan kaji ulang"
                            >
                                <x-ikon nama="chevron-right" :ukuran="15" />
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:32px;color:var(--text-mute)">
                        Tidak ada data realisasi RUP yang sesuai kriteria pencarian.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
