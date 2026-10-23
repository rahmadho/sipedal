<?php

declare(strict_types=1);

namespace App\Enums;

enum PullStatus: string
{
    case OK = 'OK';
    case KOSONG = 'KOSONG';
    case GAGAL = 'GAGAL';
    case FORBIDDEN = 'FORBIDDEN';
    case NONAKTIF = 'NONAKTIF';

    public function isFailure(): bool
    {
        return $this === self::GAGAL || $this === self::FORBIDDEN;
    }
}
