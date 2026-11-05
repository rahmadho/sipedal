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
        $rules = [
            'tahun' => ['required', 'integer', 'min:2020', 'max:2030'],
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
        ];

        $peran = session('peran');
        if (in_array($peran, ['super_admin'], true)) {
            $rules['kd_klpd'] = ['nullable', 'string', 'max:10'];
        }

        $data = $request->validate($rules);

        $update = [
            'tahun' => (int) $data['tahun'],
            'bulan' => (int) $data['bulan'],
        ];

        if (!empty($data['kd_klpd'])) {
            $update['kd_klpd'] = strtoupper($data['kd_klpd']);
        }

        session($update);

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ][$data['bulan']] ?? '';

        $infoKlpd = !empty($data['kd_klpd']) ? ' (' . $data['kd_klpd'] . ')' : '';

        return redirect()->back()->with('status_periode', "Konteks data berhasil diperbarui ke {$namaBulan} {$data['tahun']}{$infoKlpd}.");
    }
}
