<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\PullLog;
use App\Services\Pull\PullService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * F5 — empat aturan fallback (R1–R4).
 */
class FallbackRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\EndpointSeeder::class);

        // Jangan menunggu backoff nyata di test.
        config(['inaproc.max_retry' => 0, 'inaproc.rate.max_retry' => 0]);
    }

    private function pull(string $endpointName): PullLog
    {
        $endpoint = Endpoint::query()->findOrFail($endpointName);

        return app(PullService::class)->pullEndpoint($endpoint, 'D462', 2026, 'tok', 'key');
    }

    public function test_r1_fallback_dipicu_hanya_saat_non_200(): void
    {
        Http::fake([
            '*paket-penyedia-terumumkan*' => Http::response([
                'meta' => ['has_more' => false],
                'data' => [['kd_rup' => 111, 'nama_paket' => 'dari fallback']],
            ]),
            '*rup/paket-penyedia*' => Http::response('server error', 500),
        ]);

        $log = $this->pull('paket-penyedia');

        $this->assertSame('OK', $log->status);
        $this->assertSame('paket-penyedia-terumumkan', $log->source);
        Http::assertSent(fn (Request $r): bool => str_contains($r->url(), 'paket-penyedia-terumumkan'));
    }

    public function test_r2_200_kosong_tidak_memicu_fallback(): void
    {
        Http::fake([
            '*paket-penyedia-terumumkan*' => Http::response([
                'meta' => ['has_more' => false],
                'data' => [['kd_rup' => 999]],
            ]),
            '*rup/paket-penyedia*' => Http::response(['meta' => ['has_more' => false], 'data' => []]),
        ]);

        $log = $this->pull('paket-penyedia');

        $this->assertSame('KOSONG', $log->status);
        $this->assertNull($log->source);
        Http::assertNotSent(fn (Request $r): bool => str_contains($r->url(), 'terumumkan'));
    }

    public function test_r3_utama_dan_fallback_gagal_data_lama_tetap_utuh(): void
    {
        // Data lama disiapkan langsung di tabel (tanpa memanggil API).
        app(\App\Services\Pull\SchemaManager::class)
            ->ensureTable('rec_rup_paket_penyedia', [['kd_rup' => 1, 'nama_paket' => 'lama']]);

        \Illuminate\Support\Facades\DB::table('rec_rup_paket_penyedia')->insert([
            '_kode_klpd' => 'D462',
            '_tahun' => 2026,
            '_fetched_at' => now(),
            'kd_rup' => 1,
            'nama_paket' => 'lama',
        ]);

        // Utama 500, fallback 503 — keduanya gagal.
        Http::fake(function ($request) {
            return str_contains($request->url(), 'terumumkan')
                ? Http::response('err', 503)
                : Http::response('err', 500);
        });

        $log = $this->pull('paket-penyedia');

        $this->assertSame('GAGAL', $log->status);
        $this->assertDatabaseHas('rec_rup_paket_penyedia', ['nama_paket' => 'lama']);
    }

    public function test_r4_hasil_fallback_masuk_tabel_utama_dengan_source(): void
    {
        Http::fake([
            '*paket-penyedia-terumumkan*' => Http::response([
                'meta' => ['has_more' => false],
                'data' => [['kd_rup' => 555, 'nama_paket' => 'fallback']],
            ]),
            '*rup/paket-penyedia*' => Http::response('err', 500),
        ]);

        $this->pull('paket-penyedia');

        $this->assertDatabaseHas('rec_rup_paket_penyedia', [
            'nama_paket' => 'fallback',
            '_source' => 'paket-penyedia-terumumkan',
            '_kode_klpd' => 'D462',
        ]);
        // R4: tabel fallback tidak dipakai untuk hasil fallback.
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('rec_rup_paket_penyedia_terumumkan'));
    }

    public function test_403_dilewati_tanpa_menghentikan_proses(): void
    {
        Http::fake(['*' => Http::response(['message' => 'invalid token'], 403)]);

        $log = $this->pull('paket-penyedia');

        $this->assertSame('FORBIDDEN', $log->status);
        $this->assertStringContainsString('token', (string) $log->pesan);
    }

    public function test_blokir_jaringan_dibedakan_dari_token_salah(): void
    {
        Http::fake([
            '*' => Http::response('<html><body>Access Denied</body></html>', 403, ['Content-Type' => 'text/html']),
        ]);

        $log = $this->pull('paket-penyedia');

        $this->assertSame('FORBIDDEN', $log->status);
        $this->assertStringContainsString('Diblokir', (string) $log->pesan);
    }

    public function test_pull_ulang_tidak_menghasilkan_duplikasi(): void
    {
        Http::fake([
            '*' => Http::response([
                'meta' => ['has_more' => false],
                'data' => [['kd_rup' => 1], ['kd_rup' => 2], ['kd_rup' => 3]],
            ]),
        ]);

        $this->pull('paket-penyedia');
        $this->pull('paket-penyedia');

        $this->assertDatabaseCount('rec_rup_paket_penyedia', 3);
    }

    public function test_pesan_dipotong_255_karakter(): void
    {
        Http::fake([
            '*' => Http::response(['message' => str_repeat('x', 600)], 400),
        ]);

        $log = $this->pull('paket-penyedia');

        $this->assertLessThanOrEqual(255, mb_strlen((string) $log->pesan));
    }
}
