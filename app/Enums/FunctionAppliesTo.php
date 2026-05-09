<?php

namespace App\Enums;

enum FunctionAppliesTo: string
{
    case Admin = 'admin';
    case Pastor = 'pastor';
    case Council = 'council';
    case Ministry = 'ministry';
    case Commission = 'commission';

    public function label(): string
    {
        return match ($this) {
            self::Admin => __('Admin'),
            self::Pastor => __('Pastor'),
            self::Council => __('Council'),
            self::Ministry => __('Ministry'),
            self::Commission => __('Commission'),
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
