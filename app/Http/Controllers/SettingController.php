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
            $waktu = $l->created_at ? $l->created_at->format('d/m/Y H:i:s') : ($l->waktu ?? '—');
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
        $terakhir = \App\Models\PullLog::orderByDesc('id')->value('waktu')
            ?: (\App\Models\PullLog::orderByDesc('id')->value('created_at')?->format('d/m/Y H:i') ?? '—');

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
        return view('setting.pengaturan', [
            'nilai' => Mock::pengaturan(),
            'tema' => config('sipedal.tema', []),
            'tenant' => app(\App\Support\Tenant::class),
            'daftarTenant' => config('sipedal.tenants', []),
            'scheduler' => \App\Support\SchedulerConfig::get(),
        ]);
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
