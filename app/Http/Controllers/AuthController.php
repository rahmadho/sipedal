<?php

namespace App\Http\Controllers;

use App\Support\Mock\Mock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

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

        return view('auth.login', [
            'latar' => $tenant->latarLogin(),
        ]);
    }

    public function masuk(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'peran' => ['nullable', 'string'],
        ]);

        // Peran diambil dari pilihan cepat bila ada; jika tidak, Super Admin.
        $peran = $data['peran'] ?? 'super_admin';
        if (! array_key_exists($peran, config('sipedal.roles', []))) {
            $peran = 'operator';
        }

        $demo = Mock::USER_DEMO;

        $request->session()->regenerate();
        $request->session()->put([
            'peran' => $peran,
            'nama' => $demo['nama'],
            'email' => $data['email'],
            'jabatan' => $demo['jabatan'],
            'nip' => $demo['nip'],
            'telepon' => $demo['telepon'],
            'terakhir_masuk' => $demo['terakhir_masuk'],
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
