<?php

namespace App\Http\Controllers;

use App\Support\Mock\Mock;
use Illuminate\Http\Request;

class PerencanaanController extends Controller
{
    public function index(Request $request)
    {
        $halaman = max(1, (int) $request->query('halaman', 1));
        $perHalaman = 50;
        $offset = ($halaman - 1) * $perHalaman;

        return view('perencanaan.index', [
            'baris' => Mock::paketRup($perHalaman, $offset, 'perencanaan'),
            'halaman' => $halaman,
            'perHalaman' => $perHalaman,
            'total' => Mock::ACUAN['paket_total'],
            'metode' => array_keys(Mock::METODE),
            'jenis' => array_keys(Mock::JENIS),
            'satker' => Mock::SATKER,
        ]);
    }
}
