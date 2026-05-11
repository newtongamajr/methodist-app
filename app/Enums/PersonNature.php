<?php

namespace App\Enums;

enum PersonNature: string
{
    case Member = 'member';
    case Pastor = 'pastor';
    case Visitor = 'visitor';
    case Interested = 'interested';
    case Teenager = 'teenager';
    case Child = 'child';

    public function label(): string
    {
        return match ($this) {
            self::Member => __('Member'),
            self::Pastor => __('Pastor'),
            self::Visitor => __('Visitor'),
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
