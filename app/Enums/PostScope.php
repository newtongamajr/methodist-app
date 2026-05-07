<?php

namespace App\Enums;

enum PostScope: string
{
    case Shared = 'shared';
    case Local = 'local';

    public function label(): string
    {
        return match ($this) {
            self::Shared => __('Shared with all churches'),
            self::Local => __('Local to one church'),
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
