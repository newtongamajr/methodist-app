<?php

namespace App\Enums;

enum RegionKind: string
{
    case Regular = 'regular';
    case Missionary = 'missionary';

    public function label(): string
    {
        return match ($this) {
            self::Regular => __('Regular region'),
            self::Missionary => __('Missionary region'),
        };
    }
}
