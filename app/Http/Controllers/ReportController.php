<?php

namespace App\Http\Controllers;

use App\Support\Mock\Mock;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function itkpBulanan()
    {
        return view('report.itkp-bulanan', [
            'baris' => Mock::itkpBulanan(),
        ]);
    }

    public function itkpTahunan()
    {
        return view('report.itkp-tahunan', [
            'baris' => Mock::itkpTahunan(),
        ]);
    }

    public function pantau(Request $request, string $jenis)
    {
        $daftar = Mock::pantauJenis();
        abort_unless(isset($daftar[$jenis]), 404);

        $halaman = max(1, (int) $request->query('halaman', 1));
        $perHalaman = 25;
        $offset = ($halaman - 1) * $perHalaman;

        return view('report.pantau', [
            'jenis' => $jenis,
            'meta' => $daftar[$jenis],
            'daftar' => $daftar,
            'baris' => Mock::pantau($jenis, $perHalaman, $offset),
            'halaman' => $halaman,
            'perHalaman' => $perHalaman,
            'total' => match ($jenis) {
                'rup' => 16_024,
                'tender' => 94,
                'nontender' => 1_681,
                'pencatatan-nontender' => 7,
                'pencatatan-swakelola' => 2_409,
                'e-katalog' => 5_770,
                'e-kontrak' => 412,
                default => 0,
            },
        ]);
    }
}
