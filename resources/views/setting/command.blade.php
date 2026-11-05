@extends('layouts.app')

@section('judul', 'Command')

@section('konten')

    <div class="page-head">
        <div class="page-head__title">
            <h1>Command Manual</h1>
            <p>
                Perintah penarikan data yang dijalankan atas permintaan, bukan oleh penjadwal.
                Berguna ketika satu endpoint perlu ditarik ulang tanpa menunggu jadwal harian.
            </p>
        </div>
    </div>

    <div class="banner banner--peringatan">
        <span class="banner__ikon"><x-ikon nama="alert" :ukuran="17" /></span>
        <div>
            <strong>Perintah berat memakai kuota API.</strong>
            Setiap penarikan penuh mengirim hingga 61 permintaan per KLPD. Pastikan
            jadwal harian sudah berjalan sebelum menjalankan ulang secara manual.
        </div>
    </div>

    <div class="grid grid-2">
        @foreach ($daftar as $c)
            @php
                $warnaBahaya = match ($c['bahaya']) {
                    'berat' => ['merah', 'Berat'],
                    'ringan' => ['amber', 'Ringan'],
                    default => ['hijau', 'Aman'],
                };
            @endphp

            <div class="cmd-kartu">
                <span class="cmd-kartu__ikon"><x-ikon nama="terminal" :ukuran="16" /></span>
                <div class="grow" style="min-width:0">
                    <div class="row-between gap-2">
                        <div class="cmd-kartu__nama">{{ $c['nama'] }}</div>
                        <span class="badge badge--{{ $warnaBahaya[0] }}">{{ $warnaBahaya[1] }}</span>
                    </div>
                    <div class="cmd-kartu__ket">{{ $c['ket'] }}</div>

                    @if ($c['args'])
                        <div class="mono text-xs text-mute" style="margin-top:8px;padding:6px 9px;background:var(--gray-50);border-radius:6px;border:1px solid var(--border)">
                            {{ $c['args'] }}
                        </div>
                    @endif

                    <div class="cmd-kartu__foot">
                        @php
                            $fullCommand = trim($c['nama'] . ' ' . $c['args']);
                        @endphp
                        <button class="btn btn-primary btn-sm" type="button" data-jalankan="{{ $fullCommand }}">
                            <x-ikon nama="play" :ukuran="13" />
                            Jalankan
                        </button>
                        <button class="btn btn-ghost btn-sm" type="button" data-salin="{{ $c['nama'] }} {{ $c['args'] }}">
                            <x-ikon nama="file" :ukuran="13" />
                            Salin
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ================= KONSOL KELUARAN ================= --}}
    <div class="card" style="margin-top:16px">
        <div class="card__head">
            <div>
                <h2>Keluaran Perintah</h2>
                <p id="konsol-status">Belum ada perintah dijalankan</p>
            </div>
            <div class="head-aksi">
                <button class="btn btn-ghost btn-sm" type="button" id="bersihkan-konsol">
                    <x-ikon nama="trash" :ukuran="13" />
                    Bersihkan
                </button>
            </div>
        </div>
        <div class="card__body card__body--flush" style="padding:16px">
            <div class="log-konsol" id="konsol" role="log" aria-live="polite">
                <div><span class="dim"># Konsol siap. Pilih perintah di atas untuk melihat keluarannya.</span></div>
            </div>
        </div>
    </div>

    {{-- ================= MODAL KONFIRMASI ================= --}}
    <div class="modal-latar" id="latar-konfirmasi" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="judul-konfirmasi">
            <div class="modal__head">
                <h2 id="judul-konfirmasi">Jalankan perintah?</h2>
                <p id="ket-konfirmasi">—</p>
            </div>
            <div class="modal__body">
                <div class="banner banner--peringatan" style="margin:0">
                    <span class="banner__ikon"><x-ikon nama="alert" :ukuran="16" /></span>
                    <div>
                        Perintah ini akan dieksekusi secara nyata di backend server.
                        Pastikan argumen dan tujuan sudah sesuai.
                    </div>
                </div>
            </div>
            <div class="modal__foot">
                <button class="btn btn-ghost" type="button" id="batal-konfirmasi">Batal</button>
                <button class="btn btn-primary" type="button" id="lanjut-konfirmasi">
                    <x-ikon nama="play" :ukuran="14" />
                    Jalankan
                </button>
            </div>
        </div>
    </div>

