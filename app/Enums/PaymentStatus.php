<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\v1\EnumHelpers;

enum PaymentStatus: string
{
    use EnumHelpers;

    case Success = 'success';
    case Failed = 'failed';
    case Refunded = 'refunded';
}
