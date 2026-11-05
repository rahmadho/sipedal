<?php

namespace App\Providers;

use App\Support\Menu;
use App\Support\Tenant;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Membagikan data yang dibutuhkan seluruh tampilan aplikasi:
 * tenant aktif dan menu yang sudah disaring menurut peran.
 *
 * Ditempatkan di view composer (bukan middleware) karena menu bergantung
 * pada sesi yang baru tersedia setelah middleware sesi berjalan.
 */
class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $peran = session('peran');
            $rupStkId = session('rup_stk_id');
            $namaSatker = null;

            if ($rupStkId) {
                foreach (\App\Support\Mock\SatkerPilihan::semua() as $s) {
                    if ((int) $s['nilai'] === (int) $rupStkId) {
                        $namaSatker = $s['label'];
                        break;
                    }
                }
            }

            $bisaLintasKlpd = in_array($peran, ['super_admin'], true);
            $sesiKdKlpd = session('kd_klpd', app(Tenant::class)->kode());
            $namaKlpd = \App\Support\KlpdList::nama($sesiKdKlpd) ?? app(Tenant::class)->nama();

            $view->with([
                'menu' => $peran ? Menu::untuk($peran) : [],
                'tenant' => $view->getData()['tenant'] ?? app(Tenant::class),
                // Saringan satuan kerja aktif bila peran wajib punya rup_stk_id.
                'saringanSatker' => \App\Support\SatkerFilter::berlakunya(session()->all()),
                'rupStkId' => $rupStkId,
                'namaSatker' => $namaSatker,
                'sesiTahun' => (int) session('tahun', now()->year),
                'sesiBulan' => (int) session('bulan', now()->month),
                'sesiKdKlpd' => $sesiKdKlpd,
                'namaKlpd' => $namaKlpd,
                'bisaLintasKlpd' => $bisaLintasKlpd,
                'daftarKlpd' => \App\Support\KlpdList::semua(),
            ]);
        });
    }
}
