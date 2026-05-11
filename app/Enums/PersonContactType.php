<?php

namespace App\Enums;

enum PersonContactType: string
{
    case Email = 'email';
    case Phone = 'phone';
    case Mobile = 'mobile';
    case Whatsapp = 'whatsapp';
    case Social = 'social';
    case Website = 'website';

    public function label(): string
    {
        return match ($this) {
            self::Email => __('Email'),
            self::Phone => __('Phone'),
            self::Mobile => __('Mobile'),
            self::Whatsapp => __('WhatsApp'),
            self::Social => __('Social'),
            self::Website => __('Website'),
        };
    }

    /** Whether this contact type stores a phone number (and therefore needs a country). */
    public function isPhone(): bool
    {
        return in_array($this, [self::Phone, self::Mobile, self::Whatsapp], true);
    }

    /** Whether the value should be masked as a mobile number (vs a fixed-line one). */
    public function usesMobileFormat(): bool
    {
        return in_array($this, [self::Mobile, self::Whatsapp], true);
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
