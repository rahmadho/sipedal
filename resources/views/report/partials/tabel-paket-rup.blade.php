{{--
    Tabel Paket RUP Serverside Partial (Penyedia / Swakelola)
--}}
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

@if ($tab === 'penyedia')
    <div class="tabel-wrap">
        <table class="tabel" aria-label="Daftar Paket RUP Penyedia dan PDS">
            <thead>
                <tr>
                    <th style="width:44px" class="num">No</th>
                    <th style="width:110px;cursor:pointer;user-select:none" class="th-sortable" data-sort="kd_rup" title="Urutkan Kode RUP">
                        <div style="display:inline-flex;align-items:center">Kode RUP {!! $indikator('kd_rup') !!}</div>
                    </th>
                    <th style="cursor:pointer;user-select:none" class="th-sortable" data-sort="nama_paket" title="Urutkan Nama Paket">
                        <div style="display:inline-flex;align-items:center">Nama Paket {!! $indikator('nama_paket') !!}</div>
                    </th>
                    <th style="cursor:pointer;user-select:none" class="th-sortable" data-sort="nama_satker" title="Urutkan Satker">
                        <div style="display:inline-flex;align-items:center">Satuan Kerja {!! $indikator('nama_satker') !!}</div>
                    </th>
                    <th style="cursor:pointer;user-select:none" class="th-sortable" data-sort="tipe_paket" title="Urutkan Tipe Paket">
                        <div style="display:inline-flex;align-items:center">Tipe {!! $indikator('tipe_paket') !!}</div>
                    </th>
                    <th style="cursor:pointer;user-select:none" class="th-sortable" data-sort="jenis_pengadaan" title="Urutkan Jenis Pengadaan">
                        <div style="display:inline-flex;align-items:center">Jenis Pengadaan {!! $indikator('jenis_pengadaan') !!}</div>
                    </th>
                    <th style="cursor:pointer;user-select:none" class="th-sortable" data-sort="status_pdn" title="Urutkan Status PDN">
                        <div style="display:inline-flex;align-items:center">PDN {!! $indikator('status_pdn') !!}</div>
                    </th>
                    <th>Nama Proses</th>
                    <th class="num" style="cursor:pointer;user-select:none" class="th-sortable" data-sort="pagu" title="Urutkan Pagu Anggaran">
                        <div style="display:inline-flex;align-items:center;justify-content:flex-end">Pagu {!! $indikator('pagu') !!}</div>
                    </th>
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
                            <span class="badge {{ $p['badge_proses'] }}" title="Proses pengadaan: {{ $p['nama_proses'] }}">
                                <span class="badge__dot"></span> {{ $p['nama_proses'] }}
                            </span>
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
    <div class="tabel-wrap">
        <table class="tabel" aria-label="Daftar Paket RUP Swakelola">
            <thead>
                <tr>
                    <th style="width:44px" class="num">No</th>
                    <th style="width:110px;cursor:pointer;user-select:none" class="th-sortable" data-sort="kd_rup" title="Urutkan Kode RUP">
                        <div style="display:inline-flex;align-items:center">Kode RUP {!! $indikator('kd_rup') !!}</div>
                    </th>
                    <th style="cursor:pointer;user-select:none" class="th-sortable" data-sort="nama_paket" title="Urutkan Nama Paket">
                        <div style="display:inline-flex;align-items:center">Nama Paket {!! $indikator('nama_paket') !!}</div>
                    </th>
                    <th style="cursor:pointer;user-select:none" class="th-sortable" data-sort="nama_satker" title="Urutkan Satker">
                        <div style="display:inline-flex;align-items:center">Satuan Kerja {!! $indikator('nama_satker') !!}</div>
                    </th>
                    <th style="cursor:pointer;user-select:none" class="th-sortable" data-sort="tipe_swakelola" title="Urutkan Tipe Swakelola">
                        <div style="display:inline-flex;align-items:center">Tipe Swakelola {!! $indikator('tipe_swakelola') !!}</div>
                    </th>
                    <th>Penyelenggara</th>
                    <th class="num" style="cursor:pointer;user-select:none" class="th-sortable" data-sort="pagu" title="Urutkan Pagu">
                        <div style="display:inline-flex;align-items:center;justify-content:flex-end">Pagu {!! $indikator('pagu') !!}</div>
                    </th>
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
