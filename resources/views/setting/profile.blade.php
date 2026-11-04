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
                        <dd class="mono text-xs">{{ $user['nip'] }}</dd>
                        <dt>Email</dt>
                        <dd class="text-xs" style="word-break:break-all">
                            {{ session('email', $user['email']) ?: 'Belum diisi' }}
                        </dd>
                        <dt>Telepon</dt>
                        <dd class="text-xs">{{ $user['telepon'] }}</dd>
                        <dt>KLPD</dt>
                        <dd class="text-xs">{{ $tenant->nama() }}</dd>
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

            <div class="card">
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
                            <input class="input" id="nama" value="{{ session('nama', $user['nama']) }}">
                        </div>
                        <div class="field">
                            <label for="username">Nama Pengguna</label>
                            <input class="input mono" id="username" value="{{ session('username', $user['username']) }}">
                        </div>
                        <div class="field">
                            <label for="jabatan">Jabatan</label>
                            <input class="input" id="jabatan" value="{{ session('jabatan', $user['jabatan']) }}">
                        </div>
                        <div class="field">
                            <label for="nip">NIP</label>
                            <input class="input" id="nip" value="{{ $user['nip'] }}" class="mono">
                        </div>
                        <div class="field">
                            <label for="telp">Telepon</label>
                            <input class="input" id="telp" value="{{ $user['telepon'] }}">
                        </div>
                    </div>
                    <div class="field" style="margin-top:14px">
                        <label for="email-profil">
                            Email
                            <span class="text-mute fw-500" style="font-weight:500">(opsional)</span>
                        </label>
                        <input class="input" id="email-profil" type="email" value="{{ session('email', $user['email']) }}">
                        <span class="field__hint">
                            Boleh dikosongkan. Dua pengguna boleh memakai email yang sama,
                            karena yang dipakai untuk masuk adalah nama pengguna.
                        </span>
                    </div>

                    <div class="row" style="justify-content:flex-end;margin-top:16px">
                        <button class="btn btn-primary btn-sm" type="button">
                            <x-ikon nama="check" :ukuran="14" />
                            Simpan Data
                        </button>
                    </div>
                </div>
            </div>

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
