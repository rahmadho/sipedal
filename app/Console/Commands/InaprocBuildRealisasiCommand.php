<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Realisasi\ViewBuilder;
use Illuminate\Console\Command;

/**
 * inaproc:build-realisasi — hitung ulang ref_rup_proses, ref_rup_warisan,
 * dan view v_realisasi_rup (F11.6–F11.8).
 */
class InaprocBuildRealisasiCommand extends Command
{
    protected $signature = 'inaproc:build-realisasi {--json : Keluarkan ringkasan sebagai JSON}';

    protected $description = 'Membangun ulang materialisasi realisasi RUP dan view';

    public function handle(ViewBuilder $builder): int
    {
        $this->info('Membangun realisasi RUP...');

        $result = $builder->build();
        $breakdown = $builder->breakdown();

        if ($this->option('json')) {
            $this->line((string) json_encode($result + ['rincian' => $breakdown], JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->table(['Tabel', 'Baris'], [
            ['ref_rup_proses', $result['proses']],
            ['ref_rup_warisan', $result['warisan']],
            ['v_realisasi_rup', $result['view']],
        ]);

        if ($breakdown !== []) {
            $rows = [];

            foreach ($breakdown as $sumber => $counts) {
                $rows[] = [$sumber, $counts['langsung'], $counts['warisan']];
            }

            $this->table(['Sumber', 'Langsung', 'Warisan'], $rows);
        }

        return self::SUCCESS;
    }
}
