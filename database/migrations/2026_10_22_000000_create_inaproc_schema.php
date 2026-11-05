<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Buat schema inaproc untuk menampung tabel-tabel mentah tarikan data API (rec_*).
 * Tabel non-tarikan data (users, session, pull_log, endpoints, dsb) tetap di schema public.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS inaproc');
    }

    public function down(): void
    {
        // Jangan drop schema inaproc karena berisi data riil
    }
};
