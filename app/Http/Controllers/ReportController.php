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

        $tabel = (string) $request->query('tabel', 'paket');

        // 2. Tabel Rekap per Satuan Kerja (DataTable)
        $satkerFilters = [
            'cari' => trim((string) $request->query($tabel === 'satker' ? 'cari' : 'cari_satker', '')),
            'sort' => (string) $request->query($tabel === 'satker' ? 'sort' : 'sort_satker', 'total_pagu'),
            'arah' => strtolower((string) $request->query($tabel === 'satker' ? 'arah' : 'arah_satker', 'desc')) === 'asc' ? 'asc' : 'desc',
            'per_halaman' => max(5, min(100, (int) $request->query($tabel === 'satker' ? 'per_halaman' : 'per_halaman_satker', 10))),
            'halaman' => max(1, (int) $request->query($tabel === 'satker' ? 'halaman' : 'halaman_satker', 1)),
        ];

        $rekapSatker = \App\Support\RupRepository::rekapSatker(
            $kdKlpd,
            $tahun,
            $satkerFilters['cari'] !== '' ? $satkerFilters['cari'] : null,
            $satkerId,
            $satkerFilters['sort'],
            $satkerFilters['arah'],
            $satkerFilters['per_halaman'],
            $satkerFilters['halaman']
        );

        // 3. Tabel Paket (dibagi 2: penyedia & swakelola)
        $tab = $request->query('tab', 'penyedia');
        if (! in_array($tab, ['penyedia', 'swakelola'], true)) {
            $tab = 'penyedia';
        }

        $halaman = max(1, (int) $request->query('halaman', 1));
        $perHalaman = max(5, min(100, (int) $request->query('per_halaman', 25)));
        $offset = ($halaman - 1) * $perHalaman;

        $satkerInput = $satkerId ?: $request->input('satker');
        if (is_string($satkerInput) && str_contains($satkerInput, ',')) {
            $satkerInput = explode(',', $satkerInput);
        }

        $filters = [
            'cari' => trim((string) $request->query('cari', '')),
            'satker' => $satkerInput,
            'sort' => (string) $request->query('sort', 'kd_rup'),
            'arah' => strtolower((string) $request->query('arah', 'desc')) === 'asc' ? 'asc' : 'desc',
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

        // Respons AJAX / Server-side DataTable (tanpa reload halaman)
        if ($request->ajax() || $request->wantsJson() || $request->query('ajax')) {
            if ($tabel === 'satker') {
                $htmlTabel = view('report.partials.tabel-rekap-satker', [
                    'baris' => $rekapSatker['baris'],
                    'grandTotal' => $rekapSatker['grand_total'],
                    'sort' => $satkerFilters['sort'],
                    'arah' => $satkerFilters['arah'],
                    'halaman' => $satkerFilters['halaman'],
                    'perHalaman' => $satkerFilters['per_halaman'],
                    'tab' => $tab,
                ])->render();

                $htmlPaginasi = view('components.paginasi', [
                    'halaman' => $satkerFilters['halaman'],
                    'perHalaman' => $satkerFilters['per_halaman'],
                    'total' => $rekapSatker['total'],
                ])->render();

                return response()->json([
                    'success' => true,
                    'tabel' => 'satker',
                    'total' => $rekapSatker['total'],
                    'halaman' => $satkerFilters['halaman'],
                    'perHalaman' => $satkerFilters['per_halaman'],
                    'sort' => $satkerFilters['sort'],
                    'arah' => $satkerFilters['arah'],
                    'html_tabel' => $htmlTabel,
                    'html_paginasi' => $htmlPaginasi,
                ]);
            }

            $htmlTabel = view('report.partials.tabel-paket-rup', [
                'tab' => $tab,
                'baris' => $hasilPaket['items'],
                'sort' => $filters['sort'],
                'arah' => $filters['arah'],
            ])->render();

            $htmlPaginasi = view('components.paginasi', [
                'halaman' => $halaman,
                'perHalaman' => $perHalaman,
                'total' => $hasilPaket['total'],
            ])->render();

            return response()->json([
                'success' => true,
                'tabel' => 'paket',
                'tab' => $tab,
                'total' => $hasilPaket['total'],
                'halaman' => $halaman,
                'perHalaman' => $perHalaman,
                'sort' => $filters['sort'],
                'arah' => $filters['arah'],
                'html_tabel' => $htmlTabel,
                'html_paginasi' => $htmlPaginasi,
            ]);
        }

        return view('report.pantau-rup', [
            'jenis' => 'rup',
            'meta' => $daftar['rup'],
            'daftar' => $daftar,
            'kdKlpd' => $kdKlpd,
            'tahun' => $tahun,
            'ringkasan' => $ringkasan,
            'rekapSatker' => $rekapSatker,
            'satkerFilters' => $satkerFilters,
            'cariSatker' => $satkerFilters['cari'],
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
