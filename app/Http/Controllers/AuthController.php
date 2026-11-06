<?php

namespace App\Http\Controllers;

use App\Support\Mock\Mock;
use App\Support\Mock\SatkerPilihan;
use App\Support\SatkerFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

/**
 * Autentikasi tahap UI/UX — TANPA basis data.
 *
 * Kata sandi tidak diverifikasi dan tidak disimpan. Ini hanya untuk meninjau
 * tampilan tiap peran. Pada tahap API, kelas ini diganti guard Laravel
 * dengan hash bcrypt, rate limit, dan audit login.
 */
class AuthController extends Controller
{
    public function tampil(Request $request)
    {
        $tenant = app(\App\Support\Tenant::class);
        $kdKlpd = $tenant->kode();

        // Ambil pengaturan gambar latar dan status animasi dari AppSetting
        $bgSetting = \App\Models\AppSetting::get('login_bg_list', $kdKlpd);
        if ($bgSetting) {
            $latar = json_decode($bgSetting, true) ?: $tenant->latarLogin();
        } else {
            $latar = $tenant->latarLogin();
        }

        $animasi = (bool) \App\Models\AppSetting::get('animasi_login', $kdKlpd, true);

        return view('auth.login', [
            'latar' => $latar,
            'animasi' => $animasi,
            'satker' => SatkerPilihan::semua(),
        ]);
    }

    public function masuk(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'min:3'],
            'peran' => ['nullable', 'string'],
            'rup_stk_id' => ['nullable', 'integer'],
        ]);

        // Peran diambil dari pilihan cepat bila ada; jika tidak, Super Admin.
        $peran = $data['peran'] ?? 'super_admin';
        if (! array_key_exists($peran, config('sipedal.roles', []))) {
            $peran = 'operator';
        }

        // Peran yang mengelola satu satuan kerja wajib menunjuk satkernya.
        if (SatkerFilter::wajib($peran) && empty($data['rup_stk_id'])) {
            return Redirect::route('login')
                ->withInput()
                ->with('galat', SatkerFilter::pesan($peran));
        }

        $demo = Mock::USER_DEMO;
        $tenant = app(\App\Support\Tenant::class);

        $request->session()->regenerate();
        $request->session()->put([
            'peran' => $peran,
            'nama' => $demo['nama'],
            'username' => $data['username'],
            // Email opsional dan tidak unik: hanya dipakai bila pengguna memilikinya.
            'email' => $demo['email'] ?? null,
            'jabatan' => $demo['jabatan'],
            'nip' => $demo['nip'],
            'telepon' => $demo['telepon'],
            'terakhir_masuk' => $demo['terakhir_masuk'],
            'rup_stk_id' => ! empty($data['rup_stk_id']) ? (int) $data['rup_stk_id'] : null,
            'kd_klpd' => $tenant->kode(),
            'tahun' => (int) now()->year,
            'bulan' => (int) now()->month,
        ]);

        return Redirect::route('dashboard');
    }

    public function keluar(Request $request)
    {
        $request->session()->flush();
        $request->session()->regenerateToken();

        return Redirect::route('login');
    }
}
