<?php

namespace App\Enums;

enum RegionKind: string
{
    case NationalHeadquarters = 'national_headquarters';
    case Regular = 'regular';
    case Missionary = 'missionary';

    public function label(): string
    {
        return match ($this) {
            self::NationalHeadquarters => __('National headquarters'),
            self::Regular => __('Regular region'),
            self::Missionary => __('Missionary region'),
        };
    }
}
