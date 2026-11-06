<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_settings')) {
            Schema::create('app_settings', function (Blueprint $table) {
                $table->id();
                $table->string('kd_klpd', 10)->default('GLOBAL')->index();
                $table->string('key', 64)->index();
                $table->text('value')->nullable();
                $table->timestamps();

                $table->unique(['kd_klpd', 'key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
