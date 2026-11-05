@extends('layouts.app')

@section('judul', 'Profile')

@section('konten')

    <div class="page-head">
        <div class="page-head__title">
            <h1>Profil Saya</h1>
            <p>Data akun, peran, dan keamanan sesi Anda.</p>
        </div>
    </div>

    <div class="grid grid-1-2">

        {{-- Kartu identitas --}}
        <div class="col gap-4">
            <div class="card">
                <div class="card__body" style="text-align:center;padding-top:26px">
                    <div style="display:inline-flex;margin-bottom:12px">
                        <span class="avatar avatar--lg">{{ inisial(session('nama', $user['nama'])) }}</span>
                    </div>
                    <div class="fw-700 text-lg">{{ session('nama', $user['nama']) }}</div>
                    <div class="text-sm text-mute" style="margin-top:2px">{{ session('jabatan', $user['jabatan']) }}</div>

                    <div style="margin-top:12px">
                        <span class="badge badge--brand">
                            <x-ikon nama="shield" :ukuran="11" />
                            {{ \App\Support\Menu::labelPeran(session('peran', 'operator')) }}
                        </span>
                    </div>

                    <div class="pemisah"></div>

                    <dl class="dl" style="text-align:left">
                        <dt>Nama pengguna</dt>
                        <dd class="mono text-xs">{{ session('username', $user['username']) }}</dd>
                        <dt>NIP</dt>
                        <dd class="mono text-xs">{{ session('nip', $user['nip']) }}</dd>
                        <dt>Email</dt>
                        <dd class="text-xs" style="word-break:break-all">
                            {{ session('email', $user['email']) ?: 'Belum diisi' }}
                        </dd>
                        <dt>Satuan kerja</dt>
                        <dd class="text-xs">
                            @if (session('rup_stk_id'))
                                {{ \App\Support\Mock\SatkerPilihan::nama((int) session('rup_stk_id')) ?: ('Kode ' . session('rup_stk_id')) }}
                            @else
                                <span class="text-mute">Belum diatur</span>
                            @endif
                        </dd>
                        <dt>KLPD Aktif</dt>
                        <dd class="text-xs mono">{{ session('kd_klpd', $tenant->kode()) }} · {{ $tenant->nama() }}</dd>
                        <dt>Tahun &amp; Bulan</dt>
                        <dd class="text-xs">Tahun {{ session('tahun', now()->year) }} (Bulan {{ session('bulan', now()->month) }})</dd>
                        <dt>Telepon</dt>
                        <dd class="text-xs">{{ session('telepon', $user['telepon']) }}</dd>
                        <dt>Lingkup</dt>
                        <dd class="text-xs">{{ \App\Support\Menu::lingkupPeran(session('peran', 'operator')) }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card__head">
                    <div>
                        <h2>Sesi Aktif</h2>
                        <p>Perangkat yang sedang masuk</p>
                    </div>
                </div>
                <div class="card__body">
                    <div class="timeline">
                        <div class="timeline__item timeline__item--kini">
                            <span class="timeline__titik"></span>
                            <div class="timeline__waktu">Sesi ini · {{ tgl($user['terakhir_masuk'], true) }}</div>
                            <div class="timeline__judul">Peramban di perangkat ini</div>
                            <div class="timeline__ket">{{ request()->header('User-Agent') ? \Illuminate\Support\Str::limit(request()->header('User-Agent'), 46) : '—' }}</div>
                        </div>
                        <div class="timeline__item">
                            <span class="timeline__titik"></span>
                            <div class="timeline__waktu">22/10/2026 14:08</div>
                            <div class="timeline__judul">Chrome · Windows</div>
                            <div class="timeline__ket">Kantor Dinas PU · 103.22.250.210</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Formulir --}}
        <div class="col gap-4">

            @if (session('status'))
                <div class="banner banner--sukses">
                    <span class="banner__ikon"><x-ikon nama="check" :ukuran="16" /></span>
                    <div>{{ session('status') }}</div>
                </div>
            @endif

            @if ($errors->any())
                <div class="banner banner--bahaya">
                    <span class="banner__ikon"><x-ikon nama="alert" :ukuran="16" /></span>
                    <div>{{ $errors->first() }}</div>
                </div>
            @endif

            <form class="card" method="POST" action="{{ route('setting.profile.simpan') }}">
                @csrf
                <div class="card__head">
                    <div>
                        <h2>Data Pribadi</h2>
                        <p>Perubahan akan tercatat pada log aktivitas</p>
                    </div>
                </div>
                <div class="card__body">
                    <div class="grid grid-2 gap-4">
                        <div class="field">
                            <label for="nama">Nama Lengkap</label>
                            <input class="input" id="nama" name="nama" value="{{ old('nama', session('nama', $user['nama'])) }}" required>
                        </div>
                        <div class="field">
                            <label for="username">Nama Pengguna</label>
                            <input class="input mono" id="username" value="{{ session('username', $user['username']) }}" disabled title="Nama pengguna tidak dapat diubah">
                        </div>
                        <div class="field">
                            <label for="jabatan">Jabatan</label>
                            <input class="input" id="jabatan" name="jabatan" value="{{ old('jabatan', session('jabatan', $user['jabatan'])) }}">
                        </div>
                        <div class="field">
                            <label for="nip">NIP</label>
                            <input class="input mono" id="nip" name="nip" value="{{ old('nip', session('nip', $user['nip'])) }}">
                        </div>
                        <div class="field">
                            <label for="telp">Telepon</label>
                            <input class="input" id="telp" name="telepon" value="{{ old('telepon', session('telepon', $user['telepon'])) }}">
                        </div>
                        <div class="field">
                            <label for="email-profil">
                                Email
                                <span class="text-mute fw-500">(opsional)</span>
                            </label>
                            <input class="input" id="email-profil" name="email" type="email" value="{{ old('email', session('email', $user['email'])) }}">
                        </div>
                    </div>

                    <div class="field" style="margin-top:14px">
                        <label for="rup_stk_id">
                            Satuan Kerja
                            @if (\App\Support\SatkerFilter::wajib(session('peran')))
                                <span class="wajib" style="color:var(--red-600)">* (Wajib untuk peran ini)</span>
                            @else
                                <span class="text-mute fw-500">(opsional)</span>
                            @endif
                        </label>
                        <select class="select js-combobox-asal" id="rup_stk_id" name="rup_stk_id"
                                data-placeholder="Pilih satuan kerja" data-cari="Cari satuan kerja" data-keterangan="1">
                            <option value="">Pilih satuan kerja</option>
                            @foreach ($satker as $s)
                                <option value="{{ $s['nilai'] }}"
                                    @selected((string) old('rup_stk_id', session('rup_stk_id')) === (string) $s['nilai'])
                                    data-keterangan="{{ $s['keterangan'] }}">
                                    {{ $s['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <span class="field__hint">
                            Satuan kerja digunakan untuk menyaring data yang Anda lihat pada dashboard dan laporan.
                        </span>
                    </div>

                    <div class="row" style="justify-content:flex-end;margin-top:20px">
                        <button class="btn btn-primary btn-sm" type="submit">
                            <x-ikon nama="check" :ukuran="14" />
                            Simpan Data
                        </button>
                    </div>
                </div>
            </form>

            <div class="card">
                <div class="card__head">
                    <div>
                        <h2>Keamanan</h2>
                        <p>Kata sandi dan verifikasi dua langkah</p>
                    </div>
                </div>
                <div class="card__body">
                    <div class="grid grid-2 gap-4">
                        <div class="field">
                            <label for="sandi-lama">Kata sandi saat ini</label>
                            <input class="input" id="sandi-lama" type="password" placeholder="••••••••">
                        </div>
                        <div class="field">
                            <label for="sandi-baru">Kata sandi baru</label>
                            <input class="input" id="sandi-baru" type="password" placeholder="Minimal 12 karakter">
                        </div>
                    </div>

                    <div class="banner banner--info" style="margin-top:14px;margin-bottom:0">
                        <span class="banner__ikon"><x-ikon nama="lock" :ukuran="15" /></span>
                        <div>
                            Gunakan kombinasi huruf besar, kecil, angka, dan simbol. Kata sandi
                            tidak pernah ditampilkan kembali setelah disimpan.
                        </div>
                    </div>

                    <div class="pemisah"></div>

                    <div class="row-between wrap gap-3">
                        <div>
                            <div class="fw-600 text-sm">Verifikasi dua langkah</div>
                            <div class="text-xs text-mute">Kode dikirim melalui aplikasi autentikator</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox">
                            <span class="switch__track"></span>
                        </label>
                    </div>

                    <div class="row" style="justify-content:flex-end;margin-top:16px">
                        <button class="btn btn-primary btn-sm" type="button">
                            Perbarui Kata Sandi
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card__head">
                    <div>
                        <h2>Aktivitas Terakhir</h2>
                        <p>Lima tindakan terakhir pada akun ini</p>
                    </div>
                </div>
                <div class="card__body card__body--flush">
                    <div class="tabel-wrap">
                        <table class="tabel tabel--padat">
                            <caption class="sr-only">Aktivitas terakhir pengguna</caption>
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Tindakan</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ([
                                    ['23/10/2026 08:42', 'Masuk', 'Peramban Chrome · 103.22.250.210'],
                                    ['22/10/2026 16:20', 'Ekspor CSV', 'Realisasi RUP · 3.665 baris'],
                                    ['22/10/2026 14:08', 'Ubah tema', 'KLPD D463 · Hijau Andalas'],
                                    ['21/10/2026 11:35', 'Jalankan perintah', 'inaproc:pull --klpd=D462'],
                                    ['20/10/2026 09:12', 'Masuk', 'Peramban Firefox · 103.22.250.210'],
                                ] as $a)
                                    <tr>
                                        <td class="nowrap mono text-xs">{{ $a[0] }}</td>
                                        <td class="fw-500">{{ $a[1] }}</td>
                                        <td class="text-xs text-mute">{{ $a[2] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection
