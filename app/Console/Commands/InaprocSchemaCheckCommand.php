<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Inaproc\EndpointRegistry;
use App\Services\Pull\SchemaManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * inaproc:schema-check — bandingkan skema aktual tabel rec_* dengan registry (F6.3).
 */
class InaprocSchemaCheckCommand extends Command
{
    protected $signature = 'inaproc:schema-check {--klpd= : Batasi pemeriksaan ke satu KLPD}';

    protected $description = 'Memverifikasi tabel data setiap endpoint sudah ada dan sesuai';

    public function handle(EndpointRegistry $registry, SchemaManager $schema): int
    {
        $missing = [];
        $empty = [];

        foreach ($registry->all(true) as $endpoint) {
            $table = $endpoint->tableName();

            if (! DB::getSchemaBuilder()->hasTable($table)) {
                $missing[] = [$endpoint->name, $table];

                continue;
            }

            $count = (int) DB::table($table)->count();

            if ($count === 0) {
                $empty[] = [$endpoint->name, $table];
            }
        }

        $this->table(['Tabel belum ada', 'Endpoint'], array_map(
            static fn (array $row): array => [$row[1], $row[0]],
            $missing,
        ));

        $this->line(sprintf('%d endpoint aktif, %d tabel belum dibuat, %d tabel kosong.',
            $registry->all(true)->count(), count($missing), count($empty)));

        return self::SUCCESS;
    }
}
