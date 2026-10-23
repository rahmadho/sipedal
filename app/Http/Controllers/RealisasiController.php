<?php

namespace App\Http\Controllers;

use App\Support\Mock\Mock;
use Illuminate\Http\Request;

class RealisasiController extends Controller
{
    public function index(Request $request)
    {
        $halaman = max(1, (int) $request->query('halaman', 1));
        $perHalaman = 50;
        $offset = ($halaman - 1) * $perHalaman;

        return view('realisasi.index', [
            'baris' => Mock::realisasi($perHalaman, $offset),
            'halaman' => $halaman,
            'perHalaman' => $perHalaman,
            'total' => Mock::ACUAN['view_baris'],
            'komposisi' => Mock::komposisiSumber(),
            'sumber' => array_keys(Mock::SUMBER),
        ]);
    }
}
