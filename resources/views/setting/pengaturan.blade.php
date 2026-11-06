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
                            <div class="text-xs text-mute">Gambar bergantian dengan transisi halus secara otomatis</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="animasi_login" value="1" @checked($nilai['tampilan']['animasi_login'])>
                            <span class="switch__track"></span>
                        </label>
                    </div>

                    <div class="pemisah" style="margin:16px 0"></div>

                    <div>
                        <div class="fw-600 text-sm" style="margin-bottom:4px">Gambar Latar Login (Maksimal 5 Gambar)</div>
                        <div class="text-xs text-mute" style="margin-bottom:12px">
                            Gunakan tombol <strong>Pilih Media</strong> untuk mengunggah berkas atau memasukkan URL gambar.
                        </div>

                        <div class="col gap-3" id="wadah-latar-gambar">
                            @php
                                $latarGambar = $nilai['tampilan']['latar_gambar'] ?? [];
                                while (count($latarGambar) < 5) {
                                    $latarGambar[] = '';
                                }
                            @endphp
                            @foreach (array_slice($latarGambar, 0, 5) as $idx => $bgItem)
                                <div class="row gap-2" style="align-items:center">
                                    <span class="badge badge--netral" style="width:28px;height:34px;justify-content:center">{{ $idx + 1 }}</span>
                                    <div class="input-wrap" style="flex:1">
                                        <input class="input input--sm mono" name="latar_gambar[]" id="input-bg-{{ $idx }}"
                                               value="{{ $bgItem }}"
                                               placeholder="Pilih atau masukkan tautan gambar…"
                                               style="font-size:12px;height:34px">
                                    </div>
                                    <button class="btn btn-ghost btn-sm" type="button" data-buka-media="{{ $idx }}" style="height:34px" title="Buka Media Manager">
                                        <x-ikon nama="image" :ukuran="14" />
                                        Pilih Media
                                    </button>
                                    <button class="btn btn-ghost btn-sm" type="button" data-bersihkan-media="{{ $idx }}" style="height:34px;color:var(--text-mute)" title="Kosongkan">
                                        <x-ikon nama="x" :ukuran="14" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
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

    {{-- ================= MODAL MEDIA MANAGER ================= --}}
    <div class="modal-latar" id="latar-media" hidden>
        <div class="modal modal--lebar" role="dialog" aria-modal="true" aria-labelledby="judul-media">
            <div class="modal__head">
                <div class="row-between wrap gap-2">
                    <div>
                        <h2 id="judul-media">Media Manager</h2>
                        <p>Pilih dari pustaka, unggah berkas lokal, atau masukkan tautan URL luar</p>
                    </div>
                    <button class="btn btn-ghost btn-sm" type="button" id="tutup-media-btn" style="color:var(--text-mute)">
                        <x-ikon nama="x" :ukuran="16" />
                    </button>
                </div>
            </div>

            <div class="modal__body" style="padding-top:10px">
                <div class="media-tabs" role="tablist">
                    <button class="media-tab is-active" type="button" data-tab-target="tab-pustaka">
                        Pustaka Media
                    </button>
                    <button class="media-tab" type="button" data-tab-target="tab-unggah">
                        Unggah Berkas
                    </button>
                    <button class="media-tab" type="button" data-tab-target="tab-url">
                        Masukkan dari URL
                    </button>
                </div>

                {{-- Tab 1: Pustaka Media --}}
                <div id="tab-pustaka" class="media-tab-panel">
                    <div class="row-between" style="margin-bottom:12px;align-items:center">
                        <span class="text-xs text-mute" id="media-status-text">Memuat berkas media…</span>
                        <button class="btn btn-ghost btn-sm" type="button" id="media-segarkan-btn">
                            <x-ikon nama="refresh" :ukuran="13" /> Segarkan
                        </button>
                    </div>
                    <div class="media-grid" id="media-galeri">
                        {{-- Dimuat via fetch javascript --}}
                    </div>
                </div>

                {{-- Tab 2: Unggah Berkas --}}
                <div id="tab-unggah" class="media-tab-panel" hidden>
                    <div class="unggah" id="area-drop-unggah" style="cursor:pointer">
                        <input type="file" id="input-berkas-unggah" accept="image/png,image/jpeg,image/webp,image/svg+xml" style="display:none">
                        <div class="unggah__ikon"><x-ikon nama="upload" :ukuran="32" /></div>
                        <p><strong>Klik untuk memilih gambar</strong> atau seret berkas ke sini</p>
                        <small>Mendukung format SVG, PNG, JPG, WEBP (Maksimal 4 MB)</small>
                    </div>
                    <div id="status-unggah" style="margin-top:12px;font-size:12px"></div>
                </div>

                {{-- Tab 3: Masukkan dari URL --}}
                <div id="tab-url" class="media-tab-panel" hidden>
                    <div class="field" style="margin-bottom:12px">
                        <label for="input-url-eksternal">Tautan URL Gambar (HTTPS)</label>
                        <div class="row gap-2">
                            <input class="input" id="input-url-eksternal" placeholder="https://example.com/gambar.jpg">
                            <button class="btn btn-primary btn-sm" type="button" id="btn-pratinjau-url">
                                Pratinjau
                            </button>
                        </div>
                        <span class="field__hint">Pastikan URL dapat diakses publik secara aman</span>
                    </div>

                    <div id="wadah-pratinjau-url" style="text-align:center;padding:16px;border:1px dashed var(--border);border-radius:var(--radius);background:var(--gray-50);display:none">
                        <img id="img-pratinjau-url" src="" alt="Pratinjau" style="max-height:160px;max-width:100%;border-radius:var(--radius)">
                    </div>
                </div>
            </div>

            <div class="modal__foot" style="border-top:1px solid var(--border);padding-top:14px">
                <button class="btn btn-ghost btn-sm" type="button" id="batal-media-btn">Batal</button>
                <button class="btn btn-primary btn-sm" type="button" id="pilih-media-btn" disabled>
                    <x-ikon nama="check" :ukuran="14" />
                    Gunakan Gambar Ini
                </button>
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

    // ---------- Logika Media Manager ----------
    (function () {
        var latarModal = document.getElementById('latar-media');
        var tutupBtn = document.getElementById('tutup-media-btn');
        var batalBtn = document.getElementById('batal-media-btn');
        var pilihBtn = document.getElementById('pilih-media-btn');
        var galeri = document.getElementById('media-galeri');
        var statusText = document.getElementById('media-status-text');
        var segarkanBtn = document.getElementById('media-segarkan-btn');

        var areaDrop = document.getElementById('area-drop-unggah');
        var inputBerkas = document.getElementById('input-berkas-unggah');
        var statusUnggah = document.getElementById('status-unggah');

        var inputUrl = document.getElementById('input-url-eksternal');
        var btnPratinjauUrl = document.getElementById('btn-pratinjau-url');
        var wadahPratinjauUrl = document.getElementById('wadah-pratinjau-url');
        var imgPratinjauUrl = document.getElementById('img-pratinjau-url');

        var targetSlotIndex = null;
        var mediaTerpilih = null; // { url, path }

        // Buka modal dari tombol baris gambar latar
        document.querySelectorAll('[data-buka-media]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                targetSlotIndex = btn.dataset.bukaMedia;
                mediaTerpilih = null;
                pilihBtn.disabled = true;
                latarModal.hidden = false;
                aktifkanTab('tab-pustaka');
                muatDaftarMedia();
            });
        });

        // Kosongkan input
        document.querySelectorAll('[data-bersihkan-media]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var idx = btn.dataset.bersihkanMedia;
                var inp = document.getElementById('input-bg-' + idx);
                if (inp) inp.value = '';
            });
        });

        function tutupModal() {
            latarModal.hidden = true;
            targetSlotIndex = null;
            mediaTerpilih = null;
        }

        tutupBtn.addEventListener('click', tutupModal);
        batalBtn.addEventListener('click', tutupModal);
        latarModal.addEventListener('click', function (e) {
            if (e.target === latarModal) tutupModal();
        });

        // Tab switching
        document.querySelectorAll('.media-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                aktifkanTab(tab.dataset.tabTarget);
            });
        });

        function aktifkanTab(tabId) {
            document.querySelectorAll('.media-tab').forEach(function (t) {
                t.classList.toggle('is-active', t.dataset.tabTarget === tabId);
            });
            document.querySelectorAll('.media-tab-panel').forEach(function (p) {
                p.hidden = (p.id !== tabId);
            });
        }

        // Muat daftar media via AJAX
        function muatDaftarMedia() {
            statusText.textContent = 'Memuat media…';
            galeri.innerHTML = '';

            fetch('{{ route("media.list") }}')
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (!res.ok || !res.data) {
                        statusText.textContent = 'Gagal memuat media.';
                        return;
                    }
                    var list = res.data;
                    statusText.textContent = list.length + ' berkas media tersedia';

                    if (list.length === 0) {
                        galeri.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:24px;color:var(--text-mute)">Belum ada media diunggah.</div>';
                        return;
                    }

                    list.forEach(function (item) {
                        var div = document.createElement('div');
                        div.className = 'media-item';
                        div.innerHTML = '<img class="media-item__thumb" src="' + item.url + '" alt="">' +
                                        '<div class="media-item__nama" title="' + item.nama + '">' + item.nama + '</div>' +
                                        '<div class="media-item__meta">' + item.ukuran + ' · ' + item.waktu + '</div>';

                        div.addEventListener('click', function () {
                            document.querySelectorAll('.media-item').forEach(function (el) { el.classList.remove('is-selected'); });
                            div.classList.add('is-selected');
                            mediaTerpilih = item.path || item.url;
                            pilihBtn.disabled = false;
                        });

                        galeri.appendChild(div);
                    });
                })
                .catch(function () {
                    statusText.textContent = 'Kesalahan saat menghubungi server.';
                });
        }

        segarkanBtn.addEventListener('click', muatDaftarMedia);

        // Upload berkas
        areaDrop.addEventListener('click', function () { inputBerkas.click(); });
        inputBerkas.addEventListener('change', function () {
            if (!inputBerkas.files || !inputBerkas.files[0]) return;
            var file = inputBerkas.files[0];
            statusUnggah.innerHTML = '<span class="text-mute">Mengunggah ' + file.name + '…</span>';

            var formData = new FormData();
            formData.append('berkas', file);

            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            fetch('{{ route("media.upload") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.ok && res.media) {
                    statusUnggah.innerHTML = '<span class="text-success" style="color:var(--green-600)">✓ ' + res.pesan + '</span>';
                    mediaTerpilih = res.media.path;
                    pilihBtn.disabled = false;
                    muatDaftarMedia();
                    setTimeout(function () { aktifkanTab('tab-pustaka'); }, 800);
                } else {
                    statusUnggah.innerHTML = '<span class="text-danger" style="color:var(--red-600)">✗ ' + (res.pesan || 'Unggah gagal.') + '</span>';
                }
            })
            .catch(function (err) {
                statusUnggah.innerHTML = '<span class="text-danger" style="color:var(--red-600)">✗ Galat jaringan: ' + err.message + '</span>';
            });
        });

        // Insert from URL
        btnPratinjauUrl.addEventListener('click', function () {
            var url = inputUrl.value.trim();
            if (!url) return;
            imgPratinjauUrl.src = url;
            wadahPratinjauUrl.style.display = 'block';
            mediaTerpilih = url;
            pilihBtn.disabled = false;
        });

        inputUrl.addEventListener('input', function () {
            var url = inputUrl.value.trim();
            if (url) {
                mediaTerpilih = url;
                pilihBtn.disabled = false;
            } else {
                wadahPratinjauUrl.style.display = 'none';
                pilihBtn.disabled = true;
            }
        });

        // Terapkan pilihan ke input form target
        pilihBtn.addEventListener('click', function () {
            if (!mediaTerpilih || targetSlotIndex === null) return;
            var inputTarget = document.getElementById('input-bg-' + targetSlotIndex);
            if (inputTarget) {
                inputTarget.value = mediaTerpilih;
            }
            tutupModal();
        });
    })();
</script>
@endpush
