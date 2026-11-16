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

echo '== RUNNING PANTAU RUP ASSERT CHECK ==' . PHP_EOL;

// 1. Ringkasan
$ringkasan = \App\Support\RupRepository::ringkasan('D462', 2026);
assert(isset($ringkasan['pdn']['pdn']['jumlah']), 'PDN jumlah missing');
assert(isset($ringkasan['tipe_paket']['penyedia']['diproses']), 'Tipe paket diproses missing');
assert(isset($ringkasan['swakelola']['tipe'][1]), 'Swakelola tipe 1 missing');
assert(count($ringkasan['jenis_pengadaan']) > 0, 'Jenis pengadaan empty');
echo '✓ Ringkasan data OK (PDN: ' . $ringkasan['pdn']['pdn']['jumlah'] . ', Swakelola: ' . $ringkasan['swakelola']['total']['jumlah'] . ')' . PHP_EOL;

// 2. Satker
$satker = \App\Support\RupRepository::rekapSatker('D462', 2026);
assert(count($satker['baris']) > 0, 'Satker baris empty');
assert(isset($satker['grand_total']['total']['pagu']), 'Grand total missing');
echo '✓ Rekap satker OK (' . count($satker['baris']) . ' satker, grand total: ' . $satker['grand_total']['total']['paket'] . ' paket)' . PHP_EOL;

// 3. Penyedia list
$penyedia = \App\Support\RupRepository::daftarPenyedia('D462', 2026, [], 10, 0);
assert($penyedia['total'] > 0, 'Penyedia total 0');
assert(count($penyedia['items']) === 10, 'Penyedia items count mismatch');
echo '✓ Daftar penyedia OK (' . $penyedia['total'] . ' total)' . PHP_EOL;

// 4. Swakelola list
$swakelola = \App\Support\RupRepository::daftarSwakelola('D462', 2026, [], 10, 0);
assert($swakelola['total'] > 0, 'Swakelola total 0');
assert(count($swakelola['items']) === 10, 'Swakelola items count mismatch');
echo '✓ Daftar swakelola OK (' . $swakelola['total'] . ' total)' . PHP_EOL;

// 5. HTTP 200 normal check
$httpKernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$request = \Illuminate\Http\Request::create('/report/pantau/rup', 'GET');
$session = app('session')->driver();
$session->setId('check-session');
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
assert($response->getStatusCode() === 200, 'HTTP status not 200');
echo '✓ HTTP GET /report/pantau/rup returns 200 OK' . PHP_EOL;

// 6. AJAX DataTables serverside check (nama proses + sorting + json)
$ajaxReq = \Illuminate\Http\Request::create('/report/pantau/rup?status_proses=e-katalog&sort=pagu&arah=desc', 'GET');
$ajaxReq->headers->set('X-Requested-With', 'XMLHttpRequest');
$ajaxReq->headers->set('Accept', 'application/json');
$ajaxReq->setLaravelSession($session);
$ajaxRes = $httpKernel->handle($ajaxReq);
assert($ajaxRes->getStatusCode() === 200, 'AJAX status not 200');
$json = json_decode($ajaxRes->getContent(), true);
assert(isset($json['html_tabel']) && !empty($json['html_tabel']), 'AJAX missing html_tabel');
assert(isset($json['html_paginasi']) && !empty($json['html_paginasi']), 'AJAX missing html_paginasi');
assert(str_contains($json['html_tabel'], 'E-Katalog'), 'AJAX table does not display E-Katalog label');
assert($json['total'] === 1343, 'AJAX total filter e-katalog mismatch');
echo '✓ AJAX DataTables serverside OK (E-Katalog: ' . $json['total'] . ' paket)' . PHP_EOL;

// 7. Check per_halaman support
$dtReq = \Illuminate\Http\Request::create('/report/pantau/rup?per_halaman=50', 'GET');
$dtReq->headers->set('X-Requested-With', 'XMLHttpRequest');
$dtReq->headers->set('Accept', 'application/json');
$dtReq->setLaravelSession($session);
$dtRes = $httpKernel->handle($dtReq);
assert($dtRes->getStatusCode() === 200, 'per_halaman AJAX status not 200');
$dtJson = json_decode($dtRes->getContent(), true);
assert($dtJson['perHalaman'] === 50, 'perHalaman not 50');
assert(str_contains($dtJson['html_paginasi'], 'Menampilkan'), 'html_paginasi missing info');
echo '✓ Per-halaman settings OK (perHalaman = 50)' . PHP_EOL;

echo '== ALL CHECKS PASSED ==' . PHP_EOL;
