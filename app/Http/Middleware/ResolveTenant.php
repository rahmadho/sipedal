<?php

namespace App\Http\Middleware;

use App\Support\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menentukan KLPD aktif untuk setiap permintaan, lalu membagikannya ke
 * seluruh tampilan. Urutan penentuan:
 *
 *   1. ?klpd=  — hanya dihormati bila pengguna berperan Super Admin
 *   2. host permintaan (klpdA.domain.com)
 *   3. tenant default dari config
 */
class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->tentukan($request);

        app()->instance(Tenant::class, $tenant);
        view()->share('tenant', $tenant);

        return $next($request);
    }

    private function tentukan(Request $request): Tenant
    {
        // 1. Pemaksaan lewat query string — khusus Super Admin.
        $paksa = $request->query('klpd');
        if (is_string($paksa) && $paksa !== '' && $this->penggunaSuperAdmin($request)) {
            $tenant = Tenant::dariKunci($paksa);
            $request->session()->put('klpd_paksa', $tenant->kunci());

            return $tenant;
        }

        // 2. Pilihan yang tersimpan di sesi (dari kunjungan Super Admin sebelumnya).
        $tersimpan = $request->session()->get('klpd_paksa');
        if (is_string($tersimpan) && $tersimpan !== '' && $this->penggunaSuperAdmin($request)) {
            $dariSesi = Tenant::dariKunci($tersimpan);

            // Bila host dikenali dan berbeda, host tetap menang — kecuali memang
            // sedang memaksa lewat query string di atas.
            $dariHost = Tenant::dariHost($request->getHost());
            if ($dariHost->kunci() === config('sipedal.default_tenant')) {
                return $dariSesi;
            }

            return $dariHost;
        }

        // 3. Host permintaan.
        return Tenant::dariHost($request->getHost());
    }

    private function penggunaSuperAdmin(Request $request): bool
    {
        return $request->session()->get('peran') === 'super_admin';
    }
}
