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
        ]);
    }
}
