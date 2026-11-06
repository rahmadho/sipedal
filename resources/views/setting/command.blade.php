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

    {{-- ================= KARTU TARIK SATU ENDPOINT PILIHAN ================= --}}
    <div class="card" style="margin-bottom:20px;border:1px solid var(--border);box-shadow:var(--shadow-sm)">
        <div class="card__head" style="border-bottom:1px solid var(--border);padding:14px 18px">
            <div>
                <h2 style="font-size:15px;display:flex;align-items:center;gap:8px">
                    <span style="color:var(--brand)"><x-ikon nama="refresh" :ukuran="18" /></span>
                    Tarik Satu Endpoint Spesifik
                </h2>
                <p style="font-size:12px;color:var(--mute);margin:0">
                    Pilih salah satu endpoint dari daftar untuk melakukan sinkronisasi parsial tanpa menarik seluruh data KLPD.
                </p>
            </div>
        </div>
        <div class="card__body" style="padding:18px">
            <div class="grid grid-3 gap-3" style="align-items:flex-end">
                <div class="field" style="margin:0">
                    <label for="pilih-endpoint" style="font-weight:600;font-size:13px;margin-bottom:6px">Pilih Endpoint</label>
                    <select class="select js-combobox-asal" id="pilih-endpoint">
                        <option value="">-- Pilih Endpoint INAPROC --</option>
                        @php
                            $grupTerakhir = null;
                        @endphp
                        @foreach ($endpoints as $ep)
                            @if ($grupTerakhir !== $ep->grup)
                                @if ($grupTerakhir !== null) </optgroup> @endif
                                <optgroup label="Grup: {{ strtoupper($ep->grup) }} (API {{ strtoupper($ep->api) }})">
                                @php $grupTerakhir = $ep->grup; @endphp
                            @endif
                            <option value="{{ $ep->name }}"
                                    data-api="{{ $ep->api }}"
                                    data-grup="{{ $ep->grup }}"
                                    data-scope="{{ $ep->scope }}"
                                    data-active="{{ $ep->active ? '1' : '0' }}">
                                {{ $ep->name }} &nbsp; [{{ $ep->scope }}] {{ !$ep->active ? '— (Nonaktif)' : '' }}
                            </option>
                        @endforeach
                        @if ($grupTerakhir !== null) </optgroup> @endif
                    </select>
                </div>

                <div class="field" style="margin:0">
                    <label for="param-klpd" style="font-weight:600;font-size:13px;margin-bottom:6px">Target KLPD & Tahun</label>
                    <div class="row gap-2">
                        <input type="text" class="input mono" id="param-klpd" value="{{ $kdKlpd }}" placeholder="KLPD" style="max-width:110px">
                        <input type="number" class="input mono" id="param-tahun" value="{{ $tahun }}" placeholder="Tahun" style="max-width:90px">
                    </div>
                </div>

                <div class="field" style="margin:0">
                    <div class="row gap-2">
                        <button class="btn btn-primary grow" type="button" id="btn-jalankan-endpoint">
                            <x-ikon nama="play" :ukuran="14" />
                            Tarik Endpoint Ini
                        </button>
                        <button class="btn btn-ghost" type="button" id="btn-salin-endpoint" title="Salin Perintah Artisan">
                            <x-ikon nama="file" :ukuran="14" />
                        </button>
                    </div>
                </div>
            </div>

            <div id="endpoint-info" class="text-xs text-mute mono" style="margin-top:12px;padding:8px 12px;background:var(--gray-50);border-radius:6px;border:1px solid var(--border);display:none">
                Perintah yang akan dieksekusi: <span id="endpoint-cmd-text" style="color:var(--brand);font-weight:600"></span>
            </div>
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

        // Logika Dropdown Endpoint Tunggal
        var selEndpoint = document.getElementById('pilih-endpoint');
        var inputKlpd = document.getElementById('param-klpd');
        var inputTahun = document.getElementById('param-tahun');
        var btnRunEndpoint = document.getElementById('btn-jalankan-endpoint');
        var btnCopyEndpoint = document.getElementById('btn-salin-endpoint');
        var infoBox = document.getElementById('endpoint-info');
        var cmdText = document.getElementById('endpoint-cmd-text');

        function bangunCommandEndpoint() {
            var ep = selEndpoint.value;
            if (!ep) return null;

            var opt = selEndpoint.selectedOptions ? selEndpoint.selectedOptions[0] : null;
            var scope = opt ? opt.getAttribute('data-scope') : 'klpd';
            var klpd = inputKlpd.value.trim() || 'D462';
            var thn = inputTahun.value.trim() || '2026';

            var cmd = 'inaproc:pull --endpoint=' + ep;
            if (scope === 'klpd') {
                cmd += ' --klpd=' + klpd + ' --tahun=' + thn;
            } else if (scope === 'tahun') {
                cmd += ' --tahun=' + thn;
            } else if (scope === 'derived') {
                cmd += ' --klpd=' + klpd + ' --tahun=' + thn + ' --refresh-derived';
            }
            cmd += ' --sync';
            return cmd;
        }

        function updatePreviewCmd() {
            var cmd = bangunCommandEndpoint();
            if (cmd) {
                cmdText.textContent = 'php artisan ' + cmd;
                infoBox.style.display = 'block';
            } else {
                infoBox.style.display = 'none';
            }
        }

        selEndpoint.addEventListener('change', updatePreviewCmd);
        inputKlpd.addEventListener('input', updatePreviewCmd);
        inputTahun.addEventListener('input', updatePreviewCmd);

        btnRunEndpoint.addEventListener('click', function () {
            var cmd = bangunCommandEndpoint();
            if (!cmd) {
                alert('Silakan pilih salah satu endpoint terlebih dahulu.');
                return;
            }
            perintahTertunda = cmd;
            ket.textContent = 'php artisan ' + perintahTertunda;
            latar.hidden = false;
        });

        btnCopyEndpoint.addEventListener('click', function () {
            var cmd = bangunCommandEndpoint();
            if (!cmd) {
                alert('Silakan pilih salah satu endpoint terlebih dahulu.');
                return;
            }
            navigator.clipboard.writeText('php artisan ' + cmd);
            var asli = btnCopyEndpoint.innerHTML;
            btnCopyEndpoint.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m4.5 12.5 5 5 10-11"/></svg>';
            setTimeout(function () { btnCopyEndpoint.innerHTML = asli; }, 1400);
        });

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
