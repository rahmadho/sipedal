<?php

namespace App\Http\Controllers;

use App\Support\Mock\Mock;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function log(Request $request)
    {
        $halaman = max(1, (int) $request->query('halaman', 1));
        $perHalaman = 20;

        $query = \App\Models\PullLog::query();

        if ($cari = $request->query('cari')) {
            $query->where('endpoint', 'like', "%{$cari}%");
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($grup = $request->query('grup')) {
            $query->where('grup', $grup);
        }

        $total = $query->count();
        $logs = $query->orderByDesc('id')
            ->offset(($halaman - 1) * $perHalaman)
            ->limit($perHalaman)
            ->get();

        $baris = $logs->map(function ($l) {
            $waktu = $l->created_at ? $l->created_at->format('d/m/Y H:i:s') : '—';
            return [
                'waktu' => $waktu,
                'endpoint' => $l->endpoint,
                'grup' => $l->grup ?: '—',
                'api' => $l->api ?: 'v1',
                'klpd' => $l->kode_klpd,
                'status' => $l->status,
                'rows' => (int) $l->rows,
                'pages' => (int) $l->pages,
                'durasi_ms' => (int) $l->durasi_ms,
                'pesan' => $l->pesan ?: '—',
            ];
        })->all();

        // Hitung ringkasan statistik dari seluruh data tabel pull_log
        $statOk = \App\Models\PullLog::where('status', 'OK')->count();
        $statGagal = \App\Models\PullLog::whereIn('status', ['GAGAL', 'FORBIDDEN'])->count();
        $statKosong = \App\Models\PullLog::where('status', 'KOSONG')->count();
        $statAvgDurasi = (int) round(\App\Models\PullLog::avg('durasi_ms') ?? 0);
        $terakhir = \App\Models\PullLog::orderByDesc('id')->value('created_at')?->format('d/m/Y H:i') ?? '—';

        return view('setting.log', [
            'baris' => $baris,
            'halaman' => $halaman,
            'perHalaman' => $perHalaman,
            'total' => $total,
            'statOk' => $statOk,
            'statGagal' => $statGagal,
            'statKosong' => $statKosong,
            'statAvgDurasi' => $statAvgDurasi,
            'terakhir' => $terakhir,
        ]);
    }

    public function command()
    {
        return view('setting.command', [
            'daftar' => Mock::command(),
        ]);
    }

    public function jalankanCommand(Request $request)
    {
        $request->validate([
            'command' => ['required', 'string'],
        ]);

        $peran = session('peran');
        if (!in_array($peran, ['super_admin', 'operator', 'admin_pbj'], true)) {
            return response()->json([
                'ok' => false,
                'pesan' => 'Akses ditolak: peran Anda tidak diizinkan menjalankan perintah.',
            ], 403);
        }

        $inputCmd = trim($request->input('command'));
        // Whitelist command yang aman dan diizinkan
        $allowed = [
            'inaproc:pull' => 'inaproc:pull',
            'inaproc:probe' => 'inaproc:probe',
            'inaproc:validate-tokens' => 'inaproc:validate-tokens',
            'inaproc:build-realisasi' => 'inaproc:build-realisasi',
            'inaproc:endpoints' => 'inaproc:endpoints',
            'inaproc:schema-check' => 'inaproc:schema-check',
        ];

        // Pisahkan nama command dan argumen
        $parts = explode(' ', $inputCmd, 2);
        $baseCmd = $parts[0];

        if (!isset($allowed[$baseCmd])) {
            return response()->json([
                'ok' => false,
                'pesan' => "Perintah '{$baseCmd}' tidak dikenali atau dilarang.",
            ], 422);
        }

        try {
            $exitCode = \Illuminate\Support\Facades\Artisan::call($inputCmd);
            $output = \Illuminate\Support\Facades\Artisan::output();

            return response()->json([
                'ok' => $exitCode === 0,
                'exit_code' => $exitCode,
                'output' => $output,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'exit_code' => 1,
                'output' => "Kesalahan saat menjalankan perintah: " . $e->getMessage(),
            ]);
        }
    }

    public function pengaturan()
    {
        $kdKlpd = session('kd_klpd', app(\App\Support\Tenant::class)->kode());
        $namaKlpd = \App\Support\KlpdList::nama($kdKlpd) ?? app(\App\Support\Tenant::class)->nama();

        // Nilai acuan gabungan dari database (AppSetting) dan fallback Mock
        $mock = Mock::pengaturan();
        $nilai = [
            'umum' => [
                'nama_aplikasi' => \App\Models\AppSetting::get('nama_aplikasi', $kdKlpd, $mock['umum']['nama_aplikasi']),
                'nama_instansi' => \App\Models\AppSetting::get('nama_instansi', $kdKlpd, $namaKlpd),
                'tahun_aktif' => (int) session('tahun', (int) \App\Models\AppSetting::get('tahun_aktif', $kdKlpd, now()->year)),
                'zona_waktu' => \App\Models\AppSetting::get('zona_waktu', $kdKlpd, $mock['umum']['zona_waktu']),
                'bahasa' => session('bahasa', \App\Models\AppSetting::get('bahasa', $kdKlpd, 'id')),
                'format_angka' => \App\Models\AppSetting::get('format_angka', $kdKlpd, $mock['umum']['format_angka']),
            ],
            'tampilan' => [
                'tema_aktif' => \App\Models\AppSetting::get('tema_aktif', $kdKlpd, 'biru'),
                'ukuran_halaman' => (int) \App\Models\AppSetting::get('ukuran_halaman', $kdKlpd, 50),
                'tema_gelap' => false,
                'animasi_login' => (bool) \App\Models\AppSetting::get('animasi_login', $kdKlpd, true),
                'interval_transisi' => (int) \App\Models\AppSetting::get('interval_transisi', $kdKlpd, 6),
            ],
            'data' => [
                'ambang_basi_jam' => (int) \App\Models\AppSetting::get('ambang_basi_jam', $kdKlpd, 48),
                'batas_ekspor' => (int) \App\Models\AppSetting::get('batas_ekspor', $kdKlpd, 50000),
                'jadwal_refresh' => \App\Models\AppSetting::get('jadwal_refresh', $kdKlpd, '04:00, 16:00 WIB'),
                'refresh_otomatis' => (bool) \App\Models\AppSetting::get('refresh_otomatis', $kdKlpd, true),
            ],
        ];

        return view('setting.pengaturan', [
            'nilai' => $nilai,
            'tema' => config('sipedal.tema', []),
            'tenant' => app(\App\Support\Tenant::class),
            'daftarTenant' => config('sipedal.tenants', []),
            'scheduler' => \App\Support\SchedulerConfig::get(),
            'kdKlpdAktif' => $kdKlpd,
            'namaKlpdAktif' => $namaKlpd,
        ]);
    }

    public function simpanUmum(Request $request)
    {
        $peran = session('peran');
        if (!in_array($peran, ['super_admin', 'operator', 'admin_pbj'], true)) {
            return redirect()->route('setting.pengaturan')->with('galat', 'Akses ditolak: peran Anda tidak dapat mengubah pengaturan.');
        }

        $kdKlpd = session('kd_klpd', app(\App\Support\Tenant::class)->kode());

        $data = $request->validate([
            'nama_aplikasi' => ['required', 'string', 'max:100'],
            'nama_instansi' => ['required', 'string', 'max:200'],
            'tahun_aktif' => ['required', 'integer', 'min:2020', 'max:2030'],
            'zona_waktu' => ['required', 'string'],
            'bahasa' => ['required', 'string', 'in:id,en'],
            'format_angka' => ['required', 'string'],
        ]);

        foreach ($data as $k => $v) {
            \App\Models\AppSetting::set($k, $v, $kdKlpd);
        }

        // Sinkronkan ke sesi
        session([
            'tahun' => (int) $data['tahun_aktif'],
            'bahasa' => $data['bahasa'],
        ]);

        return redirect()->route('setting.pengaturan')->with('status', 'Pengaturan umum dan bahasa berhasil disimpan.');
    }

    public function simpanTampilan(Request $request)
    {
        $peran = session('peran');
        $kdKlpd = session('kd_klpd', app(\App\Support\Tenant::class)->kode());

        $data = $request->validate([
            'tema_aktif' => ['nullable', 'string'],
            'ukuran_halaman' => ['required', 'integer', 'in:25,50,100'],
        ]);

        if (!empty($data['tema_aktif'])) {
            if ($peran !== 'super_admin') {
                return redirect()->route('setting.pengaturan')->with('galat', 'Hanya Super Admin yang dapat mengganti skema warna.');
            }
            \App\Models\AppSetting::set('tema_aktif', $data['tema_aktif'], $kdKlpd);
        }

        \App\Models\AppSetting::set('ukuran_halaman', $data['ukuran_halaman'], $kdKlpd);

        return redirect()->route('setting.pengaturan')->with('status', 'Pengaturan tampilan berhasil disimpan.');
    }

    public function simpanData(Request $request)
    {
        $peran = session('peran');
        if (!in_array($peran, ['super_admin', 'operator', 'admin_pbj'], true)) {
            return redirect()->route('setting.pengaturan')->with('galat', 'Akses ditolak.');
        }

        $kdKlpd = session('kd_klpd', app(\App\Support\Tenant::class)->kode());

        $data = $request->validate([
            'ambang_basi_jam' => ['required', 'integer', 'min:1'],
            'batas_ekspor' => ['required', 'integer', 'min:100'],
        ]);

        \App\Models\AppSetting::set('ambang_basi_jam', $data['ambang_basi_jam'], $kdKlpd);
        \App\Models\AppSetting::set('batas_ekspor', $data['batas_ekspor'], $kdKlpd);

        return redirect()->route('setting.pengaturan')->with('status', 'Pengaturan data & sinkronisasi berhasil disimpan.');
    }

    public function simpanScheduler(Request $request)
    {
        $peran = session('peran');
        if (!in_array($peran, ['super_admin', 'operator', 'admin_pbj'], true)) {
            return redirect()->route('setting.pengaturan')->with('galat', 'Akses ditolak: peran Anda tidak dapat mengubah penjadwalan.');
        }

        $config = \App\Support\SchedulerConfig::get();
        $config['enabled'] = $request->boolean('scheduler_enabled');

        $jobsInput = $request->input('jobs', []);
        foreach ($config['jobs'] as $key => &$job) {
            $jobEnabled = !empty($jobsInput[$key]['enabled']);
            $job['enabled'] = $jobEnabled;

            $timesRaw = $jobsInput[$key]['times'] ?? '';
            $timesList = array_filter(array_map('trim', explode(',', (string) $timesRaw)));

            // Validasi format jam HH:MM
            $validTimes = [];
            foreach ($timesList as $t) {
                if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $t)) {
                    $validTimes[] = $t;
                }
            }

            if (!empty($validTimes)) {
                sort($validTimes);
                $job['times'] = array_values(array_unique($validTimes));
            }
        }

        \App\Support\SchedulerConfig::save($config);

        return redirect()->route('setting.pengaturan')->with('status', 'Pengaturan jadwal otomatis berhasil disimpan.');
    }

    public function profile()
    {
        return view('setting.profile', [
            'user' => Mock::USER_DEMO,
            'satker' => \App\Support\Mock\SatkerPilihan::semua(),
        ]);
    }

    public function simpanProfile(Request $request)
    {
        $peran = session('peran');
        $wajib = \App\Support\SatkerFilter::wajib($peran);

        $rules = [
            'nama' => ['required', 'string', 'max:255'],
            'jabatan' => ['nullable', 'string', 'max:200'],
            'nip' => ['nullable', 'string', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'rup_stk_id' => [$wajib ? 'required' : 'nullable', 'integer'],
        ];

        $pesan = [
            'rup_stk_id.required' => \App\Support\SatkerFilter::pesan($peran),
        ];

        $data = $request->validate($rules, $pesan);

        session([
            'nama' => $data['nama'],
            'jabatan' => $data['jabatan'] ?? null,
            'nip' => $data['nip'] ?? null,
            'telepon' => $data['telepon'] ?? null,
            'email' => $data['email'] ?? null,
            'rup_stk_id' => !empty($data['rup_stk_id']) ? (int) $data['rup_stk_id'] : null,
        ]);

        return redirect()->route('setting.profile')->with('status', 'Data profil berhasil diperbarui.');
    }
}
