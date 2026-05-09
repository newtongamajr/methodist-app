<?php

namespace App\Enums;

enum MaritalStatus: string
{
    case Single = 'single';
    case Married = 'married';
    case StableUnion = 'stable_union';
    case Separated = 'separated';
    case Divorced = 'divorced';
    case Widowed = 'widowed';

    public function label(): string
    {
        return match ($this) {
            self::Single => __('Single'),
            self::Married => __('Married'),
            self::StableUnion => __('Stable union'),
            self::Separated => __('Separated'),
            self::Divorced => __('Divorced'),
            self::Widowed => __('Widowed'),
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
