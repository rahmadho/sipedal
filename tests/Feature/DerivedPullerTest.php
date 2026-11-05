<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\PullLog;
use App\Services\Pull\PullService;
use App\Services\Pull\SchemaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * F1.3 — incremental endpoint turunan.
 */
class DerivedPullerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, array<string, mixed>>|null null = respons gagal */
    private ?array $nextRows = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\EndpointSeeder::class);
        config(['inaproc.max_retry' => 0, 'inaproc.rate.max_retry' => 0]);

        // Satu stub untuk seluruh test: Http::fake() menumpuk stub, sehingga
        // pemanggilan berulang membuat stub lama tetap menang. Status respons
        // dikendalikan lewat properti agar tiap fase dapat berbeda.
        Http::fake(function () {
            return $this->nextRows === null
                ? Http::response('err', 500)
                : Http::response(['meta' => ['has_more' => false], 'data' => $this->nextRows]);
        });
    }

    private function seedSource(array $penyedia, string $klpd = 'D462'): void
    {
        app(SchemaManager::class)
            ->ensureTable('rec_ekatalog_paket_e_purchasing', [['kode_penyedia' => 'x']]);

        foreach ($penyedia as $kode) {
            DB::table('rec_ekatalog_paket_e_purchasing')->insert([
                '_kode_klpd' => $klpd,
                '_tahun' => 2026,
                '_fetched_at' => now(),
                'kode_penyedia' => $kode,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function fakeOk(array $rows = [['nama' => 'x']]): void
    {
        $this->nextRows = $rows;
        Http::clearResolvedInstances();
        Http::fake(function () {
            return Http::response(['meta' => ['has_more' => false], 'data' => $this->nextRows ?? []]);
        });
    }

    private function fakeFail(): void
    {
        $this->nextRows = null;
        Http::clearResolvedInstances();
        Http::fake(fn () => Http::response('err', 500));
    }

    private function pullDerived(bool $refresh = false, string $klpd = 'D462'): PullLog
    {
        $endpoint = Endpoint::query()->findOrFail('ekatalog-penyedia-detail');
        $endpoint->active = true;

        return app(PullService::class)->pullEndpoint($endpoint, $klpd, 2026, 'tok', 'key', $refresh);
    }

    public function test_run_kedua_tanpa_perubahan_tidak_memanggil_api_lagi(): void
    {
        $this->seedSource(['P1', 'P2']);

        $this->fakeOk();
        $first = $this->pullDerived();
        $this->assertSame('OK', $first->status);

        $this->fakeOk();
        $second = $this->pullDerived();

        // 0 request ke endpoint turunan pada run kedua.
        Http::assertNothingSent();
        $this->assertStringContainsString('dilewati=2', (string) $second->pesan);
        $this->assertStringContainsString('ditarik=0', (string) $second->pesan);
    }

    public function test_nilai_baru_pada_sumber_hanya_menarik_nilai_tersebut(): void
    {
        $this->seedSource(['P1', 'P2']);

        $this->fakeOk();
        $this->pullDerived();

        $this->seedSource(['P3']);

        $this->fakeOk([['nama' => 'y']]);
        $this->pullDerived();

        // Hanya P3 yang belum tersimpan → 1 request.
        Http::assertSentCount(1);
    }

    public function test_hasil_kosong_tetap_dicatat(): void
    {
        $this->seedSource(['P1']);

        $this->fakeOk([]);
        $this->pullDerived();

        $this->assertDatabaseHas('derived_progress', [
            'endpoint' => 'ekatalog-penyedia-detail',
            'kode_klpd' => 'D462',
            'tahun' => 2026,
            'nilai_kunci' => 'P1',
            'hasil_rows' => 0,
        ]);

        // Run kedua tidak mencoba ulang nilai kosong.
        $this->fakeOk([]);
        $this->pullDerived();

        Http::assertNothingSent();
    }

    public function test_request_gagal_tidak_dicatat_dan_diulang(): void
    {
        $this->seedSource(['P1']);

        $this->fakeFail();
        $this->pullDerived();

        $this->assertDatabaseCount('derived_progress', 0);

        $this->fakeOk();
        $this->pullDerived();

        $this->assertDatabaseHas('derived_progress', ['nilai_kunci' => 'P1']);
    }

    public function test_refresh_derived_menarik_ulang_semua_nilai(): void
    {
        $this->seedSource(['P1', 'P2']);

        $this->fakeOk();
        $this->pullDerived();

        $this->fakeOk();
        $this->pullDerived(true);

        Http::assertSentCount(2);
    }

    public function test_data_klpd_lain_dengan_nilai_sama_tetap_ditarik(): void
    {
        $this->seedSource(['P1']);

        $this->fakeOk();
        $this->pullDerived();

        // KLPD berbeda, nilai kunci sama → harus ditarik terpisah.
        $this->seedSource(['P1'], 'D463');

        $this->fakeOk();
        $this->pullDerived(false, 'D463');

        Http::assertSentCount(1);
        $this->assertDatabaseCount('derived_progress', 2);
    }

    public function test_append_tidak_menghapus_data_lama(): void
    {
        $this->seedSource(['P1']);

        $this->fakeOk([['nama' => 'pertama']]);
        $this->pullDerived();

        $this->fakeOk([['nama' => 'kedua']]);
        $this->pullDerived(true);

        $this->assertDatabaseCount('inaproc.rec_ekatalog_penyedia_detail', 2);
    }
}
