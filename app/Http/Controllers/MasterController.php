<?php

namespace App\Http\Controllers;

use App\Support\Mock\Mock;
use App\Support\SatkerFilter;
use Illuminate\Http\Request;

class MasterController extends Controller
{
    public function klpd(Request $request)
    {
        $cari = trim((string) $request->query('cari', ''));
        $jenis = trim((string) $request->query('jenis', ''));

        $query = \App\Models\Klpd::query()
            ->with('credential')
            ->orderBy('kd_klpd');

        if ($cari !== '') {
            $query->where(function ($q) use ($cari) {
                $q->where('kd_klpd', 'ilike', "%{$cari}%")
                  ->orWhere('nama', 'ilike', "%{$cari}%")
                  ->orWhere('ibukota', 'ilike', "%{$cari}%")
                  ->orWhere('domain', 'ilike', "%{$cari}%");
            });
        }

        if ($jenis !== '') {
            $query->where('jenis', strtoupper($jenis));
        }

        $semua = $query->get();

        $baris = $semua->map(function ($k) {
            $tokenValid = $k->credential && !empty($k->credential->token_encrypted);
            return [
                'kd_klpd' => $k->kd_klpd,
                'kode' => $k->kd_klpd,
                'nama' => $k->nama,
                'alamat' => $k->alamat ?: '—',
                'ibukota' => $k->ibukota ?: '—',
                'jenis' => $k->jenis ?: 'KABUPATEN',
                'prp_id' => $k->prp_id,
                'kbp_id' => $k->kbp_id,
                'spse_id' => $k->spse_id,
                'spse_versi' => $k->spse_versi ?: '—',
                'spse_url' => $k->spse_url,
                'domain' => $k->domain ?: strtolower($k->kd_klpd) . '.sipedal.id',
                'tema' => $k->tema ?: 'biru',
                'status' => $k->status ?: 'aktif',
                'has_token' => $tokenValid,
            ];
        });

        return view('master.klpd', [
            'baris' => $baris,
            'cari' => $cari,
            'jenisTerpilih' => $jenis,
            'total' => $baris->count(),
        ]);
    }

    public function simpanTemaKlpd(Request $request)
    {
        $request->validate([
            'kode' => ['required', 'string'],
            'tema' => ['required', 'string'],
        ]);

        if (session('peran') !== 'super_admin') {
            return response()->json(['ok' => false, 'pesan' => 'Hanya Super Admin yang diizinkan mengubah tema KLPD.'], 403);
        }

        $klpd = \App\Models\Klpd::find($request->input('kode'));
        if ($klpd) {
            $klpd->tema = $request->input('tema');
            $klpd->save();
        }

        return response()->json(['ok' => true]);
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
