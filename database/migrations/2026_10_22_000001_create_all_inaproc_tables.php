<?php

declare(strict_types=1);

use App\Models\Endpoint;
use App\Services\Inaproc\EndpointRegistry;
use App\Services\Pull\SchemaManager;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Buat seluruh tabel penampung data tarikan INAPROC (rec_*) di schema inaproc.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS inaproc');

        $registry = app(EndpointRegistry::class);

        // Sumber tabel diambil dari daftar endpoint seeder agar tidak bergantung pada isi database
        $endpoints = \Database\Seeders\EndpointSeeder::ENDPOINTS;
        foreach ($endpoints as $ep) {
            $tableName = EndpointRegistry::tableNameFor($ep['api'], $ep['grup'], $ep['name']);
            $this->createTableIfNotExists($tableName);
        }
    }

    private function createTableIfNotExists(string $table): void
    {
        $columns = [
            '_row_id BIGSERIAL PRIMARY KEY',
            '_kode_klpd VARCHAR(16) NOT NULL',
            '_tahun SMALLINT NOT NULL',
            '_source VARCHAR(128)',
            '_fetched_at TIMESTAMPTZ NOT NULL DEFAULT now()',
        ];

        DB::statement(sprintf(
            'CREATE TABLE IF NOT EXISTS inaproc.%s (%s)',
            $table,
            implode(', ', $columns)
        ));

        DB::statement(sprintf(
            'CREATE INDEX IF NOT EXISTS %s ON inaproc.%s (_kode_klpd, _tahun)',
            $table . '_kode_klpd_tahun_index',
            $table
        ));
    }

    public function down(): void
    {
        // Tabel penampung data tidak di-drop otomatis saat rollback
    }
};
