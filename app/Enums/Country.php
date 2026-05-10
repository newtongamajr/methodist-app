<?php

namespace App\Enums;

/**
 * Curated country list focused on Brazilian Methodist mission/diaspora plus
 * common partner countries. Each case carries:
 *   - value: ISO 3166-1 alpha-2 (e.g. "BR")
 *   - phoneCode(): E.164 calling code without the leading "+"
 *   - fixedMask() / mobileMask(): x-mask patterns ("9" = digit, "a" = alpha,
 *       "*" = any). Null when the local format is too variable for a static mask.
 *   - zipMask(): same conventions for postal codes.
 *
 * Extend the list as needed — the enum is the source of truth for both the
 * register form and the People → Contacts/Addresses tabs.
 */
enum Country: string
{
    case BR = 'BR';
    case US = 'US';
    case CA = 'CA';
    case GB = 'GB';
    case PT = 'PT';
    case ES = 'ES';
    case IT = 'IT';
    case FR = 'FR';
    case DE = 'DE';
    case CH = 'CH';
    case NL = 'NL';
    case BE = 'BE';
    case IE = 'IE';
    case SE = 'SE';
    case NO = 'NO';
    case AR = 'AR';
    case CL = 'CL';
    case UY = 'UY';
    case PY = 'PY';
    case BO = 'BO';
    case PE = 'PE';
    case CO = 'CO';
    case MX = 'MX';
    case AU = 'AU';
    case NZ = 'NZ';
    case JP = 'JP';
    case CN = 'CN';
    case KR = 'KR';
    case AO = 'AO';
    case MZ = 'MZ';
    case ZA = 'ZA';
    case IL = 'IL';

    public function label(): string
    {
        return match ($this) {
            self::BR => __('Brazil'),
            self::US => __('United States'),
            self::CA => __('Canada'),
            self::GB => __('United Kingdom'),
            self::PT => __('Portugal'),
            self::ES => __('Spain'),
            self::IT => __('Italy'),
            self::FR => __('France'),
            self::DE => __('Germany'),
            self::CH => __('Switzerland'),
            self::NL => __('Netherlands'),
            self::BE => __('Belgium'),
            self::IE => __('Ireland'),
            self::SE => __('Sweden'),
            self::NO => __('Norway'),
            self::AR => __('Argentina'),
            self::CL => __('Chile'),
            self::UY => __('Uruguay'),
            self::PY => __('Paraguay'),
            self::BO => __('Bolivia'),
            self::PE => __('Peru'),
            self::CO => __('Colombia'),
            self::MX => __('Mexico'),
            self::AU => __('Australia'),
            self::NZ => __('New Zealand'),
            self::JP => __('Japan'),
            self::CN => __('China'),
            self::KR => __('South Korea'),
            self::AO => __('Angola'),
            self::MZ => __('Mozambique'),
            self::ZA => __('South Africa'),
            self::IL => __('Israel'),
        };
    }

    /** E.164 calling code without leading "+". */
    public function phoneCode(): string
    {
        return match ($this) {
            self::BR => '55',
            self::US, self::CA => '1',
            self::GB => '44',
            self::PT => '351',
            self::ES => '34',
            self::IT => '39',
            self::FR => '33',
            self::DE => '49',
            self::CH => '41',
            self::NL => '31',
            self::BE => '32',
            self::IE => '353',
            self::SE => '46',
            self::NO => '47',
            self::AR => '54',
            self::CL => '56',
            self::UY => '598',
            self::PY => '595',
            self::BO => '591',
            self::PE => '51',
            self::CO => '57',
            self::MX => '52',
            self::AU => '61',
            self::NZ => '64',
            self::JP => '81',
            self::CN => '86',
            self::KR => '82',
            self::AO => '244',
            self::MZ => '258',
            self::ZA => '27',
            self::IL => '972',
        };
    }

