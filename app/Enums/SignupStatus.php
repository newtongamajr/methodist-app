<?php

namespace App\Enums;

enum SignupStatus: string
{
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => __('Confirmed'),
            self::Cancelled => __('Cancelled'),
        };
    }
}
