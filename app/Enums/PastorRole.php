<?php

namespace App\Enums;

enum PastorRole: string
{
    case Main = 'main';
    case Auxiliary = 'auxiliary';
    case Seminarist = 'seminarist';

    public function label(): string
    {
        return match ($this) {
            self::Main => __('Main pastor'),
            self::Auxiliary => __('Auxiliary pastor'),
            self::Seminarist => __('Seminarist'),
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
