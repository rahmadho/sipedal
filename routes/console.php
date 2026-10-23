<?php

use App\Console\Commands\InaprocBuildRealisasiCommand;
use App\Console\Commands\InaprocEndpointListCommand;
use App\Console\Commands\InaprocProbeCommand;
use App\Console\Commands\InaprocPullCommand;
use App\Console\Commands\InaprocSchemaCheckCommand;
use App\Console\Commands\InaprocValidateTokensCommand;
use App\Jobs\BuildRealisasiJob;
use App\Jobs\PullKlpdJob;
use App\Services\Pull\KlpdCredentials;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console routes
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment('Data yang teramati adalah data yang dipercaya.');
})->purpose('Tampilkan kutipan penyemangat');

/*
| Penjadwalan (F8). Tarik berkala tanpa intervensi.
| Aktifkan lewat `php artisan schedule:work` atau cron.
*/
Schedule::command('inaproc:pull --all --tahun='.now()->year.' --sync')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->description('Tarik harian seluruh KLPD terdaftar');

Schedule::job(new BuildRealisasiJob)
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->description('Bangun ulang realisasi RUP setelah tarik harian');

Schedule::command('inaproc:validate-tokens')
    ->weeklyOn(1, '06:00')
    ->description('Periksa validitas token KLPD');
