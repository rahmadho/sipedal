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
        $offset = ($halaman - 1) * $perHalaman;

        return view('setting.log', [
            'baris' => Mock::logSinkronisasi($perHalaman, $offset),
            'halaman' => $halaman,
            'perHalaman' => $perHalaman,
            'total' => 33,
        ]);
    }

    public function command()
    {
        return view('setting.command', [
            'daftar' => Mock::command(),
        ]);
    }

    public function pengaturan()
    {
        return view('setting.pengaturan', [
            'nilai' => Mock::pengaturan(),
            'tema' => config('sipedal.tema', []),
            'tenant' => app(\App\Support\Tenant::class),
            'daftarTenant' => config('sipedal.tenants', []),
        ]);
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
