<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\v1\EnumHelpers;

enum TicketTypes: string
{
    use EnumHelpers;

    case VIP = 'vip';
    case Standard = 'standard';
    case Economy = 'economy';
}
