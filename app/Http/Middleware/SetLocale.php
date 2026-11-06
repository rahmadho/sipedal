<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $bahasa = session('bahasa');

        if (!$bahasa) {
            $kdKlpd = session('kd_klpd');
            $bahasa = \App\Models\AppSetting::get('bahasa', $kdKlpd, 'id');
            session(['bahasa' => $bahasa]);
        }

        App::setLocale($bahasa === 'en' ? 'en' : 'id');

        return $next($request);
    }
}
