<?php

namespace App\Http\Controllers;

use App\Support\Mock\Mock;
use Illuminate\Http\Request;

class MasterController extends Controller
{
    public function klpd()
    {
        return view('master.klpd', [
            'baris' => Mock::klpd(),
        ]);
    }

    public function satker(Request $request)
    {
        $halaman = max(1, (int) $request->query('halaman', 1));
        $perHalaman = 40;
        $offset = ($halaman - 1) * $perHalaman;

        return view('master.satker', [
            'baris' => Mock::satker($perHalaman, $offset),
            'halaman' => $halaman,
            'perHalaman' => $perHalaman,
            'total' => Mock::ACUAN['satker'],
        ]);
    }

    /** Halaman "Akses" — daftar peran dan haknya. */
    public function akses()
    {
        return view('master.akses', [
            'peran' => config('sipedal.roles', []),
        ]);
    }
}
