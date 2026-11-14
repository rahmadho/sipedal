@extends('layouts.app')

@section('judul', 'Satuan Kerja')

@section('konten')

    <div class="page-head">
        <div class="page-head__title">
            <h1>Satuan Kerja</h1>
            <p>
                Daftar satuan kerja pada <strong>{{ $namaKlpd ?? $tenant->nama() }}</strong> ({{ $kdKlpd }}) tahun anggaran <strong>{{ $tahun }}</strong> beserta pagu RUP, jumlah paket, dan realisasi serapannya.
            </p>
        </div>
        <div class="page-head__aksi">
            <span class="badge badge--brand" style="padding:6px 12px;font-size:12px">
                {{ $kdKlpd }} · TA {{ $tahun }}
            </span>
        </div>
    </div>

    <div class="grid grid-3" style="margin-bottom:16px">
        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__label">Total Satuan Kerja</span>
                <span class="kpi__ikon"><x-ikon nama="layers" :ukuran="17" /></span>
            </div>
            <div class="kpi__nilai">{{ angka($total) }}</div>
            <div class="kpi__sub">Total {{ angka($totalPaket ?? 0) }} paket RUP terdaftar</div>
        </div>
        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__label">Total Pagu RUP</span>
                <span class="kpi__ikon"><x-ikon nama="wallet" :ukuran="17" /></span>
            </div>
            <div class="kpi__nilai">{{ ringkas($totalPagu ?? 0) }}</div>
            <div class="kpi__sub">{{ rp($totalPagu ?? 0) }}</div>
        </div>
        <div class="kpi">
            <div class="kpi__top">
                <span class="kpi__label">Realisasi & Serapan</span>
                <span class="kpi__ikon"><x-ikon nama="trending" :ukuran="17" /></span>
            </div>
            <div class="kpi__nilai">{{ persen($rataSerapan ?? 0) }}</div>
            <div class="kpi__sub">Realisasi: {{ ringkas($totalRealisasi ?? 0) }} ({{ rp($totalRealisasi ?? 0) }})</div>
        </div>
    </div>

    <div class="card">
        <div class="card__head">
            <div>
                <h2>Daftar Satuan Kerja</h2>
                <p>Menampilkan {{ count($baris) }} dari {{ angka($total) }} satuan kerja</p>
            </div>
        </div>

        <form class="toolbar" method="GET" action="{{ route('master.satker') }}">
            <div class="field field--cari">
                <label for="cari">Cari satker</label>
                <div class="input-wrap">
                    <span class="input-ikon"><x-ikon nama="search" :ukuran="14" /></span>
                    <input class="input" type="search" id="cari" name="cari" placeholder="Nama atau kode satuan kerja…" value="{{ $cari ?? request('cari') }}">
                </div>
            </div>
            <div class="field field--md">
                <label for="jenis">Jenis</label>
                <select class="select js-combobox-asal" id="jenis" name="jenis">
                    <option value="">Semua jenis</option>
                    @foreach (($jenisOpsi ?? ['Dinas' => 'Dinas', 'Badan' => 'Badan', 'Sekretariat' => 'Sekretariat', 'RSUD' => 'RSUD']) as $val => $lbl)
                        <option value="{{ $val }}" @selected(($jenisTerpilih ?? request('jenis')) === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="toolbar__aksi">
                <button class="btn btn-primary btn-sm" type="submit">
                    <x-ikon nama="search" :ukuran="14" />
                    Terapkan
                </button>
                @if (!empty($cari) || !empty($jenisTerpilih))
                    <a class="btn btn-ghost btn-sm" href="{{ route('master.satker') }}">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        <div class="tabel-wrap">
            <table class="tabel">
                <caption class="sr-only">Daftar satuan kerja beserta pagu dan rasio serapan</caption>
                <thead>
                    <tr>
                        <th style="width:52px">No</th>
                        <th style="width:140px">Kode</th>
                        <th>Nama Satuan Kerja</th>
                        <th style="width:110px">Jenis</th>
                        <th class="num">Paket</th>
                        <th class="num">Pagu RUP</th>
                        <th class="num">Realisasi</th>
                        <th style="width:160px">Serapan</th>
                        <th style="width:56px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($baris as $b)
                        <tr>
                            <td class="text-mute tnum">{{ $b['no'] }}</td>
                            <td class="sel-kode">
                                <div class="fw-600">{{ $b['kd_satker'] }}</div>
                                @if (!empty($b['kd_satker_str']))
                                    <div class="text-xs text-mute mono" style="font-size:10.5px">{{ $b['kd_satker_str'] }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="sel-nama truncate" style="max-width:340px" title="{{ $b['nama'] }}">{{ $b['nama'] }}</div>
                                @if (!empty($b['alamat']))
                                    <div class="text-xs text-mute truncate" style="max-width:340px" title="{{ $b['alamat'] }}">{{ $b['alamat'] }}</div>
                                @endif
                            </td>
                            <td><span class="chip">{{ $b['jenis'] }}</span></td>
                            <td class="num">
                                <div class="fw-600">{{ angka($b['paket']) }}</div>
                                @if (isset($b['paket_penyedia']) || isset($b['paket_swakelola']))
                                    <div class="text-xs text-mute" title="Penyedia / Swakelola">{{ angka($b['paket_penyedia'] ?? 0) }}P / {{ angka($b['paket_swakelola'] ?? 0) }}S</div>
                                @endif
                            </td>
                            <td class="num">
                                <div class="fw-600">{{ ringkas($b['pagu']) }}</div>
                                <div class="text-xs text-mute">{{ rp($b['pagu']) }}</div>
                            </td>
                            <td class="num">
                                <div class="fw-600">{{ ringkas($b['realisasi']) }}</div>
                                <div class="text-xs text-mute">{{ rp($b['realisasi']) }}</div>
                            </td>
                            <td>
                                <div class="row gap-2">
                                    <div class="progres grow {{ $b['serapan'] < 30 ? 'progres--merah' : ($b['serapan'] < 60 ? 'progres--amber' : 'progres--hijau') }}">
                                        <div class="progres__isi" style="width:{{ min(100, $b['serapan']) }}%"></div>
                                    </div>
                                    <span class="text-xs fw-600 tnum" style="width:42px;text-align:right">{{ persen($b['serapan']) }}</span>
                                </div>
                            </td>
                            <td>
                                <button
                                    class="icon-btn"
                                    type="button"
                                    aria-label="Lihat detail satker {{ $b['nama'] }}"
                                    data-detail-satker="{{ json_encode($b) }}"
                                >
                                    <x-ikon nama="chevron-right" :ukuran="15" />
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center;padding:36px">
                                <div class="text-mute" style="font-size:13px">
                                    Tidak ada data satuan kerja yang cocok dengan kriteria pencarian.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-paginasi :halaman="$halaman" :per-halaman="$perHalaman" :total="$total" />
    </div>

    {{-- ================= MODAL DETAIL SATKER ================= --}}
    <div class="modal-latar" id="latar-detail-satker" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="judul-detail-satker">
            <div class="modal__head">
                <h2 id="judul-detail-satker">Detail Satuan Kerja</h2>
                <p id="sub-detail-satker" class="mono text-xs">—</p>
            </div>
            <div class="modal__body">
                <dl class="dl">
                    <dt>Kode Satker (INAPROC)</dt>
                    <dd class="mono fw-600" id="det-kd-satker">—</dd>

                    <dt>Kode Unit (SIPD/STR)</dt>
                    <dd class="mono text-xs" id="det-kd-str">—</dd>

                    <dt>Nama Satuan Kerja</dt>
                    <dd class="fw-600" id="det-nama">—</dd>

                    <dt>Jenis & Kategori</dt>
                    <dd id="det-jenis">—</dd>

                    <dt>Alamat</dt>
                    <dd id="det-alamat">—</dd>

                    <dt>Paket RUP</dt>
                    <dd id="det-paket">—</dd>

                    <dt>Total Pagu RUP</dt>
                    <dd class="fw-600" id="det-pagu">—</dd>

                    <dt>Total Realisasi</dt>
                    <dd class="fw-600" id="det-realisasi">—</dd>

                    <dt>Rasio Serapan</dt>
                    <dd class="fw-700" id="det-serapan">—</dd>
                </dl>
            </div>
            <div class="modal__foot">
                <button class="btn btn-primary" type="button" id="tutup-detail-satker">Tutup</button>
            </div>
        </div>
    </div>

@endsection

@push('skrip')
<script>
    (function () {
        var modal = document.getElementById('latar-detail-satker');
        var btnTutup = document.getElementById('tutup-detail-satker');

        function formatRupiah(angka) {
            return 'Rp ' + Number(angka || 0).toLocaleString('id-ID');
        }

        document.querySelectorAll('[data-detail-satker]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                try {
                    var d = JSON.parse(btn.getAttribute('data-detail-satker'));
                    document.getElementById('sub-detail-satker').textContent = d.nama;
                    document.getElementById('det-kd-satker').textContent = d.kd_satker || '—';
                    document.getElementById('det-kd-str').textContent = d.kd_satker_str || '—';
                    document.getElementById('det-nama').textContent = d.nama || '—';
                    document.getElementById('det-jenis').textContent = (d.jenis || '—') + (d.ket_satker ? ' · ' + d.ket_satker : '');
                    document.getElementById('det-alamat').textContent = d.alamat || '—';
                    document.getElementById('det-paket').textContent = Number(d.paket || 0).toLocaleString('id-ID') + ' Paket (' + Number(d.paket_penyedia || 0).toLocaleString('id-ID') + ' Penyedia, ' + Number(d.paket_swakelola || 0).toLocaleString('id-ID') + ' Swakelola)';
                    document.getElementById('det-pagu').textContent = formatRupiah(d.pagu);
                    document.getElementById('det-realisasi').textContent = formatRupiah(d.realisasi);
                    document.getElementById('det-serapan').textContent = Number(d.serapan || 0).toFixed(1).replace('.', ',') + '%';
                    modal.hidden = false;
                } catch (e) {}
            });
        });

        function tutup() { modal.hidden = true; }
        if (btnTutup) btnTutup.addEventListener('click', tutup);
        if (modal) modal.addEventListener('click', function (e) { if (e.target === modal) tutup(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && modal) tutup(); });
    })();
</script>
@endpush
