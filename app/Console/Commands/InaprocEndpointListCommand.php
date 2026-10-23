<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Inaproc\EndpointRegistry;
use Illuminate\Console\Command;

/**
 * inaproc:endpoints — daftar registry endpoint beserta nama tabel turunan (F1).
 */
class InaprocEndpointListCommand extends Command
{
    protected $signature = 'inaproc:endpoints
        {--grup= : Filter per grup}
        {--only-active : Hanya endpoint aktif}
        {--json : Keluarkan JSON}';

    protected $description = 'Menampilkan registry endpoint dan nama tabelnya';

    public function handle(EndpointRegistry $registry): int
    {
        $endpoints = $registry->all((bool) $this->option('only-active'));

        if (($group = $this->option('grup')) !== null && $group !== '') {
            $endpoints = $endpoints->filter(fn ($e) => $e->grup === $group)->values();
        }

        if ($this->option('json')) {
            $this->line((string) json_encode($endpoints->map(fn ($e) => [
                'name' => $e->name,
                'path' => $e->path,
                'api' => $e->api,
                'grup' => $e->grup,
                'scope' => $e->scope,
                'active' => $e->active,
                'urutan' => $e->urutan,
                'tabel' => $e->tableName(),
            ])->all(), JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $rows = $endpoints->map(fn ($e) => [
            $e->active ? 'ya' : 'tidak',
            $e->name,
            $e->api,
            $e->grup,
            $e->scope,
            $e->tableName(),
        ])->all();

        $this->table(['Aktif', 'Nama', 'API', 'Grup', 'Scope', 'Tabel'], $rows);
        $this->line(sprintf(
            '%d endpoint, %d aktif.',
            $endpoints->count(),
            $endpoints->where('active', true)->count(),
        ));

        return self::SUCCESS;
    }
}
