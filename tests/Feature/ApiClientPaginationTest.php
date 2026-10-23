<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ApiFamily;
use App\Models\Endpoint;
use App\Services\Inaproc\ApiClient;
use App\Services\Inaproc\EndpointRegistry;
use App\Services\Inaproc\Paginator;
use App\Support\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * F3 — pagination cursor (v1) dan single shot (legacy).
 */
class ApiClientPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function paginator(): Paginator
    {
        return new Paginator(
            new ApiClient(new RateLimiter(static fn (int $us) => null, static fn (): float => 0.0)),
            new EndpointRegistry,
        );
    }

    private function endpoint(array $attributes): Endpoint
    {
        return new Endpoint(array_merge([
            'name' => 'paket-penyedia',
            'path' => '/api/v1/rup/paket-penyedia',
            'api' => 'v1',
            'grup' => 'rup',
            'scope' => 'klpd',
            'active' => true,
            'urutan' => 1,
        ], $attributes));
    }

    public function test_v1_mengikuti_cursor_sampai_habis(): void
    {
        Http::fake([
            '*' => Http::sequence()
                ->push(['meta' => ['cursor' => 'c1', 'has_more' => true], 'success' => true, 'data' => [['kd_rup' => 1]]])
                ->push(['meta' => ['cursor' => 'c2', 'has_more' => true], 'success' => true, 'data' => [['kd_rup' => 2]]])
                ->push(['meta' => ['cursor' => null, 'has_more' => false], 'success' => true, 'data' => [['kd_rup' => 3]]]),
        ]);

        $result = $this->paginator()->fetchAll($this->endpoint([]), 'D462', 2026, 'tok', 'key');

        $this->assertSame(3, $result['pages']);
        $this->assertCount(3, $result['rows']);
        $this->assertSame([1, 2, 3], array_column($result['rows'], 'kd_rup'));
    }

    public function test_berhenti_saat_has_more_true_tapi_cursor_kosong(): void
    {
        // API dapat mengirim has_more true bersama cursor kosong; hanya satu
        // syarat saja akan menyebabkan crash / loop.
        Http::fake([
            '*' => Http::sequence()
                ->push(['meta' => ['cursor' => 'c1', 'has_more' => true], 'data' => [['kd_rup' => 1]]])
                ->push(['meta' => ['cursor' => '', 'has_more' => true], 'data' => [['kd_rup' => 2]]]),
        ]);

        $result = $this->paginator()->fetchAll($this->endpoint([]), 'D462', 2026, 'tok', 'key');

        $this->assertSame(2, $result['pages']);
        $this->assertCount(2, $result['rows']);
    }

    public function test_cursor_tidak_ada_sama_sekali(): void
    {
        Http::fake([
            '*' => Http::response(['meta' => ['has_more' => true], 'data' => [['kd_rup' => 1]]]),
        ]);

        $result = $this->paginator()->fetchAll($this->endpoint([]), 'D462', 2026, 'tok', 'key');

        $this->assertSame(1, $result['pages']);
    }

    public function test_limit_dibatasi_1000(): void
    {
        Http::fake(['*' => Http::response(['meta' => ['has_more' => false], 'data' => []])]);

        config(['inaproc.page_size' => 5000]);

        $this->paginator()->fetchAll($this->endpoint([]), 'D462', 2026, 'tok', 'key');

        Http::assertSent(function (Request $request): bool {
            return $request['limit'] <= 1000;
        });
    }

    public function test_legacy_single_shot_array_langsung(): void
    {
        Http::fake([
            '*' => Http::response([['kd_rup' => 1], ['kd_rup' => 2]]),
        ]);

        $result = $this->paginator()->fetchAll(
            $this->endpoint(['api' => 'legacy', 'path' => '/api/legacy/rup/paket-penyedia']),
            'D462',
            2026,
            'tok',
            'key',
        );

        $this->assertSame(1, $result['pages']);
        $this->assertCount(2, $result['rows']);
        Http::assertSentCount(1);
    }

    public function test_legacy_objek_tunggal_tetap_tertangani(): void
    {
        Http::fake(['*' => Http::response(['kd_rup' => 7, 'nama_paket' => 'X'])]);

        $result = $this->paginator()->fetchAll(
            $this->endpoint(['api' => 'legacy', 'path' => '/api/legacy/rup/paket-penyedia']),
            'D462',
            2026,
            'tok',
            'key',
        );

        $this->assertCount(1, $result['rows']);
        $this->assertSame(7, $result['rows'][0]['kd_rup']);
    }

    public function test_respons_bukan_json_tidak_melempar_exception(): void
    {
        Http::fake(['*' => Http::response('<html>error</html>', 200)]);

        $result = $this->paginator()->fetchAll($this->endpoint([]), 'D462', 2026, 'tok', 'key');

        $this->assertSame([], $result['rows']);
        $this->assertFalse($result['response']->isJson);
        $this->assertSame('Respons bukan JSON yang valid', $result['response']->error);
    }

    public function test_master_program_tidak_mengirim_kode_klpd(): void
    {
        Http::fake(['*' => Http::response(['meta' => ['has_more' => false], 'data' => []])]);

        $this->paginator()->fetchAll(
            $this->endpoint(['name' => 'master-program', 'path' => '/api/v1/master/program', 'grup' => 'master', 'scope' => 'tahun']),
            'D462',
            2026,
            'tok',
            'key',
        );

        Http::assertSent(function (Request $request): bool {
            return ! isset($request['kode_klpd']) && $request['tahun'] === 2026;
        });
    }
}
