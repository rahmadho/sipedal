<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ApiFamily;
use App\Support\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * F7 — empat lapisan pembatasan laju.
 */
class RateLimiterTest extends TestCase
{
    private float $now = 1000.0;

    /** @var array<int, int> */
    private array $slept = [];

    private function limiter(): RateLimiter
    {
        return new RateLimiter(
            function (int $microseconds): void {
                $this->slept[] = $microseconds;
                $this->now += $microseconds / 1_000_000;
            },
            fn (): float => $this->now,
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->now = 1000.0;
        $this->slept = [];
    }

    public function test_l1_token_bucket_menjaga_laju(): void
    {
        config(['inaproc.rate.v1_per_min' => 60]); // 1 per detik

        $limiter = $this->limiter();

        $limiter->acquire('tok-a', ApiFamily::V1);
        $limiter->acquire('tok-a', ApiFamily::V1);

        $this->assertNotEmpty($this->slept);
        $this->assertEqualsWithDelta(1.0, $this->slept[0] / 1_000_000, 0.01);
    }

    public function test_dua_klpd_tidak_saling_mengurangi_kuota(): void
    {
        config(['inaproc.rate.v1_per_min' => 60]);

        $limiter = $this->limiter();

        $limiter->acquire('tok-a', ApiFamily::V1);
        $limiter->acquire('tok-b', ApiFamily::V1);

        // Token berbeda → bucket terpisah → tidak ada penundaan.
        $this->assertSame([], $this->slept);
    }

    public function test_l2_sisa_kuota_hampir_habis_memicu_parkir(): void
    {
        config(['inaproc.rate.pause_threshold' => 30]);

        $limiter = $this->limiter();
        $reset = (string) (int) ($this->now + 60);

        $limiter->observe('tok-a', ApiFamily::V1, [
            'x-ratelimit-remaining' => '5',
            'x-ratelimit-reset' => $reset,
        ]);

        $this->assertTrue($limiter->isParked('tok-a'));
        $this->assertGreaterThan(0, $limiter->parkedFor('tok-a'));
    }

    public function test_l2_sisa_kuota_menengah_memperlambat(): void
    {
        config([
            'inaproc.rate.pause_threshold' => 30,
            'inaproc.rate.slow_threshold' => 300,
            'inaproc.rate.v1_per_min' => 300,
        ]);

        $limiter = $this->limiter();

        $limiter->observe('tok-a', ApiFamily::V1, ['x-ratelimit-remaining' => '150']);

        $this->assertFalse($limiter->isParked('tok-a'));

        $limiter->acquire('tok-a', ApiFamily::V1);

        $this->assertNotEmpty($this->slept);
    }

    public function test_l4_circuit_breaker_memarkir_kredensial(): void
    {
        $limiter = $this->limiter();

        $limiter->park('tok-a', 120);

        $this->assertTrue($limiter->isParked('tok-a'));
        $this->assertSame(120, $limiter->parkedFor('tok-a'));

        // KLPD lain tetap bebas.
        $this->assertFalse($limiter->isParked('tok-b'));

        $limiter->release('tok-a');
        $this->assertFalse($limiter->isParked('tok-a'));
    }

    public function test_kredensial_berbeda_tidak_berbagi_parkir(): void
    {
        $limiter = $this->limiter();

        $limiter->park('D462|hash', 60);

        $this->assertTrue($limiter->isParked('D462|hash'));
        $this->assertFalse($limiter->isParked('D463|hash'));
    }
}
