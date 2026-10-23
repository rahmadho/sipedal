<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Services\Inaproc\EndpointRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F1, F1.1, F2 — registry endpoint, scope, penamaan tabel.
 */
class EndpointRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\EndpointSeeder::class);
    }

    public function test_seeder_mengisi_65_endpoint_dengan_61_aktif(): void
    {
        $this->assertDatabaseCount('endpoints', 65);
        $this->assertSame(61, Endpoint::query()->where('active', true)->count());
    }

    public function test_seeder_idempoten(): void
    {
        $this->seed(\Database\Seeders\EndpointSeeder::class);

        $this->assertDatabaseCount('endpoints', 65);
    }

    public function test_nama_tabel_diturunkan_dan_unik(): void
    {
        $registry = app(EndpointRegistry::class);
        $tables = $registry->all()->map(fn (Endpoint $e): string => $e->tableName())->all();

        $this->assertCount(65, array_unique($tables));

        $this->assertSame(
            'rec_rup_paket_penyedia',
            $registry->find('paket-penyedia')->tableName(),
        );

        $this->assertSame(
            'rec_legacy_tender_non_tender_selesai',
            $registry->find('legacy-tender-non-tender-selesai')->tableName(),
        );
    }

    public function test_panjang_nama_tabel_di_bawah_batas_postgres(): void
    {
        foreach (app(EndpointRegistry::class)->all() as $endpoint) {
            $this->assertLessThanOrEqual(63, strlen($endpoint->tableName()), $endpoint->name);
        }
    }

    public function test_scope_klpd_mengirim_kode_klpd_dan_tahun(): void
    {
        $registry = app(EndpointRegistry::class);
        $params = $registry->buildParams($registry->find('paket-penyedia'), 'D462', 2026);

        $this->assertSame('D462', $params['kode_klpd']);
        $this->assertSame(2026, $params['tahun']);
        $this->assertSame(1000, $params['limit']);
    }

    public function test_scope_tahun_tidak_mengirim_kode_klpd(): void
    {
        $registry = app(EndpointRegistry::class);
        $params = $registry->buildParams($registry->find('master-program'), 'D462', 2026);

        $this->assertArrayNotHasKey('kode_klpd', $params);
        $this->assertSame(2026, $params['tahun']);
    }

    public function test_scope_derived_hanya_mengirim_parameter_turunan(): void
    {
        $registry = app(EndpointRegistry::class);
        $params = $registry->buildParams(
            $registry->find('ekatalog-penyedia-detail'),
            'D462',
            2026,
            ['kode_penyedia' => 'P123'],
        );

        $this->assertSame(['kode_penyedia' => 'P123', 'limit' => 1000], $params);
        $this->assertArrayNotHasKey('kode_klpd', $params);
        $this->assertArrayNotHasKey('tahun', $params);
    }

    public function test_endpoint_baru_dapat_ditambahkan_tanpa_menyentuh_pull_service(): void
    {
        Endpoint::query()->create([
            'name' => 'uji-endpoint-baru',
            'path' => '/api/v1/uji/baru',
            'api' => 'v1',
            'grup' => 'rup',
            'scope' => 'klpd',
            'active' => true,
            'urutan' => 999,
        ]);

        $this->assertSame(
            'rec_rup_uji_endpoint_baru',
            app(EndpointRegistry::class)->find('uji-endpoint-baru')->tableName(),
        );
    }

    public function test_dapat_difilter_per_grup_dan_status(): void
    {
        $registry = app(EndpointRegistry::class);

        $this->assertCount(20, $registry->forGroup('tender')->filter(fn (Endpoint $e): bool => $e->api === 'legacy'));
        $this->assertCount(61, $registry->all(true));
        $this->assertSame(0, $registry->forGroup('ekatalog', true)->where('scope', 'derived')->count());
    }
}
