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
| Penjadwalan Dinamis.
| Dibaca langsung dari SchedulerConfig (dapat diubah dari menu Pengaturan).
| Aktifkan worker di server lewat `php artisan schedule:work` atau crontab `schedule:run`.
*/
$scheduler = \App\Support\SchedulerConfig::get();

if ($scheduler['enabled'] ?? false) {
    $jobs = $scheduler['jobs'] ?? [];

    // 1. Tarik Data INAPROC
    if (!empty($jobs['pull']['enabled']) && !empty($jobs['pull']['times'])) {
        foreach ($jobs['pull']['times'] as $time) {
            $tahun = now()->year;
            Schedule::command("inaproc:pull --all --tahun={$tahun} --sync")
                ->dailyAt($time)
                ->withoutOverlapping()
                ->onOneServer()
                ->description("Tarik harian seluruh KLPD ({$time} WIB)");
        }
    }

    // 2. Build Realisasi
    if (!empty($jobs['build_realisasi']['enabled']) && !empty($jobs['build_realisasi']['times'])) {
        foreach ($jobs['build_realisasi']['times'] as $time) {
            Schedule::command('inaproc:build-realisasi')
                ->dailyAt($time)
                ->withoutOverlapping()
                ->onOneServer()
                ->description("Bangun ulang realisasi RUP ({$time} WIB)");
        }
    }

    // 3. Validasi Token
    if (!empty($jobs['validate_tokens']['enabled']) && !empty($jobs['validate_tokens']['times'])) {
        foreach ($jobs['validate_tokens']['times'] as $time) {
            Schedule::command('inaproc:validate-tokens')
                ->dailyAt($time)
                ->withoutOverlapping()
                ->onOneServer()
                ->description("Uji token seluruh KLPD ({$time} WIB)");
        }
    }
}
