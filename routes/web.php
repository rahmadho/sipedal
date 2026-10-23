<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KlaimController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\PerencanaanController;
use App\Http\Controllers\RealisasiController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Support\Tenant;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute aplikasi Sipedal — tahap UI/UX
|--------------------------------------------------------------------------
| Belum ada basis data. Autentikasi hanya penanda sesi (lihat AuthDemo).
| Tenant (KLPD) ditentukan dari domain oleh middleware ResolveTenant.
*/

Route::middleware('tenant')->group(function () {
    Route::get('/login', [AuthController::class, 'tampil'])->name('login');
    Route::post('/login', [AuthController::class, 'masuk'])->name('login.aksi');
    Route::post('/logout', [AuthController::class, 'keluar'])->name('logout');
});

Route::middleware(['tenant', 'auth.demo'])->group(function () {

    /* ---------------- Main ---------------- */
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/perencanaan', [PerencanaanController::class, 'index'])->name('perencanaan');
    Route::get('/realisasi', [RealisasiController::class, 'index'])->name('realisasi');
    Route::get('/klaim', [KlaimController::class, 'index'])->name('klaim');
    Route::get('/akses', [MasterController::class, 'akses'])->name('akses');

    /* ---------------- Master ---------------- */
    Route::get('/master/klpd', [MasterController::class, 'klpd'])->name('master.klpd');
    Route::get('/master/satker', [MasterController::class, 'satker'])->name('master.satker');

    /* ---------------- Report ---------------- */
    Route::get('/report/itkp/bulanan', [ReportController::class, 'itkpBulanan'])->name('report.itkp.bulanan');
    Route::get('/report/itkp/tahunan', [ReportController::class, 'itkpTahunan'])->name('report.itkp.tahunan');
    Route::get('/report/pantau/{jenis}', [ReportController::class, 'pantau'])
        ->name('report.pantau')
        ->whereIn('jenis', [
            'rup', 'tender', 'nontender', 'pencatatan-nontender',
            'pencatatan-swakelola', 'e-katalog', 'e-kontrak',
        ]);

    /* ---------------- Setting ---------------- */
    Route::get('/setting/log', [SettingController::class, 'log'])->name('setting.log');
    Route::get('/setting/command', [SettingController::class, 'command'])->name('setting.command');
    Route::get('/setting/pengaturan', [SettingController::class, 'pengaturan'])->name('setting.pengaturan');
    Route::get('/setting/profile', [SettingController::class, 'profile'])->name('setting.profile');

    /* ---------------- Pratinjau tema (khusus Super Admin) ---------------- */
    Route::get('/pratinjau-tema/{tema}', function (string $tema) {
        $daftar = config('sipedal.tema', []);
        abort_unless(array_key_exists($tema, $daftar), 404);

        // Salin tenant aktif, ganti temanya, lalu tampilkan dashboard.
        $aktif = app(Tenant::class)->keArray();
        $kunci = $aktif['kunci'];
        $data = config('sipedal.tenants.' . $kunci, []);
        $data['tema'] = $tema;

        $tenant = new Tenant($kunci, $data);
        app()->instance(Tenant::class, $tenant);
        view()->share('tenant', $tenant);

        return app(DashboardController::class)->index(request());
    })->name('pratinjau.tema');
});
