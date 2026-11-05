<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration tabel users sesuai DDL pengguna (idempotent).
 *
 * Catatan peran & satuan kerja:
 * - username UNIQUE.
 * - email NULLABLE dan TIDAK unik.
 * - rup_stk_id adalah kode satuan kerja (nullable, indexed untuk filter data).
 * - Tidak ada kolom role/peran di skema ini (otorisasi diatur per sesi / pivot terpisah).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 255);
                $table->string('username', 255)->unique('users_username_unique');
                $table->string('email', 255)->nullable();
                $table->timestamp('email_verified_at', 0)->nullable();
                $table->string('password', 255);
                $table->rememberToken();
                $table->timestamps(0);
                $table->string('kd_klpd', 5);
                $table->integer('rup_stk_id')->nullable();
                $table->string('nip', 255)->nullable();
                $table->string('pangkat', 50)->nullable();
                $table->string('golongan', 50)->nullable();
                $table->string('jabatan', 200)->nullable();
                $table->string('no_hp', 20)->nullable();
                $table->string('avatar', 255)->nullable();
                $table->string('no_telp', 255)->nullable();

                $table->index('rup_stk_id', 'users_rup_stk_id_idx');
                $table->index('kd_klpd', 'users_kd_klpd_idx');
            });
        }
    }

    public function down(): void
    {
        // Skema bersama, tidak di-drop saat rollback sembarangan
    }
};
