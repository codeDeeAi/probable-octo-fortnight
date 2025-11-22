<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\v1\EnumHelpers;

enum UserRoles: string
{
    use EnumHelpers;

    case Admin = 'admin';
    case Organizer = 'organizer';
    case Customer = 'customer';
}
