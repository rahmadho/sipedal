<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Pull\SchemaManager;
use App\Services\Realisasi\ViewBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * F11.3, F11.6, F11.7 — eksploitasi multi-nilai, materialisasi, view.
 */
class RealisasiTest extends TestCase
{
    use RefreshDatabase;

    private function schema(): SchemaManager
    {
        return app(SchemaManager::class);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function seedTable(string $table, array $rows, string $klpd = 'D462'): void
    {
        $this->schema()->ensureTable($table, $rows);

        foreach ($rows as $row) {
            DB::table($table)->insert($row + [
                '_kode_klpd' => $klpd,
                '_tahun' => 2026,
                '_fetched_at' => now(),
            ]);
        }
    }

    public function test_referensi_multi_nilai_menghasilkan_tiga_entri(): void
    {
        $this->seedTable('rec_rup_paket_penyedia', [
            ['kd_rup' => 111, 'nama_paket' => 'A', 'pagu' => 1000, 'kd_satker' => 1, 'nama_satker' => 'S', 'metode_pengadaan' => 'Tender', 'tahun_anggaran' => 2026],
            ['kd_rup' => 222, 'nama_paket' => 'B', 'pagu' => 2000, 'kd_satker' => 1, 'nama_satker' => 'S', 'metode_pengadaan' => 'Tender', 'tahun_anggaran' => 2026],
            ['kd_rup' => 333, 'nama_paket' => 'C', 'pagu' => 3000, 'kd_satker' => 1, 'nama_satker' => 'S', 'metode_pengadaan' => 'Tender', 'tahun_anggaran' => 2026],
        ]);

        // Satu baris sumber memuat 3 kode.
        $this->seedTable('rec_tender_pengumuman', [
            ['kd_rup' => '111;222;333'],
        ]);

        $result = app(ViewBuilder::class)->build();

        $this->assertSame(3, $result['proses']);
        $this->assertSame(0, $result['warisan']);
        $this->assertSame(3, $result['view']);
    }

    public function test_nilai_kosong_dan_spasi_dibuang(): void
    {
        $this->seedTable('rec_rup_paket_penyedia', [
            ['kd_rup' => 111, 'nama_paket' => 'A', 'pagu' => 1, 'kd_satker' => 1, 'nama_satker' => 'S', 'metode_pengadaan' => 'X', 'tahun_anggaran' => 2026],
        ]);

        $this->seedTable('rec_tender_pengumuman', [
            ['kd_rup' => ' 111 ;; ;'],
        ]);

        $this->assertSame(1, app(ViewBuilder::class)->build()['proses']);
    }

    public function test_warisan_dicatat_dengan_depth_dan_dari_rup(): void
    {
        $this->seedTable('rec_rup_paket_penyedia', [
            ['kd_rup' => 100, 'nama_paket' => 'lama', 'pagu' => 1, 'kd_satker' => 1, 'nama_satker' => 'S', 'metode_pengadaan' => 'X', 'tahun_anggaran' => 2026],
            ['kd_rup' => 200, 'nama_paket' => 'baru', 'pagu' => 2, 'kd_satker' => 1, 'nama_satker' => 'S', 'metode_pengadaan' => 'X', 'tahun_anggaran' => 2026],
            ['kd_rup' => 300, 'nama_paket' => 'baru2', 'pagu' => 3, 'kd_satker' => 1, 'nama_satker' => 'S', 'metode_pengadaan' => 'X', 'tahun_anggaran' => 2026],
        ]);

        // Hanya 100 yang punya proses.
        $this->seedTable('rec_tender_pengumuman', [['kd_rup' => '100']]);

        // 200 mewarisi dari 100; 300 mewarisi dari 200 (transitif).
        $this->seedTable('rec_rup_history_kaji_ulang', [
            ['kd_rup_lama' => 100, 'kd_rup_baru' => 200, 'jenis_revisi' => 'SATUKESATU', 'jenis_paket' => 'PENYEDIA'],
            ['kd_rup_lama' => 200, 'kd_rup_baru' => 300, 'jenis_revisi' => 'SATUKESATU', 'jenis_paket' => 'PENYEDIA'],
        ]);

        $result = app(ViewBuilder::class)->build();

        $this->assertSame(1, $result['proses']);
        $this->assertSame(2, $result['warisan']);
        $this->assertSame(3, $result['view']);

        $this->assertDatabaseHas('ref_rup_warisan', ['kd_rup' => '200', 'dari_rup' => '100', 'depth' => 1]);
        $this->assertDatabaseHas('ref_rup_warisan', ['kd_rup' => '300', 'dari_rup' => '100', 'depth' => 2]);
    }

    public function test_revisi_selain_satukesatu_tidak_mewarisi(): void
    {
        $this->seedTable('rec_rup_paket_penyedia', [
            ['kd_rup' => 100, 'nama_paket' => 'a', 'pagu' => 1, 'kd_satker' => 1, 'nama_satker' => 'S', 'metode_pengadaan' => 'X', 'tahun_anggaran' => 2026],
            ['kd_rup' => 200, 'nama_paket' => 'b', 'pagu' => 1, 'kd_satker' => 1, 'nama_satker' => 'S', 'metode_pengadaan' => 'X', 'tahun_anggaran' => 2026],
        ]);

        $this->seedTable('rec_tender_pengumuman', [['kd_rup' => '100']]);
        $this->seedTable('rec_rup_history_kaji_ulang', [
            ['kd_rup_lama' => 100, 'kd_rup_baru' => 200, 'jenis_revisi' => 'PEMBATALAN', 'jenis_paket' => 'PENYEDIA'],
        ]);

        $result = app(ViewBuilder::class)->build();

        $this->assertSame(0, $result['warisan']);
    }

    public function test_jenis_paket_swakelola_tidak_mewarisi(): void
    {
        $this->seedTable('rec_rup_paket_penyedia', [
            ['kd_rup' => 100, 'nama_paket' => 'a', 'pagu' => 1, 'kd_satker' => 1, 'nama_satker' => 'S', 'metode_pengadaan' => 'X', 'tahun_anggaran' => 2026],
            ['kd_rup' => 200, 'nama_paket' => 'b', 'pagu' => 1, 'kd_satker' => 1, 'nama_satker' => 'S', 'metode_pengadaan' => 'X', 'tahun_anggaran' => 2026],
        ]);

        $this->seedTable('rec_tender_pengumuman', [['kd_rup' => '100']]);
        $this->seedTable('rec_rup_history_kaji_ulang', [
            ['kd_rup_lama' => 100, 'kd_rup_baru' => 200, 'jenis_revisi' => 'SATUKESATU', 'jenis_paket' => 'SWAKELOLA'],
        ]);

        $this->assertSame(0, app(ViewBuilder::class)->build()['warisan']);
    }

    public function test_pasangan_identik_diabaikan(): void
    {
        $this->seedTable('rec_rup_paket_penyedia', [
            ['kd_rup' => 100, 'nama_paket' => 'a', 'pagu' => 1, 'kd_satker' => 1, 'nama_satker' => 'S', 'metode_pengadaan' => 'X', 'tahun_anggaran' => 2026],
        ]);

        $this->seedTable('rec_tender_pengumuman', [['kd_rup' => '100']]);
        $this->seedTable('rec_rup_history_kaji_ulang', [
            ['kd_rup_lama' => 100, 'kd_rup_baru' => 100, 'jenis_revisi' => 'SATUKESATU', 'jenis_paket' => 'PENYEDIA'],
        ]);

        $result = app(ViewBuilder::class)->build();

        $this->assertSame(1, $result['proses']);
        $this->assertSame(0, $result['warisan']);
    }

    public function test_perhitungan_ulang_idempoten(): void
    {
        $this->seedTable('rec_rup_paket_penyedia', [
            ['kd_rup' => 100, 'nama_paket' => 'a', 'pagu' => 1, 'kd_satker' => 1, 'nama_satker' => 'S', 'metode_pengadaan' => 'X', 'tahun_anggaran' => 2026],
        ]);
        $this->seedTable('rec_tender_pengumuman', [['kd_rup' => '100']]);

        $first = app(ViewBuilder::class)->build();
        $second = app(ViewBuilder::class)->build();

        $this->assertSame($first, $second);
        $this->assertDatabaseCount('ref_rup_proses', 1);
    }

    public function test_satu_paket_dapat_muncul_dengan_sumber_berbeda(): void
    {
        $this->seedTable('rec_rup_paket_penyedia', [
            ['kd_rup' => 100, 'nama_paket' => 'a', 'pagu' => 1, 'kd_satker' => 1, 'nama_satker' => 'S', 'metode_pengadaan' => 'X', 'tahun_anggaran' => 2026],
        ]);
        $this->seedTable('rec_tender_pengumuman', [['kd_rup' => '100']]);
        $this->seedTable('rec_tender_non_tender_pengumuman', [['kd_rup' => '100']]);

        $result = app(ViewBuilder::class)->build();

        $this->assertSame(2, $result['proses']);
        $this->assertSame(2, $result['view']);

        $this->assertDatabaseHas('v_realisasi_rup', ['kd_rup' => '100', 'sumber' => 'tender', 'jenis' => 'langsung']);
        $this->assertDatabaseHas('v_realisasi_rup', ['kd_rup' => '100', 'sumber' => 'non-tender', 'jenis' => 'langsung']);
    }

    public function test_jenis_warisan_konsisten_dengan_depth(): void
    {
        $this->seedTable('rec_rup_paket_penyedia', [
            ['kd_rup' => 100, 'nama_paket' => 'a', 'pagu' => 1, 'kd_satker' => 1, 'nama_satker' => 'S', 'metode_pengadaan' => 'X', 'tahun_anggaran' => 2026],
            ['kd_rup' => 200, 'nama_paket' => 'b', 'pagu' => 1, 'kd_satker' => 1, 'nama_satker' => 'S', 'metode_pengadaan' => 'X', 'tahun_anggaran' => 2026],
        ]);
        $this->seedTable('rec_ekatalog_paket_e_purchasing', [['rup_code' => 100]]);
        $this->seedTable('rec_rup_history_kaji_ulang', [
            ['kd_rup_lama' => 100, 'kd_rup_baru' => 200, 'jenis_revisi' => 'SATUKESATU', 'jenis_paket' => 'PENYEDIA'],
        ]);

        app(ViewBuilder::class)->build();

        $warisan = DB::table('v_realisasi_rup')->where('jenis', 'warisan')->first();

        $this->assertNotNull($warisan);
        $this->assertGreaterThan(0, $warisan->depth);
        $this->assertNotNull($warisan->dari_rup);
    }
}