    /** Fixed-line phone mask (x-mask format). Null when format is too variable. */
    public function fixedMask(): ?string
    {
        return match ($this) {
            self::BR => '(99) 9999-9999',
            self::US, self::CA => '(999) 999-9999',
            self::PT => '999 999 999',
            self::ES => '999 999 999',
            self::FR => '99 99 99 99 99',
            self::IT => '999 9999999',
            self::DE => '999 99999999',
            self::CH => '999 999 99 99',
            self::NL => '999 9999999',
            self::BE => '99 999 99 99',
            self::IE => '99 999 9999',
            self::SE => '999 999 9999',
            self::NO => '999 99 999',
            self::AR => '(999) 9999-9999',
            self::CL => '99 9999 9999',
            self::UY => '9999 9999',
            self::PY => '999 999 999',
            self::BO => '9 999 9999',
            self::PE => '999 999 999',
            self::CO => '(999) 999 9999',
            self::MX => '999 999 9999',
            self::AU => '99 9999 9999',
            self::NZ => '99 999 9999',
            self::JP => '99-9999-9999',
            self::CN => '999 9999 9999',
            self::KR => '99-9999-9999',
            self::AO => '999 999 999',
            self::MZ => '99 999 9999',
            self::ZA => '99 999 9999',
            self::IL => '99-999-9999',
            self::GB => null,
        };
    }

    /** Mobile phone mask (x-mask format). Null when format is too variable. */
    public function mobileMask(): ?string
    {
        return match ($this) {
            self::BR => '(99) 99999-9999',
            self::US, self::CA => '(999) 999-9999',
            self::PT => '999 999 999',
            self::ES => '999 999 999',
            self::FR => '99 99 99 99 99',
            self::IT => '999 999 9999',
            self::DE => '9999 9999999',
            self::CH => '999 999 99 99',
            self::NL => '99 99999999',
            self::BE => '999 99 99 99',
            self::IE => '999 999 9999',
            self::SE => '999 999 9999',
            self::NO => '999 99 999',
            self::AR => '(999) 99 9999-9999',
            self::CL => '9 9999 9999',
            self::UY => '999 999 999',
            self::PY => '(999) 999 999',
            self::BO => '9 9999 9999',
            self::PE => '999 999 999',
            self::CO => '(999) 999 9999',
            self::MX => '999 999 9999',
            self::AU => '999 999 999',
            self::NZ => '99 999 9999',
            self::JP => '999-9999-9999',
            self::CN => '999 9999 9999',
            self::KR => '999-9999-9999',
            self::AO => '999 999 999',
            self::MZ => '99 999 9999',
            self::ZA => '99 999 9999',
            self::IL => '999-999-9999',
            self::GB => null,
        };
    }

    /** Postal-code mask (x-mask format). Null when format is too variable. */
    public function zipMask(): ?string
    {
        return match ($this) {
            self::BR => '99999-999',
            self::US => '99999',
            self::CA => 'a9a 9a9',
            self::GB => null,
            self::PT => '9999-999',
            self::ES => '99999',
            self::IT => '99999',
            self::FR => '99999',
            self::DE => '99999',
            self::CH => '9999',
            self::NL => '9999 aa',
            self::BE => '9999',
            self::IE => null,
            self::SE => '999 99',
            self::NO => '9999',
            self::AR => null,
            self::CL => '9999999',
            self::UY => '99999',
            self::PY => '9999',
            self::BO => null,
            self::PE => '99999',
            self::CO => '999999',
            self::MX => '99999',
            self::AU => '9999',
            self::NZ => '9999',
            self::JP => '999-9999',
            self::CN => '999999',
            self::KR => '99999',
            self::AO => null,
            self::MZ => '9999',
            self::ZA => '9999',
            self::IL => '9999999',
        };
    }

    /** @return array<string, string> Map of ISO code → translated country name. */
    public static function options(): array
    {
        return collect(self::cases())
            ->sortBy(fn (self $case) => $case->label())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