@endsection

@push('skrip')
<script>
    (function () {
        var konsol = document.getElementById('konsol');
        var status = document.getElementById('konsol-status');
        var latar = document.getElementById('latar-konfirmasi');
        var ket = document.getElementById('ket-konfirmasi');
        var perintahTertunda = null;

        function tulis(html) {
            var div = document.createElement('div');
            div.innerHTML = html;
            konsol.appendChild(div);
            konsol.scrollTop = konsol.scrollHeight;
        }

        function jam() {
            var d = new Date();
            return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0') + ':' + String(d.getSeconds()).padStart(2, '0');
        }

        // Eksekusi nyata perintah artisan melalui backend
        function jalankan(nama) {
            status.textContent = 'Menjalankan: ' + nama + '…';
            tulis('<span class="waktu">[' + jam() + ']</span> <span class="info">$ php artisan ' + nama + '</span>');
            tulis('<span class="dim">Mengirim perintah ke server…</span>');

            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            fetch('{{ route("setting.command.run") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ command: nama })
            })
            .then(function(res) {
                return res.json().then(function(data) {
                    return { status: res.status, data: data };
                });
            })
            .then(function(resp) {
                var data = resp.data;
                if (!resp.status || resp.status >= 400 || !data.ok) {
                    var pesan = data.pesan || data.output || 'Gagal menjalankan perintah.';
                    tulis('<span class="waktu">[' + jam() + ']</span> <span class="err">GAGAL (Kode ' + (data.exit_code || resp.status) + '): ' + pesan + '</span>');
                    status.textContent = 'Gagal: ' + nama;
                    return;
                }

                // Tampilkan output baris per baris
                var lines = (data.output || '').split('\n');
                lines.forEach(function(line) {
                    if (line.trim().length > 0) {
                        tulis('<span class="dim">&gt; ' + escapeHtml(line) + '</span>');
                    }
                });

                tulis('<span class="waktu">[' + jam() + ']</span> <span class="ok">Selesai dieksekusi dengan kode keluar 0</span>');
                status.textContent = 'Selesai: ' + nama;
            })
            .catch(function(err) {
                tulis('<span class="waktu">[' + jam() + ']</span> <span class="err">Kesalahan jaringan: ' + err.message + '</span>');
                status.textContent = 'Galat: ' + nama;
            });
        }

        function escapeHtml(str) {
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        document.querySelectorAll('[data-jalankan]').forEach(function (t) {
            t.addEventListener('click', function () {
                perintahTertunda = t.dataset.jalankan;
                ket.textContent = 'php artisan ' + perintahTertunda;
                latar.hidden = false;
            });
        });

        document.getElementById('batal-konfirmasi').addEventListener('click', function () { latar.hidden = true; });
        latar.addEventListener('click', function (e) { if (e.target === latar) latar.hidden = true; });

        document.getElementById('lanjut-konfirmasi').addEventListener('click', function () {
            latar.hidden = true;
            if (perintahTertunda) jalankan(perintahTertunda);
            perintahTertunda = null;
        });

        document.querySelectorAll('[data-salin]').forEach(function (t) {
            t.addEventListener('click', function () {
                navigator.clipboard.writeText('php artisan ' + t.dataset.salin);
                var asli = t.innerHTML;
                t.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m4.5 12.5 5 5 10-11"/></svg> Tersalin';
                setTimeout(function () { t.innerHTML = asli; }, 1400);
            });
        });

        document.getElementById('bersihkan-konsol').addEventListener('click', function () {
            konsol.innerHTML = '<div><span class="dim"># Konsol dibersihkan.</span></div>';
            status.textContent = 'Belum ada perintah dijalankan';
        });

        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') latar.hidden = true; });
    })();
</script>
@endpush
