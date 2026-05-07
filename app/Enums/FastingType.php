<?php

namespace App\Enums;

enum FastingType: string
{
    case TwentyFourHours = 'h24';
    case TwelveHours = 'h12';
    case Breakfast = 'breakfast';
    case Lunch = 'lunch';
    case Dinner = 'dinner';
    case Partial = 'partial';

    public function label(): string
    {
        return match ($this) {
            self::TwentyFourHours => __('24-hour fast'),
            self::TwelveHours => __('12-hour fast'),
            self::Breakfast => __('Skip breakfast'),
            self::Lunch => __('Skip lunch'),
            self::Dinner => __('Skip dinner'),
            self::Partial => __('Partial / restrictions only'),
        };
    }

    /**
     * Hex color used to tint the calendar cell when this fast type is logged.
     */
    public function color(): string
    {
        return match ($this) {
            self::TwentyFourHours => '#7a1620', // deep methodist red
            self::TwelveHours => '#c8202f',     // methodist red
            self::Breakfast => '#f59e0b',       // amber
            self::Lunch => '#0284c7',           // sky blue
            self::Dinner => '#6366f1',          // indigo
            self::Partial => '#10b981',         // emerald
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
