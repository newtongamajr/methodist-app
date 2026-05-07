<?php

namespace App\Enums;

enum MemberType: string
{
    case Member = 'member';
    case Interested = 'interested';
    case Teenager = 'teenager';
    case Child = 'child';

    public function label(): string
    {
        return match ($this) {
            self::Member => __('Member'),
            self::Interested => __('Interested'),
            self::Teenager => __('Teenager'),
            self::Child => __('Child'),
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
