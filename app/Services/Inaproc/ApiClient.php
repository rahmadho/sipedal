<?php

declare(strict_types=1);

namespace App\Services\Inaproc;

use App\Enums\ApiFamily;
use App\Support\KodeNormalizer;
use App\Support\RateLimiter;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Klien HTTP INAPROC (F3, F4, F7, F9).
 *
 * Tanggung jawab:
 *  - mengirim satu request dengan header autentikasi yang benar
 *  - membedakan 200 / 403 / 429 / 5xx / non-JSON / blokir jaringan
 *  - retry terbatas untuk gangguan sementara (429 & 5xx & koneksi)
 *  - tidak pernah mencatat token mentah
 */
class ApiClient
{
    public function __construct(
        private readonly RateLimiter $rateLimiter,
    ) {}

    /**
     * Kirim satu request. Tidak melakukan pagination.
     *
     * @param  array<string, string|int>  $params
     */
    public function get(
        string $path,
        array $params,
        ApiFamily $family,
        ?string $token,
        string $credentialKey,
    ): ApiResponse {
        $url = rtrim((string) config('inaproc.base_url'), '/').'/'.ltrim($path, '/');
        $maxRetry = max(0, (int) config('inaproc.max_retry', 4));
        $backoff = max(1, (int) config('inaproc.retry_backoff', 2));

        $attempt = 0;
        $requests = 0;
        $lastError = null;

        while (true) {
            $attempt++;
            $this->rateLimiter->acquire($credentialKey, $family);
            $requests++;

            try {
                $response = Http::withHeaders($this->authHeaders($token))
                    ->timeout((int) config('inaproc.timeout', 120))
                    ->connectTimeout((int) config('inaproc.connect_timeout', 20))
                    ->withOptions(['http_errors' => false])
                    ->get($url, $params);
            } catch (ConnectionException $e) {
                $lastError = 'Koneksi gagal: '.$e->getMessage();

                if ($attempt > $maxRetry) {
                    return ApiResponse::failure(0, $this->trim($lastError), false, $requests);
                }

                $this->sleep($this->backoffSeconds($attempt, $backoff));

                continue;
            } catch (Throwable $e) {
                return ApiResponse::failure(0, $this->trim('Kesalahan tak terduga: '.$e->getMessage()), false, $requests);
            }

            $this->rateLimiter->observe($credentialKey, $family, $this->headers($response));
            $status = $response->status();

            // 429 — hormati Retry-After, backoff eksponensial, maksimum retry.
            if ($status === 429) {
                $maxRateRetry = max(0, (int) config('inaproc.rate.max_retry', 6));

                if ($attempt > $maxRateRetry) {
                    $this->rateLimiter->park($credentialKey);

                    return ApiResponse::failure(
                        429,
                        'Batas laju terlampaui setelah '.$attempt.' percobaan',
                        false,
                        $requests,
                    );
                }

                $this->sleep($this->retryAfterSeconds($response, $attempt, $backoff));

                continue;
            }

            // 5xx — retry terbatas.
            if ($status >= 500) {
                $lastError = 'Server membalas HTTP '.$status;

                if ($attempt > $maxRetry) {
                    return ApiResponse::failure($status, $lastError, false, $requests);
                }

                $this->sleep($this->backoffSeconds($attempt, $backoff));

                continue;
            }

            return $this->interpret($response, $requests);
        }
    }

    /**
     * Ubah respons HTTP menjadi ApiResponse, termasuk deteksi blokir (F9).
     */
    private function interpret(Response $response, int $requests): ApiResponse
    {
        $status = $response->status();
        $body = $response->body();
        $headers = $this->headers($response);
        $contentType = strtolower($headers['content-type'] ?? '');

        // Bukan JSON (halaman HTML error) — tidak boleh melempar exception.
        $decoded = $this->decode($body);

        if ($decoded === null) {
            $blocked = $status === 403 || $status === 503
                ? $this->looksBlocked($body, $contentType)
                : false;

            $message = $blocked
                ? 'Diblokir: akses ditolak oleh pengaman jaringan, ulangi nanti'
                : ($status === 200
                    ? 'Respons bukan JSON yang valid'
                    : 'Respons bukan JSON (HTTP '.$status.')');

            return new ApiResponse(
                status: $status,
                data: null,
                headers: $headers,
                isJson: false,
                error: $message,
                blocked: $blocked,
                requests: $requests,
            );
        }

        if ($status !== 200) {
            return new ApiResponse(
                status: $status,
                data: null,
                meta: $this->extractMeta($decoded),
                headers: $headers,
                isJson: true,
                error: $this->errorMessage($status, $decoded, $body, $contentType),
                blocked: $status === 403 && $this->looksBlocked($body, $contentType),
                requests: $requests,
            );
        }

        $data = $this->extractData($decoded);

        return new ApiResponse(
            status: 200,
            data: $data,
            meta: $this->extractMeta($decoded),
            headers: $headers,
            isJson: true,
            requests: $requests,
        );
    }

