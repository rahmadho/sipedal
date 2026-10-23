<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Realisasi\ViewBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Bangun materialisasi realisasi RUP setelah seluruh KLPD selesai (F11.8).
 */
class BuildRealisasiJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $timeout = 1800;

    public function handle(ViewBuilder $builder): array
    {
        return $builder->build();
    }
}
