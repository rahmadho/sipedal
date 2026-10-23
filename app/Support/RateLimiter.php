<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ApiFamily;
use Illuminate\Support\Facades\Cache;

/**
 * Pembatasan laju per token (F7).
 *
 * Empat lapisan:
 *  L1 token bucket — menjaga laju sesuai konfigurasi per token
 *  L2 adaptif header — membaca sisa kuota, memperlambat / berhenti sejenak
 *  L3 retry 429 — ditangani ApiClient (hormati Retry-After + backoff)
 *  L4 circuit breaker — parkir kredensial sementara, KLPD lain tetap jalan
 *
 * Penghitungan bersifat per token, sehingga dua KLPD paralel tidak saling
 * mengurangi kuota.
 */
class RateLimiter
{
    /** @var callable(int): void */
    private $sleeper;

    /** @var callable(): float */
    private $clock;

    public function __construct(?callable $sleeper = null, ?callable $clock = null)
    {
        $this->sleeper = $sleeper ?? static function (int $microseconds): void {
            if ($microseconds > 0) {
                usleep($microseconds);
            }
        };
        $this->clock = $clock ?? static fn (): float => microtime(true);
    }

    /**
     * Tunggu sampai satu request boleh dikirim.
     *
     * @param  string  $credentialKey  Identitas token (bukan token mentah).
     */
    public function acquire(string $credentialKey, ApiFamily $family): void
    {
        $this->waitIfParked($credentialKey);

        $limit = max(1, $family->ratePerMinute());
        $bucketKey = $this->bucketKey($credentialKey, $family);

        // L1 — token bucket: jarak minimum antar request.
        $interval = 60.0 / $limit;

        /** @var array{next: float} $state */
        $state = Cache::get($bucketKey, ['next' => 0.0]);
        $now = ($this->clock)();
        $wait = $state['next'] - $now;

        if ($wait > 0) {
            ($this->sleeper)((int) ceil($wait * 1_000_000));
            $now = ($this->clock)();
        }

        Cache::put($bucketKey, ['next' => max($now, $state['next']) + $interval], now()->addMinutes(10));
    }

    /**
     * L2 — sesuaikan laju berdasarkan header sisa kuota.
     *
     * @param  array<string, string>  $headers
     */
    public function observe(string $credentialKey, ApiFamily $family, array $headers): void
    {
        $remaining = $this->headerValue($headers, 'x-ratelimit-remaining');

        if ($remaining === null) {
            return;
        }

        $remaining = (int) $remaining;
        $limit = max(1, $family->ratePerMinute());
        $pauseThreshold = (int) config('inaproc.rate.pause_threshold', 30);
        $slowThreshold = (int) config('inaproc.rate.slow_threshold', 300);

        // Hampir habis → parkir sampai reset (bila header reset tersedia).
        if ($remaining <= $pauseThreshold) {
            $reset = $this->headerValue($headers, 'x-ratelimit-reset');
            $until = $this->resolveResetTime($reset);

            if ($until !== null && $until > ($this->clock)()) {
                $this->park($credentialKey, (int) ceil($until - ($this->clock)()));

                return;
            }

            // Tanpa header reset: perlambat drastis (1 request per 2 detik).
            Cache::put(
                $this->bucketKey($credentialKey, $family),
                ['next' => ($this->clock)() + 2.0],
                now()->addMinutes(10),
            );

            return;
        }

        if ($remaining < $slowThreshold && $remaining < $limit) {
            $ratio = $remaining / $limit;
            $extra = (1.0 - $ratio) * 2.0;

            Cache::put(
                $this->bucketKey($credentialKey, $family),
                ['next' => ($this->clock)() + $extra],
                now()->addMinutes(10),
            );
        }
    }

    /**
     * L4 — parkir kredensial sementara setelah 429 berulang.
     */
    public function park(string $credentialKey, ?int $seconds = null): void
    {
        $seconds ??= (int) config('inaproc.rate.cooldown', 120);

        Cache::put($this->parkKey($credentialKey), ($this->clock)() + $seconds, now()->addHours(1));
    }

    public function isParked(string $credentialKey): bool
    {
        $until = Cache::get($this->parkKey($credentialKey));

        return is_float($until) || is_int($until) ? $until > ($this->clock)() : false;
    }

    /**
     * Waktu tunggu hingga kredensial dilepas dari parkir (detik, 0 bila bebas).
     */
    public function parkedFor(string $credentialKey): int
    {
        $until = Cache::get($this->parkKey($credentialKey));

        if (! is_float($until) && ! is_int($until)) {
            return 0;
        }

        return max(0, (int) ceil($until - ($this->clock)()));
    }

    public function release(string $credentialKey): void
    {
        Cache::forget($this->parkKey($credentialKey));
    }

    private function waitIfParked(string $credentialKey): void
    {
        $wait = $this->parkedFor($credentialKey);

        if ($wait > 0) {
            ($this->sleeper)($wait * 1_000_000);
        }
    }

    private function bucketKey(string $credentialKey, ApiFamily $family): string
    {
        return 'inaproc:rate:'.$family->value.':'.hash('sha256', $credentialKey);
    }

    private function parkKey(string $credentialKey): string
    {
        return 'inaproc:park:'.hash('sha256', $credentialKey);
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function headerValue(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $value) {
            if (strtolower($key) === $name) {
                return is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
            }
        }

        return null;
    }

    private function resolveResetTime(?string $reset): ?float
    {
        if ($reset === null || $reset === '') {
            return null;
        }

        if (is_numeric($reset)) {
            $value = (float) $reset;

            // Epoch detik/milidetik, atau durasi detik.
            if ($value > 1_000_000_000) {
                return $value > 10_000_000_000 ? $value / 1000 : $value;
            }

            return ($this->clock)() + $value;
        }

        $ts = strtotime($reset);

        return $ts === false ? null : (float) $ts;
    }
}
