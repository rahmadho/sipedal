@extends('layouts.app')

@section('judul', 'Pengaturan')

@section('konten')

    <div class="page-head">
        <div class="page-head__title">
            <h1>Pengaturan</h1>
            <p>Konfigurasi aplikasi, tampilan, dan perilaku pengambilan data.</p>
        </div>
        <div class="page-head__aksi">
            <button class="btn btn-primary btn-sm" type="button">
                <x-ikon nama="check" :ukuran="15" />
                Simpan Perubahan
            </button>
        </div>
    </div>

    <div class="grid grid-1-2">
        {{-- Navigasi bagian --}}
        <div class="card" style="align-self:start">
            <div class="card__body" style="padding:10px">
                @php
                    $bagian = [
                        ['kunci' => 'umum', 'label' => 'Umum', 'ikon' => 'settings'],
                        ['kunci' => 'tampilan', 'label' => 'Tampilan', 'ikon' => 'palette'],
                        ['kunci' => 'scheduler', 'label' => 'Jadwal Otomatis (Scheduler)', 'ikon' => 'clock'],
                        ['kunci' => 'data', 'label' => 'Data & Sinkronisasi', 'ikon' => 'database'],
                        ['kunci' => 'domain', 'label' => 'Domain & KLPD', 'ikon' => 'domain'],
                    ];
                @endphp
                @foreach ($bagian as $i => $b)
                    <a
                        class="nav-item"
                        href="#{{ $b['kunci'] }}"
                        style="color:var(--text-soft);{{ $i === 0 ? 'background:var(--brand-50);color:var(--brand-700);font-weight:600' : '' }}"
                    >
                        <span class="nav-item__ikon"><x-ikon :nama="$b['ikon']" :ukuran="16" /></span>
                        <span class="nav-item__label">{{ $b['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="col gap-4">

            {{-- ============ UMUM ============ --}}
            <div class="card" id="umum">
                <div class="card__head">
                    <div>
                        <h2>Umum</h2>
                        <p>Identitas aplikasi dan instansi</p>
                    </div>
                </div>
                <div class="card__body">
                    <div class="grid grid-2 gap-4">
                        <div class="field">
                            <label for="nama-app">Nama Aplikasi</label>
                            <input class="input" id="nama-app" value="{{ $nilai['umum']['nama_aplikasi'] }}">
                        </div>
                        <div class="field">
                            <label for="nama-instansi">Nama Instansi</label>
                            <input class="input" id="nama-instansi" value="{{ $nilai['umum']['nama_instansi'] }}">
                        </div>
                        <div class="field">
                            <label for="tahun">Tahun Anggaran Aktif</label>
                            <select class="select js-combobox-asal" id="tahun">
                                @foreach ([2024, 2025, 2026, 2027] as $th)
                                    <option @selected($nilai['umum']['tahun_aktif'] === $th)>{{ $th }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="tz">Zona Waktu</label>
                            <select class="select js-combobox-asal" id="tz">
                                <option>{{ $nilai['umum']['zona_waktu'] }}</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="bahasa">Bahasa</label>
                            <select class="select js-combobox-asal" id="bahasa">
                                <option>Indonesia</option>
                                <option>English</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="format">Format Angka</label>
                            <select class="select js-combobox-asal" id="format">
                                <option>{{ $nilai['umum']['format_angka'] }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============ TAMPILAN ============ --}}
            <div class="card" id="tampilan">
                <div class="card__head">
                    <div>
                        <h2>Tampilan</h2>
                        <p>Skema warna dan perilaku antarmuka</p>
                    </div>
                    <div class="head-aksi">
                        <span class="badge badge--brand">{{ $tenant->labelTema() }}</span>
                    </div>
                </div>
                <div class="card__body">
                    <div class="label-bagian">Skema Warna KLPD</div>

                    @if (session('peran') === 'super_admin')
                        <p class="text-sm text-mute" style="margin-bottom:12px">
                            Anda dapat mengubah tema karena berperan Super Admin. Perubahan
                            berlaku untuk semua pengguna di domain ini.
                        </p>
                    @else
                        <div class="banner banner--info" style="margin-bottom:12px">
                            <span class="banner__ikon"><x-ikon nama="lock" :ukuran="15" /></span>
                            <div>Tema ditetapkan oleh Super Admin dan tidak dapat diubah dari akun Anda.</div>
                        </div>
                    @endif

                    <div class="tema-pilih">
                        @foreach ($tema as $kunci => $t)
                            @php $dipilih = $tenant->namaTema() === $kunci; @endphp
                            <button
                                class="tema-kartu {{ $dipilih ? 'is-dipilih' : '' }}"
                                type="button"
                                data-pilih-tema="{{ $kunci }}"
                                @if (session('peran') !== 'super_admin') disabled style="opacity:.6;cursor:not-allowed" @endif
                            >
                                <div class="tema-kartu__contoh">
                                    <span style="background:{{ $t['tokens']['--brand-600'] }}"></span>
                                    <span style="background:{{ $t['tokens']['--brand-400'] }}"></span>
                                    <span style="background:{{ $t['tokens']['--brand-200'] }}"></span>
                                    <span style="background:{{ $t['tokens']['--brand-50'] }}"></span>
                                </div>
                                <div class="tema-kartu__nama">{{ $t['nama'] }}</div>
                                <div class="tema-kartu__ket">{{ $t['keterangan'] }}</div>
                            </button>
                        @endforeach
                    </div>

                    <div class="pemisah"></div>

                    <div class="row-between wrap gap-3">
                        <div>
                            <div class="fw-600 text-sm">Ukuran halaman default</div>
                            <div class="text-xs text-mute">Jumlah baris per halaman pada tabel</div>
                        </div>
                        <select class="select js-combobox-asal" style="width:120px">
                            <option>25</option>
                            <option selected>50</option>
                            <option>100</option>
                        </select>
                    </div>

                    <div class="row-between wrap gap-3" style="margin-top:14px">
                        <div>
                            <div class="fw-600 text-sm">Animasi transisi latar login</div>
                            <div class="text-xs text-mute">Gambar bergantian dengan transisi halus</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" @checked($nilai['tampilan']['animasi_login'])>
                            <span class="switch__track"></span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- ============ SCHEDULER (JADWAL OTOMATIS) ============ --}}
            <form class="card" id="scheduler" method="POST" action="{{ route('setting.scheduler.simpan') }}">
                @csrf
                <div class="card__head">
                    <div>
                        <h2>Jadwal Tarik Data Otomatis (Scheduler)</h2>
                        <p>Atur jadwal eksekusi background queue untuk masing-masing job tanpa intervensi manual</p>
                    </div>
                    <div class="head-aksi">
                        <label class="switch" title="Aktifkan atau matikan penjadwal otomatis secara menyeluruh">
                            <input type="checkbox" name="scheduler_enabled" value="1" @checked($scheduler['enabled'] ?? false)>
                            <span class="switch__track"></span>
                            <span class="fw-600 text-sm">Master Scheduler {{ ($scheduler['enabled'] ?? false) ? 'ON' : 'OFF' }}</span>
                        </label>
                    </div>
                </div>

                <div class="card__body">
                    @if (session('status'))
                        <div class="banner banner--sukses" style="margin-bottom:16px">
                            <span class="banner__ikon"><x-ikon nama="check" :ukuran="16" /></span>
                            <div>{{ session('status') }}</div>
                        </div>
                    @endif
                    @if (session('galat'))
                        <div class="banner banner--bahaya" style="margin-bottom:16px">
                            <span class="banner__ikon"><x-ikon nama="alert" :ukuran="16" /></span>
                            <div>{{ session('galat') }}</div>
                        </div>
                    @endif

                    <div class="banner banner--info" style="margin-bottom:20px">
                        <span class="banner__ikon"><x-ikon nama="clock" :ukuran="16" /></span>
                        <div>
                            Format jam menggunakan format 24 jam (<strong>HH:MM</strong> WIB). Pisahkan dengan tanda koma (<strong>,</strong>) jika ingin menjalankan lebih dari satu kali sehari.
                            <br>Contoh: <code>04:00, 16:00</code> (jalan setiap pukul 04:00 pagi dan 16:00 sore WIB).
                        </div>
                    </div>

                    <div class="col gap-4">
                        @foreach ($scheduler['jobs'] as $kunciJob => $job)
                            <div class="card" style="border:1px solid var(--border);background:var(--surface)">
                                <div class="card__body" style="padding:14px">
                                    <div class="row-between wrap gap-2" style="margin-bottom:10px">
                                        <div>
                                            <div class="fw-600 text-sm">{{ $job['label'] }}</div>
                                            <div class="text-xs text-mute">{{ $job['desc'] }}</div>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="jobs[{{ $kunciJob }}][enabled]" value="1" @checked($job['enabled'])>
                                            <span class="switch__track"></span>
                                            <span class="text-xs fw-500">{{ $job['enabled'] ? 'Aktif' : 'Nonaktif' }}</span>
                                        </label>
                                    </div>

                                    <div class="grid grid-2 gap-3" style="margin-top:12px;padding-top:12px;border-top:1px dashed var(--border)">
                                        <div class="field">
                                            <label for="time-{{ $kunciJob }}">Jadwal Jam Eksekusi (WIB)</label>
                                            <input class="input mono" id="time-{{ $kunciJob }}" name="jobs[{{ $kunciJob }}][times]"
                                                   value="{{ implode(', ', $job['times']) }}" placeholder="04:00, 16:00">
                                            <span class="field__hint">Jam eksekusi per hari (HH:MM, pisahkan koma)</span>
                                        </div>
                                        <div class="field">
                                            <label>Perintah Artisan Terkait</label>
                                            <input class="input mono" value="php artisan {{ $job['command'] }}" disabled style="background:var(--gray-50)">
                                            <span class="field__hint">Dijalankan otomatis oleh sistem</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="row" style="justify-content:flex-end;margin-top:20px">
                        <button class="btn btn-primary" type="submit">
                            <x-ikon nama="check" :ukuran="15" />
                            Simpan Pengaturan Scheduler
                        </button>
                    </div>
                </div>
            </form>

            {{-- ============ DATA ============ --}}
            <div class="card" id="data">
                <div class="card__head">
                    <div>
                        <h2>Data &amp; Sinkronisasi</h2>
                        <p>Ambang kesegaran dan batas ekspor</p>
                    </div>
                </div>
                <div class="card__body">
                    <div class="grid grid-2 gap-4">
                        <div class="field">
                            <label for="basi">Ambang data basi (jam)</label>
                            <input class="input" id="basi" type="number" value="{{ $nilai['data']['ambang_basi_jam'] }}">
                            <span class="field__hint">Peringatan muncul bila data lebih tua dari nilai ini</span>
                        </div>
                        <div class="field">
                            <label for="ekspor">Batas baris ekspor</label>
                            <input class="input" id="ekspor" type="number" value="{{ $nilai['data']['batas_ekspor'] }}">
                            <span class="field__hint">Ekspor di atas batas akan ditolak</span>
                        </div>
                        <div class="field">
                            <label for="jadwal">Jadwal refresh angka</label>
                            <input class="input" id="jadwal" value="{{ $nilai['data']['jadwal_refresh'] }}">
                            <span class="field__hint">Setelah pipeline selesai menarik data</span>
                        </div>
                        <div class="field">
                            <label for="auto">Refresh otomatis</label>
                            <div class="row gap-2" style="height:38px">
                                <label class="switch">
                                    <input type="checkbox" @checked($nilai['data']['refresh_otomatis'])>
                                    <span class="switch__track"></span>
                                </label>
                                <span class="text-sm text-soft">Aktif</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============ DOMAIN ============ --}}
            <div class="card" id="domain">
                <div class="card__head">
                    <div>
                        <h2>Domain &amp; KLPD</h2>
                        <p>Setiap KLPD memiliki domain dan identitas visual sendiri</p>
                    </div>
                </div>
                <div class="card__body card__body--flush">
                    <div class="tabel-wrap">
                        <table class="tabel">
                            <caption class="sr-only">Daftar domain KLPD dan temanya</caption>
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Domain</th>
                                    <th>Tema</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($daftarTenant as $kunci => $t)
                                    @php $temaT = config('sipedal.tema.' . $t['tema'], config('sipedal.tema.biru')); @endphp
                                    <tr>
                                        <td class="sel-kode">{{ $t['kode'] }}</td>
                                        <td class="mono text-xs">{{ $t['domain'] }}</td>
                                        <td>
                                            <span class="row gap-2 text-sm">
                                                <span class="legend__warna" style="background:{{ $temaT['tokens']['--brand-600'] }};width:10px;height:10px"></span>
                                                {{ $temaT['nama'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge--hijau"><span class="badge__dot"></span> Aktif</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card__foot" style="justify-content:flex-end">
                    <a class="btn btn-ghost btn-sm" href="{{ route('master.klpd') }}">
                        Kelola KLPD
                        <x-ikon nama="arrow-right" :ukuran="13" />
                    </a>
                </div>
            </div>

        </div>
    </div>

@endsection

@push('skrip')
<script>
    // Pratinjau tema langsung di halaman ini (tanpa menyimpan).
    (function () {
        var token = @json(collect($tema)->map(fn ($t) => $t['tokens'])->all());
        var kartu = Array.prototype.slice.call(document.querySelectorAll('[data-pilih-tema]'));

        kartu.forEach(function (k) {
            k.addEventListener('click', function () {
                if (k.disabled) return;
                var tema = k.dataset.pilihTema;
                kartu.forEach(function (x) { x.classList.toggle('is-dipilih', x === k); });

                if (token[tema]) {
                    Object.keys(token[tema]).forEach(function (nama) {
                        document.documentElement.style.setProperty(nama, token[tema][nama]);
                    });
                }
            });
        });
    })();
</script>
@endpush
