<?php

namespace App\Enums;

enum LocationMode: string
{
    case Presential = 'presential';
    case Home = 'home';

    public function label(): string
    {
        return match ($this) {
            self::Presential => __('At the church'),
            self::Home => __('From home'),
        };
    }
}
