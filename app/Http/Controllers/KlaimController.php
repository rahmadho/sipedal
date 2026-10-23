<?php

namespace App\Http\Controllers;

use App\Support\Mock\Mock;
use Illuminate\Http\Request;

class KlaimController extends Controller
{
    public function index(Request $request)
    {
        $halaman = max(1, (int) $request->query('halaman', 1));
        $perHalaman = 25;
        $offset = ($halaman - 1) * $perHalaman;

        return view('klaim.index', [
            'baris' => Mock::klaim($perHalaman, $offset),
            'halaman' => $halaman,
            'perHalaman' => $perHalaman,
            'total' => 248,
        ]);
    }
}
