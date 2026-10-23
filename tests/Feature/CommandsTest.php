<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Endpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * F12 — antarmuka operasional Artisan.
 */
class CommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\EndpointSeeder::class);
        config(['inaproc.max_retry' => 0, 'inaproc.rate.max_retry' => 0]);

        // Berkas kredensial uji.
        $path = config('inaproc.klpd_file');
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, json_encode([
            'D462' => ['token' => 'inprc-test-token-1234567890', 'label' => 'Uji'],
            'D472' => ['token' => '', 'label' => 'Belum ada token'],
        ], JSON_PRETTY_PRINT));
    }

    protected function tearDown(): void
    {
        @unlink(config('inaproc.klpd_file'));

        parent::tearDown();
    }

    public function test_endpoints_menampilkan_65_dan_61_aktif(): void
    {
        $this->artisan('inaproc:endpoints')
            ->expectsOutputToContain('65 endpoint, 61 aktif.')
            ->assertSuccessful();
    }

    public function test_dry_run_tidak_memanggil_api(): void
    {
        Http::fake(['*' => Http::response(['meta' => ['has_more' => false], 'data' => []])]);

        $this->artisan('inaproc:pull', ['--klpd' => 'D462', '--tahun' => 2026, '--dry-run' => true])
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_pull_sync_mengisi_pull_log_dan_exit_code_sukses(): void
    {
        Http::fake([
            '*' => Http::response(['meta' => ['has_more' => false], 'data' => [['kd_rup' => 1]]]),
        ]);

        $this->artisan('inaproc:pull', [
            '--klpd' => 'D462',
            '--tahun' => 2026,
            '--sync' => true,
            '--endpoint' => ['paket-penyedia'],
            '--no-interaction' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('pull_log', [
            'endpoint' => 'paket-penyedia',
            'kode_klpd' => 'D462',
            'tahun' => 2026,
            'status' => 'OK',
        ]);
    }

    public function test_exit_code_gagal_bila_ada_endpoint_gagal(): void
    {
        Http::fake(['*' => Http::response('err', 500)]);

        $this->artisan('inaproc:pull', [
            '--klpd' => 'D462',
            '--tahun' => 2026,
            '--sync' => true,
            '--endpoint' => ['paket-penyedia'],
            '--no-interaction' => true,
        ])->assertFailed();
    }

    public function test_klpd_tanpa_token_dilewati(): void
    {
        Http::fake(['*' => Http::response(['meta' => ['has_more' => false], 'data' => []])]);

        $this->artisan('inaproc:pull', [
            '--klpd' => 'D472',
            '--tahun' => 2026,
            '--sync' => true,
            '--endpoint' => ['paket-penyedia'],
            '--no-interaction' => true,
        ])->assertSuccessful();

        Http::assertNothingSent();
        $this->assertDatabaseCount('pull_log', 0);
    }

    public function test_validate_tokens_tidak_mencetak_token_mentah(): void
    {
        Http::fake(['*' => Http::response(['meta' => ['has_more' => false], 'data' => []])]);

        $this->artisan('inaproc:validate-tokens', ['--tahun' => 2026])
            ->assertFailed(); // D472 tidak punya token

        $output = $this->artisan('inaproc:validate-tokens', ['--tahun' => 2026])->run();

        $this->assertStringNotContainsString('inprc-test-token-1234567890', $this->output());
    }

    public function test_probe_menguji_satu_request_per_endpoint(): void
    {
        Http::fake(['*' => Http::response(['meta' => ['has_more' => false], 'data' => []])]);

        $this->artisan('inaproc:probe', [
            '--klpd' => 'D462',
            '--tahun' => 2026,
            '--grup' => 'master',
        ])->assertSuccessful();

        Http::assertSentCount(1);
    }

    public function test_build_realisasi_menghasilkan_tabel_dan_view(): void
    {
        // View membutuhkan tabel paket; siapkan minimal satu baris.
        $schema = app(\App\Services\Pull\SchemaManager::class);
        $schema->ensureTable('rec_rup_paket_penyedia', [[
            'kd_rup' => 100, 'nama_paket' => 'a', 'pagu' => 1, 'kd_satker' => 1,
            'nama_satker' => 'S', 'metode_pengadaan' => 'X', 'tahun_anggaran' => 2026,
        ]]);
        \Illuminate\Support\Facades\DB::table('rec_rup_paket_penyedia')->insert([
            '_kode_klpd' => 'D462', '_tahun' => 2026, '_fetched_at' => now(),
            'kd_rup' => 100, 'nama_paket' => 'a', 'pagu' => 1, 'kd_satker' => 1,
            'nama_satker' => 'S', 'metode_pengadaan' => 'X', 'tahun_anggaran' => 2026,
        ]);

        $this->artisan('inaproc:build-realisasi')
            ->assertSuccessful();

        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('ref_rup_proses'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('ref_rup_warisan'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasView('v_realisasi_rup'));
    }

    public function test_schema_check_berjalan(): void
    {
        $this->artisan('inaproc:schema-check')->assertSuccessful();
    }

    public function test_pull_menolak_tahun_tidak_valid(): void
    {
        $this->artisan('inaproc:pull', ['--klpd' => 'D462', '--tahun' => 1800])
            ->assertFailed();
    }
}
