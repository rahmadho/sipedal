<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('klpds')) {
            Schema::create('klpds', function (Blueprint $table) {
                $table->string('kd_klpd', 16)->primary();
                $table->string('nama', 255);
                $table->text('alamat')->nullable();
                $table->string('ibukota', 128)->nullable();
                $table->string('jenis', 32)->nullable(); // PROVINSI, KABUPATEN, KOTA
                $table->string('prp_id', 16)->nullable();
                $table->string('kbp_id', 16)->nullable();
                $table->string('spse_id', 16)->nullable();
                $table->string('spse_versi', 32)->nullable();
                $table->string('spse_url', 255)->nullable();
                $table->string('domain', 128)->nullable();
                $table->string('tema', 32)->default('biru');
                $table->string('status', 16)->default('aktif');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('klpds');
    }
};
