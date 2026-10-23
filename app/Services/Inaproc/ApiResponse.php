<?php

declare(strict_types=1);

namespace App\Services\Inaproc;

/**
 * Hasil satu permintaan HTTP ke API INAPROC.
 */
final class ApiResponse
{
    /**
     * @param  array<int|string, mixed>|null  $data  Baris data (null bila gagal).
     * @param  array<string, mixed>  $meta
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public readonly int $status,
        public readonly ?array $data,
        public readonly array $meta = [],
        public readonly array $headers = [],
        public readonly bool $isJson = true,
        public readonly ?string $error = null,
        public readonly bool $blocked = false,
        public readonly int $requests = 1,
    ) {}

    public static function failure(int $status, string $error, bool $blocked = false, int $requests = 1): self
    {
        return new self(
            status: $status,
            data: null,
            isJson: false,
            error: $error,
            blocked: $blocked,
            requests: $requests,
        );
    }

    public function ok(): bool
    {
        return $this->status === 200 && $this->data !== null;
    }

    public function isEmpty(): bool
    {
        return $this->ok() && $this->data === [];
    }

    public function hasMore(): bool
    {
        // F3.1: lanjut hanya bila has_more true DAN cursor terisi.
        $hasMore = $this->meta['has_more'] ?? false;
        $cursor = $this->meta['cursor'] ?? null;

        return (bool) $hasMore && is_string($cursor) && $cursor !== '';
    }

    public function cursor(): ?string
    {
        $cursor = $this->meta['cursor'] ?? null;

        return is_string($cursor) && $cursor !== '' ? $cursor : null;
    }
}
