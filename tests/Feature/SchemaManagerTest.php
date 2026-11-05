<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Pull\SchemaManager;
use App\Services\Pull\TableWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * F6.2, F6.3 — kolom sistem, evolusi skema, penulisan transaksional.
 */
class SchemaManagerTest extends TestCase
{
    use RefreshDatabase;

    private function schema(): SchemaManager
    {
        return app(SchemaManager::class);
    }

    public function test_tabel_baru_dibuat_otomatis_dengan_kolom_sistem(): void
    {
        $this->schema()->ensureTable('rec_uji_baru', [['kd_rup' => 1, 'nama_paket' => 'x']]);

        foreach (['_row_id', '_kode_klpd', '_tahun', '_source', '_fetched_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('inaproc.rec_uji_baru', $column), $column);
        }

        $this->assertTrue(Schema::hasColumn('inaproc.rec_uji_baru', 'kd_rup'));
    }

    public function test_kolom_baru_muncul_di_tarikan_berikutnya(): void
    {
        $this->schema()->ensureTable('rec_uji_baru', [['kd_rup' => 1]]);
        $this->assertFalse(Schema::hasColumn('inaproc.rec_uji_baru', 'nama_paket'));

        $this->schema()->ensureTable('rec_uji_baru', [['kd_rup' => 1, 'nama_paket' => 'x']]);
        $this->assertTrue(Schema::hasColumn('inaproc.rec_uji_baru', 'nama_paket'));
    }

    public function test_tipe_hanya_meluas(): void
    {
        $this->schema()->ensureTable('rec_uji_tipe', [['nilai' => 10]]);
        $this->assertSame('bigint', $this->schema()->existingColumns('rec_uji_tipe')['nilai']);

        // Desimal → numeric
        $this->schema()->ensureTable('rec_uji_tipe', [['nilai' => 10.5]]);
        $this->assertSame('numeric', $this->schema()->existingColumns('rec_uji_tipe')['nilai']);

        // Teks → text (tidak menyempit kembali)
        $this->schema()->ensureTable('rec_uji_tipe', [['nilai' => 'sepuluh']]);
        $this->assertSame('text', $this->schema()->existingColumns('rec_uji_tipe')['nilai']);

        $this->schema()->ensureTable('rec_uji_tipe', [['nilai' => 10]]);
        $this->assertSame('text', $this->schema()->existingColumns('rec_uji_tipe')['nilai']);
    }

    public function test_inferensi_tipe(): void
    {
        $schema = $this->schema();

        $this->assertSame('bigint', $schema->inferType(10));
        $this->assertSame('numeric', $schema->inferType(10.5));
        $this->assertSame('boolean', $schema->inferType(true));
        $this->assertSame('timestamptz', $schema->inferType('2026-10-22T10:00:00+07:00'));
        $this->assertSame('text', $schema->inferType('halo'));
    }

    public function test_kolom_bentrok_diberi_prefiks_api(): void
    {
        $this->assertSame('api_kode_klpd', $this->schema()->normalizeColumnName('_kode_klpd'));
        $this->assertSame('api_tahun', $this->schema()->normalizeColumnName('_tahun'));
        $this->assertSame('nama_paket', $this->schema()->normalizeColumnName('Nama Paket'));
    }

    public function test_nama_kolom_dinormalisasi(): void
    {
        $this->assertSame('kd_rup', $this->schema()->normalizeColumnName('KD RUP'));
        $this->assertSame('a_b', $this->schema()->normalizeColumnName('  a___b  '));
    }

    public function test_tarik_ulang_tidak_duplikasi_dan_isolasi_klpd(): void
    {
        $writer = app(TableWriter::class);
        $rows = [['kd_rup' => 1], ['kd_rup' => 2]];

        $writer->replace('rec_uji_tulis', 'D462', 2026, $rows);
        $writer->replace('rec_uji_tulis', 'D462', 2026, $rows);
        $this->assertSame(2, $writer->countFor('rec_uji_tulis', 'D462', 2026));

        // KLPD lain tidak tersentuh.
        $writer->replace('rec_uji_tulis', 'D463', 2026, [['kd_rup' => 9]]);
        $writer->replace('rec_uji_tulis', 'D462', 2026, [['kd_rup' => 1]]);

        $this->assertSame(1, $writer->countFor('rec_uji_tulis', 'D462', 2026));
        $this->assertSame(1, $writer->countFor('rec_uji_tulis', 'D463', 2026));
    }

    public function test_kegagalan_insert_di_tengah_tidak_mengubah_isi_tabel(): void
    {
        $writer = app(TableWriter::class);

        $writer->replace('rec_uji_gagal', 'D462', 2026, [['kd_rup' => 1], ['kd_rup' => 2]]);
        $before = $writer->countFor('rec_uji_gagal', 'D462', 2026);

        // Paksa kegagalan di tengah penulisan: CHECK constraint membuat baris
        // kedua ditolak, sehingga seluruh transaksi harus rollback.
        DB::statement('ALTER TABLE rec_uji_gagal ADD CONSTRAINT uji_batas CHECK (kd_rup < 1000)');

        try {
            $writer->replace('rec_uji_gagal', 'D462', 2026, [
                ['kd_rup' => 3],
                ['kd_rup' => 2000],
            ]);
            $this->fail('Kegagalan insert seharusnya melempar exception.');
        } catch (\Throwable) {
            // diharapkan
        }

        $this->assertSame($before, $writer->countFor('rec_uji_gagal', 'D462', 2026));
        $this->assertDatabaseHas('rec_uji_gagal', ['kd_rup' => 1]);
    }

    public function test_insert_dalam_batch(): void
    {
        config(['inaproc.chunk_size' => 2]);

        $writer = app(TableWriter::class);
        $rows = array_map(static fn (int $i): array => ['kd_rup' => $i], range(1, 5));

        $this->assertSame(5, $writer->replace('rec_uji_batch', 'D462', 2026, $rows));
        $this->assertSame(5, $writer->countFor('rec_uji_batch', 'D462', 2026));
    }

    public function test_diff_mendeteksi_kolom_hilang(): void
    {
        $this->schema()->ensureTable('rec_uji_diff', [['kd_rup' => 1]]);

        $diff = $this->schema()->diff('rec_uji_diff', [['kd_rup' => 1, 'kolom_baru' => 'x']]);

        $this->assertContains('kolom_baru', $diff['missing']);
    }
}
