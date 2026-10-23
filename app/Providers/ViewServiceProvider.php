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
        View::composer(['layouts.app', 'auth.login'], function ($view) {
            $peran = session('peran');

            $view->with([
                'menu' => $peran ? Menu::untuk($peran) : [],
                'tenant' => $view->getData()['tenant'] ?? app(Tenant::class),
            ]);
        });
    }
}
