<?php

namespace App\Http\Controllers;

use App\Support\Mock\Mock;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.index', [
            'kpi' => Mock::kpi(),
            'komposisi' => Mock::komposisiSumber(),
            'topSatker' => Mock::topSatker(5),
            'metode' => Mock::metodePengadaan(),
            'tren' => Mock::trenBulanan(),
            'statusTender' => Mock::statusTender(),
            'statusNontender' => Mock::statusNontender(),
            'statusEkatalog' => Mock::statusEkatalog(),
            'logTerakhir' => array_slice(Mock::logSinkronisasi(6), 0, 6),
            'acuan' => Mock::ACUAN,
        ]);
    }

    public function gantiPeriode(Request $request)
    {
        $data = $request->validate([
            'tahun' => ['required', 'integer', 'min:2020', 'max:2030'],
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        session([
            'tahun' => (int) $data['tahun'],
            'bulan' => (int) $data['bulan'],
        ]);

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ][$data['bulan']] ?? '';

        return redirect()->back()->with('status_periode', "Periode data berhasil diubah ke {$namaBulan} {$data['tahun']}.");
    }
}
