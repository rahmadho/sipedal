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

        if ($jenis === 'rup') {
            return $this->pantauRup($request, $daftar);
        }

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

    private function pantauRup(Request $request, array $daftar)
    {
        $kdKlpd = strtoupper((string) session('kd_klpd', app(\App\Support\Tenant::class)->kode()));
        $tahun = (int) session('tahun', (int) date('Y'));

        // Saringan satker jika peran terikat satker
        $satkerId = null;
        if (\App\Support\SatkerFilter::berlakunya($request->session()->all())) {
            $satkerId = (int) $request->session()->get('rup_stk_id');
        }

        // 1. Ringkasan kartu-kartu
        $ringkasan = \App\Support\RupRepository::ringkasan($kdKlpd, $tahun, $satkerId);

        // 2. Tabel Rekap per Satuan Kerja
        $cariSatker = trim((string) $request->query('cari_satker', ''));
        $rekapSatker = \App\Support\RupRepository::rekapSatker($kdKlpd, $tahun, $cariSatker !== '' ? $cariSatker : null, $satkerId);

        // 3. Tabel Paket (dibagi 2: penyedia & swakelola)
        $tab = $request->query('tab', 'penyedia');
        if (! in_array($tab, ['penyedia', 'swakelola'], true)) {
            $tab = 'penyedia';
        }

        $halaman = max(1, (int) $request->query('halaman', 1));
        $perHalaman = 25;
        $offset = ($halaman - 1) * $perHalaman;

        $filters = [
            'cari' => trim((string) $request->query('cari', '')),
            'satker' => $satkerId ?: $request->query('satker'),
            'tipe_paket' => $request->query('tipe_paket'),
            'status_pdn' => $request->query('status_pdn'),
            'status_proses' => $request->query('status_proses'),
            'jenis_pengadaan' => $request->query('jenis_pengadaan'),
            'tipe_swakelola' => $request->query('tipe_swakelola'),
        ];

        if ($tab === 'swakelola') {
            $hasilPaket = \App\Support\RupRepository::daftarSwakelola($kdKlpd, $tahun, $filters, $perHalaman, $offset);
        } else {
            $hasilPaket = \App\Support\RupRepository::daftarPenyedia($kdKlpd, $tahun, $filters, $perHalaman, $offset);
        }

        return view('report.pantau-rup', [
            'jenis' => 'rup',
            'meta' => $daftar['rup'],
            'daftar' => $daftar,
            'kdKlpd' => $kdKlpd,
            'tahun' => $tahun,
            'ringkasan' => $ringkasan,
            'rekapSatker' => $rekapSatker,
            'cariSatker' => $cariSatker,
            'tab' => $tab,
            'baris' => $hasilPaket['items'],
            'total' => $hasilPaket['total'],
            'halaman' => $halaman,
            'perHalaman' => $perHalaman,
            'filters' => $filters,
            'satkerPilihan' => \App\Support\Mock\SatkerPilihan::semua(600, $kdKlpd),
            'jenisOpsi' => array_column($ringkasan['jenis_pengadaan'], 'jenis'),
        ]);
    }
}
