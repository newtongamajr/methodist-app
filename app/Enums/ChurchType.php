<?php

namespace App\Enums;

enum ChurchType: string
{
    case Church = 'church';
    case MissionaryPoint = 'missionary_point';

    public function label(): string
    {
        return match ($this) {
            self::Church => __('Local church'),
            self::MissionaryPoint => __('Missionary point'),
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
