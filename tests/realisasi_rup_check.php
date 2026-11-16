<?php

declare(strict_types=1);

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';
} else {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
}
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo '== RUNNING REALISASI RUP ASSERT CHECK ==' . PHP_EOL;

// 1. Komposisi Sumber
$komposisi = \App\Support\RealisasiRepository::komposisiSumber('D462', 2026);
assert(count($komposisi) === 4, 'Komposisi sumber must have 4 items');
$ekatalog = null;
foreach ($komposisi as $k) {
    if ($k['sumber'] === 'e-katalog') {
        $ekatalog = $k;
        break;
    }
}
assert($ekatalog !== null, 'E-katalog not found in komposisi');
assert($ekatalog['total'] > 0, 'E-katalog total must be > 0');
echo '✓ Komposisi sumber realisasi OK (E-Katalog total: ' . $ekatalog['total'] . ', pagu: ' . $ekatalog['pagu'] . ')' . PHP_EOL;

// 2. Daftar Realisasi Query
$daftar = \App\Support\RealisasiRepository::daftar('D462', 2026, [], 10, 0);
assert($daftar['total'] > 0, 'Daftar total must be > 0');
assert(count($daftar['items']) === 10, 'Daftar items count mismatch');
assert(!empty($daftar['items'][0]['kd_rup']), 'Item missing kd_rup');
echo '✓ Query daftar realisasi OK (Total: ' . $daftar['total'] . ' items)' . PHP_EOL;

// 3. HTTP GET /realisasi
$httpKernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$request = \Illuminate\Http\Request::create('/realisasi', 'GET');
$session = app('session')->driver();
$session->setId('check-session-realisasi');
$session->start();
$session->put([
    'peran' => 'super_admin',
    'nama' => 'Super Administrator',
    'username' => 'admin',
    'kd_klpd' => 'D462',
    'tahun' => 2026,
]);
$session->save();
$request->setLaravelSession($session);
$response = $httpKernel->handle($request);
assert($response->getStatusCode() === 200, 'HTTP GET /realisasi status not 200');
$content = $response->getContent();
assert(str_contains($content, 'dt-realisasi'), 'View does not contain dt-realisasi');
assert(str_contains($content, 'Daftar Realisasi RUP'), 'View missing header text');
echo '✓ HTTP GET /realisasi returns 200 OK' . PHP_EOL;

// 4. AJAX Server-Side DataTable Request
$ajaxReq = \Illuminate\Http\Request::create('/realisasi?per_halaman=10&sort=pagu&arah=desc&sumber=e-katalog', 'GET');
$ajaxReq->headers->set('X-Requested-With', 'XMLHttpRequest');
$ajaxReq->headers->set('Accept', 'application/json');
$ajaxReq->setLaravelSession($session);
$ajaxRes = $httpKernel->handle($ajaxReq);
assert($ajaxRes->getStatusCode() === 200, 'AJAX /realisasi status not 200');
$json = json_decode($ajaxRes->getContent(), true);
assert(isset($json['html_tabel']) && !empty($json['html_tabel']), 'AJAX missing html_tabel');
assert(isset($json['html_paginasi']) && !empty($json['html_paginasi']), 'AJAX missing html_paginasi');
assert($json['perHalaman'] === 10, 'perHalaman mismatch');
assert(str_contains($json['html_tabel'], 'E-Katalog'), 'html_tabel missing E-Katalog badge');
echo '✓ AJAX DataTable Server-Side /realisasi OK (Total: ' . $json['total'] . ' paket)' . PHP_EOL;

echo '== ALL REALISASI RUP CHECKS PASSED ==' . PHP_EOL;
