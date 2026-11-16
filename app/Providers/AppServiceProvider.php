<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Ponytail: fallback tenant jika ResolveTenant middleware belum sempat jalan (mis. saat error handler render view).
        $this->app->singleton(\App\Support\Tenant::class, function () {
            return \App\Support\Tenant::dariKunci(config('sipedal.default_tenant', 'd462'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
