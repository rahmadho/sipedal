<?php

namespace App\Http\Controllers;

use App\Support\Mock\Mock;
use App\Support\SatkerFilter;
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

        $baris = Mock::satker($perHalaman, $offset);

        // Peran yang terikat satu satuan kerja hanya melihat satkernya.
        if (SatkerFilter::berlakunya($request->session()->all())) {
            $baris = SatkerFilter::terapkan($baris, (int) $request->session()->get('rup_stk_id'));
        }

        return view('master.satker', [
            'baris' => $baris,
            'halaman' => $halaman,
            'perHalaman' => $perHalaman,
            'total' => count($baris) === $perHalaman ? Mock::ACUAN['satker'] : count($baris),
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
