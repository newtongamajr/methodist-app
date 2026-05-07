<?php

namespace App\Enums;

enum FastingRestriction: string
{
    case Meat = 'meat';
    case Sweets = 'sweets';
    case SoftDrinks = 'soft_drinks';
    case Coffee = 'coffee';
    case WaterOnly = 'water_only';
    case SocialMedia = 'social_media';
    case Entertainment = 'entertainment';
    case ProcessedFoods = 'processed_foods';

    public function label(): string
    {
        return match ($this) {
            self::Meat => __('Meat'),
            self::Sweets => __('Sweets'),
            self::SoftDrinks => __('Soft drinks'),
            self::Coffee => __('Coffee'),
            self::WaterOnly => __('No food, just water'),
            self::SocialMedia => __('Social media'),
            self::Entertainment => __('TV / entertainment'),
            self::ProcessedFoods => __('Processed foods'),
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
