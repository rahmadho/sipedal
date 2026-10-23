<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penjaga sesi tahap UI/UX.
 *
 * Belum ada basis data, jadi autentikasi hanya berupa penanda di sesi.
 * JANGAN dipakai di produksi: tidak ada verifikasi kata sandi, tidak ada
 * token, tidak ada audit. Diganti dengan guard Laravel pada tahap API.
 */
class AuthDemo
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('peran')) {
            return redirect()->route('login', $request->query());
        }

        return $next($request);
    }
}
