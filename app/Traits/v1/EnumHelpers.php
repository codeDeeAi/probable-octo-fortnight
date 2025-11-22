<?php

declare(strict_types=1);

namespace App\Traits\v1;

trait EnumHelpers
{
    public static function toArray(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }

    public static function inValidationRule(array $exclude = []): string
    {
        return implode(',', self::values($exclude));
    }

    public static function values(array $exclude = []): array
    {
        return array_values(array_filter(
            array_column(self::cases(), 'value'),
            fn ($value) => ! in_array($value, $exclude, true)
        ));
    }
}
