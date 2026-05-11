<?php

namespace App\Enums;

enum Gender: string
{
    case Female = 'female';
    case Male = 'male';

    public function label(): string
    {
        return match ($this) {
            self::Female => __('Female'),
            self::Male => __('Male'),
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