    /**
     * Ambil baris data dari respons.
     *
     * v1  : {"meta": {...}, "success": true, "data": [...]}
     * legacy: array langsung, atau objek tunggal.
     *
     * @return array<int|string, mixed>
     */
    private function extractData(mixed $decoded): array
    {
        if (! is_array($decoded)) {
            return [];
        }

        // Respons bersampul (v1 atau gaya standar gateway).
        if (array_key_exists('data', $decoded)) {
            $data = $decoded['data'];

            if (is_array($data)) {
                return $data;
            }

            return $data === null ? [] : [$data];
        }

        // legacy: array baris langsung.
        if (array_is_list($decoded)) {
            return $decoded;
        }

        // legacy: objek tunggal.
        return [$decoded];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractMeta(mixed $decoded): array
    {
        if (! is_array($decoded)) {
            return [];
        }

        $meta = $decoded['meta'] ?? [];

        if (! is_array($meta)) {
            return [];
        }

        return $meta;
    }

    private function decode(string $body): mixed
    {
        if ($body === '') {
            return [];
        }

        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private function errorMessage(int $status, array $decoded, string $body, string $contentType): string
    {
        if ($status === 403) {
            if ($this->looksBlocked($body, $contentType)) {
                return 'Diblokir: akses ditolak oleh pengaman jaringan, ulangi nanti';
            }

            $message = $this->firstMessage($decoded);

            // Bedakan "token tidak berlaku" vs "endpoint belum diizinkan".
            if ($message !== null && preg_match('/endpoint|akses|scope|izin/i', $message) === 1
                && preg_match('/token|kredensial|kunci/i', $message) !== 1) {
                return 'Akses endpoint belum diizinkan';
            }

            return 'Ditolak: token tidak valid untuk KLPD ini';
        }

        // 401 = kredensial tidak diterima. Gateway INAPROC membalas
        // {"error":"Authorization field missing"} bila header auth salah bentuk,
        // dan itu harus terbaca jelas di pull_log.
        if ($status === 401) {
            $message = $this->firstMessage($decoded);

            return $message !== null
                ? $this->trim('Ditolak: token tidak diterima (HTTP 401) — '.$message)
                : 'Ditolak: token tidak diterima (HTTP 401)';
        }

        $message = $this->firstMessage($decoded);

        return $message !== null
            ? $this->trim('HTTP '.$status.': '.$message)
            : 'HTTP '.$status;
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private function firstMessage(array $decoded): ?string
    {
        foreach (['message', 'error', 'pesan', 'detail'] as $key) {
            $value = $decoded[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return $this->trim($value);
            }
        }

        return null;
    }

    private function looksBlocked(string $body, string $contentType): bool
    {
        if (str_contains($contentType, 'text/html')) {
            return true;
        }

        $haystack = strtolower($body);

        foreach ((array) config('inaproc.block_markers', []) as $marker) {
            if ($marker !== '' && str_contains($haystack, strtolower((string) $marker))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(?string $token): array
    {
        $header = (string) config('inaproc.auth_header', 'X-API-Key');
        $scheme = trim((string) config('inaproc.auth_scheme', ''));

        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => 'inaproc-pipeline/1.0 (+laravel)',
        ];

        if ($token === null || $token === '') {
            return $headers;
        }

        $headers[$header] = $scheme === '' ? $token : $scheme.' '.$token;

        return $headers;
    }

    /**
     * @return array<string, string>
     */
    private function headers(Response $response): array
    {
        $out = [];

        foreach ($response->headers() as $key => $values) {
            $out[strtolower($key)] = is_array($values) ? (string) ($values[0] ?? '') : (string) $values;
        }

        return $out;
    }

    private function retryAfterSeconds(Response $response, int $attempt, int $backoff): int
    {
        $retryAfter = $response->header('Retry-After');

        if (is_string($retryAfter) && $retryAfter !== '') {
            if (is_numeric($retryAfter)) {
                return max(1, min(600, (int) $retryAfter));
            }

            $ts = strtotime($retryAfter);

            if ($ts !== false) {
                return max(1, min(600, $ts - time()));
            }
        }

        return $this->backoffSeconds($attempt, $backoff);
    }

    private function backoffSeconds(int $attempt, int $base): int
    {
        return min(300, $base ** max(1, $attempt));
    }

    private function sleep(int $seconds): void
    {
        if ($seconds > 0) {
            sleep($seconds);
        }
    }

    private function trim(?string $message, int $limit = 255): string
    {
        $message = (string) $message;

        return mb_strlen($message) > $limit ? mb_substr($message, 0, $limit) : $message;
    }

    /**
     * Bantu diagnosa: samarkan token untuk log.
     */
    public function maskedToken(?string $token): string
    {
        return KodeNormalizer::maskToken($token);
    }
}
