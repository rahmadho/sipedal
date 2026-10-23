<?php

namespace App\Http\Controllers;

use App\Support\Mock\Mock;

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
}
