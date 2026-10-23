<?php

declare(strict_types=1);

namespace App\Enums;

enum ApiFamily: string
{
    case V1 = 'v1';
    case LEGACY = 'legacy';

    public function isCursorPaged(): bool
    {
        return $this === self::V1;
    }

    public function ratePerMinute(): int
    {
        return $this === self::V1
            ? (int) config('inaproc.rate.v1_per_min', 300)
            : (int) config('inaproc.rate.legacy_per_min', 90);
    }
}
