<?php

declare(strict_types=1);

namespace App\Enums;

enum EndpointScope: string
{
    /** kode_klpd + tahun dikirim */
    case KLPD = 'klpd';

    /** hanya tahun yang dikirim */
    case TAHUN = 'tahun';

    /** parameter turunan (kode_penyedia, kd_kategori_*) */
    case DERIVED = 'derived';
}
