<?php

namespace App\Enums;

enum AppLocale: string
{
    case PtBR = 'pt_BR';
    case En = 'en';
    case Es = 'es';

    public function label(): string
    {
        return match ($this) {
            self::PtBR => __('Portuguese'),
            self::En => __('English'),
            self::Es => __('Spanish'),
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
