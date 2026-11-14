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
        $cari = trim((string) $request->query('cari', ''));
        $jenis = trim((string) $request->query('jenis', ''));

        $kdKlpd = strtoupper((string) session('kd_klpd', app(\App\Support\Tenant::class)->kode()));
        $tahun = (int) session('tahun', (int) date('Y'));

        // Ambil data satker ril dari DB
        $semua = \App\Support\SatkerRepository::daftarLengkap($kdKlpd, $tahun);
        $pakaiDataRil = !empty($semua);

        // Fallback ke mock hanya bila DB belum punya data sama sekali (mis. pada environment test)
        if (!$pakaiDataRil) {
            $semua = Mock::satker(Mock::ACUAN['satker'], 0);
        }

        // Peran yang terikat satu satuan kerja hanya melihat satkernya.
        if (SatkerFilter::berlakunya($request->session()->all())) {
            $semua = SatkerFilter::terapkan($semua, (int) $request->session()->get('rup_stk_id'));
        }

        // Filter pencarian
        if ($cari !== '') {
            $q = mb_strtolower($cari);
            $semua = array_values(array_filter($semua, static function (array $b) use ($q): bool {
                return str_contains(mb_strtolower((string) ($b['nama'] ?? '')), $q)
                    || str_contains(mb_strtolower((string) ($b['kd_satker'] ?? '')), $q)
                    || str_contains(mb_strtolower((string) ($b['kd_satker_str'] ?? '')), $q);
            }));
        }

        // Filter jenis
        if ($jenis !== '') {
            $semua = array_values(array_filter($semua, static function (array $b) use ($jenis): bool {
                return strcasecmp((string) ($b['jenis'] ?? ''), $jenis) === 0;
            }));
        }

        // Hitung KPI dari seluruh data terfilter
        $total = count($semua);
        $totalPagu = (int) array_sum(array_column($semua, 'pagu'));
        $totalRealisasi = (int) array_sum(array_column($semua, 'realisasi'));
        $totalPaket = (int) array_sum(array_column($semua, 'paket'));
        $rataSerapan = $totalPagu > 0
            ? round(($totalRealisasi / $totalPagu) * 100, 1)
            : 0.0;

        // Paginasi
        $offset = ($halaman - 1) * $perHalaman;
        $baris = array_slice($semua, $offset, $perHalaman);
        foreach ($baris as $idx => &$item) {
            $item['no'] = $offset + $idx + 1;
        }
        unset($item);

        return view('master.satker', [
            'baris' => $baris,
            'halaman' => $halaman,
            'perHalaman' => $perHalaman,
            'total' => $total,
            'totalPagu' => $totalPagu,
            'totalRealisasi' => $totalRealisasi,
            'totalPaket' => $totalPaket,
            'rataSerapan' => $rataSerapan,
            'cari' => $cari,
            'jenisTerpilih' => $jenis,
            'jenisOpsi' => \App\Support\SatkerRepository::JENIS_OPSI,
            'kdKlpd' => $kdKlpd,
            'tahun' => $tahun,
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
