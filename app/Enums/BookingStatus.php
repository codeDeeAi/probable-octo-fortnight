<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\v1\EnumHelpers;

enum BookingStatus: string
{
    use EnumHelpers;

    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
}
