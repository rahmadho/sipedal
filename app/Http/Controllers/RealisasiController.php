<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Mock\SatkerPilihan;
use App\Support\RealisasiRepository;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RealisasiController extends Controller
{
    /**
     * Menampilkan halaman Realisasi RUP dengan data riil INAPROC dan server-side DataTable.
     */
    public function index(Request $request): View|JsonResponse
    {
        $tenant = app(Tenant::class);
        $kdKlpd = (string) ($request->session()->get('kd_klpd') ?: $tenant->kode());
        $tahun = (int) ($request->session()->get('tahun') ?: 2026);
        $satkerId = $request->session()->get('rup_stk_id') ? (int) $request->session()->get('rup_stk_id') : null;

        // KPI Ringkasan komposisi sumber realisasi
        $komposisi = RealisasiRepository::komposisiSumber($kdKlpd, $tahun, $satkerId);

        // Parameter DataTable & Pagination
        $halaman = max(1, (int) $request->query('halaman', 1));
        $perHalaman = max(5, min(100, (int) $request->query('per_halaman', 25)));
        $offset = ($halaman - 1) * $perHalaman;

        $filters = [
            'cari' => trim((string) $request->query('cari', '')),
            'satker' => $satkerId ?: $request->query('satker'),
            'sumber' => $request->query('sumber'),
            'jenis' => $request->query('jenis'),
            'sort' => (string) $request->query('sort', 'kd_rup'),
            'arah' => strtolower((string) $request->query('arah', 'desc')) === 'asc' ? 'asc' : 'desc',
        ];

        $hasil = RealisasiRepository::daftar($kdKlpd, $tahun, $filters, $perHalaman, $offset);

        // Respons AJAX Server-Side DataTable
        if ($request->ajax() || $request->wantsJson() || $request->query('ajax')) {
            $htmlTabel = view('realisasi.partials.tabel-realisasi', [
                'baris' => $hasil['items'],
                'sort' => $filters['sort'],
                'arah' => $filters['arah'],
            ])->render();

            $htmlPaginasi = view('components.paginasi', [
                'halaman' => $halaman,
                'perHalaman' => $perHalaman,
                'total' => $hasil['total'],
            ])->render();

            return response()->json([
                'success' => true,
                'total' => $hasil['total'],
                'halaman' => $halaman,
                'perHalaman' => $perHalaman,
                'sort' => $filters['sort'],
                'arah' => $filters['arah'],
                'html_tabel' => $htmlTabel,
                'html_paginasi' => $htmlPaginasi,
            ]);
        }

        return view('realisasi.index', [
            'komposisi' => $komposisi,
            'baris' => $hasil['items'],
            'total' => $hasil['total'],
            'halaman' => $halaman,
            'perHalaman' => $perHalaman,
            'filters' => $filters,
            'sumber' => RealisasiRepository::DAFTAR_SUMBER,
            'satkerPilihan' => SatkerPilihan::semua(600, $kdKlpd),
            'kdKlpd' => $kdKlpd,
            'tahun' => $tahun,
        ]);
    }
}
