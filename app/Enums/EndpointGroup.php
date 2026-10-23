<?php

declare(strict_types=1);

namespace App\Enums;

enum EndpointGroup: string
{
    case RUP = 'rup';
    case TENDER = 'tender';
    case EKATALOG = 'ekatalog';
    case BELA = 'bela';
    case MASTER = 'master';
}
