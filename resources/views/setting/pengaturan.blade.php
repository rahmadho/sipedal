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

            @if (session('status'))
                <div class="banner banner--sukses">
                    <span class="banner__ikon"><x-ikon nama="check" :ukuran="16" /></span>
                    <div>{{ session('status') }}</div>
                </div>
            @endif
            @if (session('galat'))
                <div class="banner banner--bahaya">
                    <span class="banner__ikon"><x-ikon nama="alert" :ukuran="16" /></span>
                    <div>{{ session('galat') }}</div>
                </div>
            @endif

            <div class="banner banner--info" style="margin:0">
                <span class="banner__ikon"><x-ikon nama="info" :ukuran="16" /></span>
                <div>
                    Konteks pengaturan saat ini untuk: <strong>{{ $kdKlpdAktif }} — {{ $namaKlpdAktif }}</strong>.
                    Perubahan nilai disimpan secara persisten ke database (schema <code>public.app_settings</code>).
                </div>
            </div>

            {{-- ============ UMUM ============ --}}
            <form class="card" id="umum" method="POST" action="{{ route('setting.pengaturan.umum') }}">
                @csrf
                <div class="card__head">
                    <div>
                        <h2>Umum</h2>
                        <p>Identitas aplikasi, instansi, dan preferensi bahasa</p>
                    </div>
                </div>
                <div class="card__body">
                    <div class="grid grid-2 gap-4">
                        <div class="field">
                            <label for="nama-app">Nama Aplikasi</label>
                            <input class="input" id="nama-app" name="nama_aplikasi" value="{{ $nilai['umum']['nama_aplikasi'] }}" required>
                        </div>
                        <div class="field">
                            <label for="nama-instansi">Nama Instansi</label>
                            <input class="input" id="nama-instansi" name="nama_instansi" value="{{ $nilai['umum']['nama_instansi'] }}" required>
                        </div>
                        <div class="field">
                            <label for="tahun">Tahun Anggaran Aktif</label>
                            <select class="select js-combobox-asal" id="tahun" name="tahun_aktif">
                                @foreach ([2023, 2024, 2025, 2026, 2027] as $th)
                                    <option value="{{ $th }}" @selected($nilai['umum']['tahun_aktif'] === $th)>{{ $th }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="tz">Zona Waktu</label>
                            <select class="select js-combobox-asal" id="tz" name="zona_waktu">
                                @foreach (['Asia/Jakarta' => 'Asia/Jakarta (WIB)', 'Asia/Makassar' => 'Asia/Makassar (WITA)', 'Asia/Jayapura' => 'Asia/Jayapura (WIT)'] as $tzVal => $tzLabel)
                                    <option value="{{ $tzVal }}" @selected($nilai['umum']['zona_waktu'] === $tzVal)>{{ $tzLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="bahasa">Bahasa (Language)</label>
                            <select class="select js-combobox-asal" id="bahasa" name="bahasa">
                                <option value="id" @selected($nilai['umum']['bahasa'] === 'id')>Indonesia (Bahasa Indonesia)</option>
                                <option value="en" @selected($nilai['umum']['bahasa'] === 'en')>English (Inggris)</option>
                            </select>
                            <span class="field__hint">Pilihan bahasa disimpan di sesi dan database</span>
                        </div>
                        <div class="field">
                            <label for="format">Format Angka</label>
                            <select class="select js-combobox-asal" id="format" name="format_angka">
                                <option value="Indonesia (1.234.567,89)" @selected($nilai['umum']['format_angka'] === 'Indonesia (1.234.567,89)')>Indonesia (1.234.567,89)</option>
                                <option value="Standar (1,234,567.89)" @selected($nilai['umum']['format_angka'] === 'Standar (1,234,567.89)')>Standar (1,234,567.89)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row" style="justify-content:flex-end;margin-top:20px">
                        <button class="btn btn-primary btn-sm" type="submit">
                            <x-ikon nama="check" :ukuran="14" />
                            Simpan Pengaturan Umum
                        </button>
                    </div>
                </div>
            </form>

            {{-- ============ TAMPILAN ============ --}}
            <form class="card" id="tampilan" method="POST" action="{{ route('setting.pengaturan.tampilan') }}">
                @csrf
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
                            berlaku untuk semua pengguna pada konteks wilayah {{ $kdKlpdAktif }}.
                        </p>
                    @else
                        <div class="banner banner--info" style="margin-bottom:12px">
                            <span class="banner__ikon"><x-ikon nama="lock" :ukuran="15" /></span>
                            <div>Tema ditetapkan oleh Super Admin dan tidak dapat diubah dari akun Anda.</div>
                        </div>
                    @endif

                    <input type="hidden" name="tema_aktif" id="input-tema-aktif" value="{{ $nilai['tampilan']['tema_aktif'] }}">

                    <div class="tema-pilih">
                        @foreach ($tema as $kunci => $t)
                            @php $dipilih = ($nilai['tampilan']['tema_aktif'] ?? $tenant->namaTema()) === $kunci; @endphp
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
                        <select class="select js-combobox-asal" name="ukuran_halaman" style="width:120px">
                            <option value="25" @selected($nilai['tampilan']['ukuran_halaman'] === 25)>25</option>
                            <option value="50" @selected($nilai['tampilan']['ukuran_halaman'] === 50)>50</option>
                            <option value="100" @selected($nilai['tampilan']['ukuran_halaman'] === 100)>100</option>
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

                    <div class="row" style="justify-content:flex-end;margin-top:20px">
                        <button class="btn btn-primary btn-sm" type="submit">
                            <x-ikon nama="check" :ukuran="14" />
                            Simpan Tampilan
                        </button>
                    </div>
                </div>
            </form>

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
            <form class="card" id="data" method="POST" action="{{ route('setting.pengaturan.data') }}">
                @csrf
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
                            <input class="input" id="basi" name="ambang_basi_jam" type="number" value="{{ $nilai['data']['ambang_basi_jam'] }}" required min="1">
                            <span class="field__hint">Peringatan muncul bila data lebih tua dari nilai ini</span>
                        </div>
                        <div class="field">
                            <label for="ekspor">Batas baris ekspor</label>
                            <input class="input" id="ekspor" name="batas_ekspor" type="number" value="{{ $nilai['data']['batas_ekspor'] }}" required min="100">
                            <span class="field__hint">Ekspor di atas batas akan ditolak</span>
                        </div>
                        <div class="field">
                            <label for="jadwal">Jadwal refresh angka</label>
                            <input class="input" id="jadwal" value="{{ $nilai['data']['jadwal_refresh'] }}" disabled style="background:var(--gray-50)">
                            <span class="field__hint">Dikelola otomatis pada tab Scheduler di atas</span>
                        </div>
                        <div class="field">
                            <label for="auto">Refresh otomatis</label>
                            <div class="row gap-2" style="height:38px">
                                <label class="switch">
                                    <input type="checkbox" @checked($nilai['data']['refresh_otomatis']) disabled>
                                    <span class="switch__track"></span>
                                </label>
                                <span class="text-sm text-soft">Aktif (Scheduler)</span>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="justify-content:flex-end;margin-top:20px">
                        <button class="btn btn-primary btn-sm" type="submit">
                            <x-ikon nama="check" :ukuran="14" />
                            Simpan Pengaturan Data
                        </button>
                    </div>
                </div>
            </form>

            {{-- ============ DOMAIN ============ --}}
            <div class="card" id="domain">
                <div class="card__head">
                    <div>
                        <h2>Domain &amp; Wilayah KLPD</h2>
                        <p>Daftar 20 wilayah KLPD Sumatera Barat dan status aktifnya</p>
                    </div>
                </div>
                <div class="card__body card__body--flush">
                    <div class="tabel-wrap">
                        <table class="tabel">
                            <caption class="sr-only">Daftar domain KLPD dan temanya</caption>
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Instansi / Pemda</th>
                                    <th>Status Konteks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (\App\Support\KlpdList::semua() as $klpdItem)
                                    @php $isAktifSesi = ($sesiKdKlpd === $klpdItem['kode']); @endphp
                                    <tr style="{{ $isAktifSesi ? 'background:var(--brand-50)' : '' }}">
                                        <td class="sel-kode fw-600">{{ $klpdItem['kode'] }}</td>
                                        <td class="text-sm">
                                            <strong>{{ $klpdItem['nama'] }}</strong>
                                        </td>
                                        <td>
                                            @if ($isAktifSesi)
                                                <span class="badge badge--hijau"><span class="badge__dot"></span> Sedang Aktif</span>
                                            @else
                                                <span class="text-xs text-mute">Tersedia</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card__foot" style="justify-content:flex-end">
                    <a class="btn btn-ghost btn-sm" href="{{ route('master.klpd') }}">
                        Kelola Master KLPD
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

                var inputTema = document.getElementById('input-tema-aktif');
                if (inputTema) inputTema.value = tema;

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
