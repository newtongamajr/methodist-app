<?php

namespace App\Enums;

enum PersonNature: string
{
    // Individual natures.
    case Member = 'member';
    case Pastor = 'pastor';
    case Visitor = 'visitor';
    case Interested = 'interested';
    case Youth = 'youth';
    case Teenager = 'teenager';
    case Child = 'child';

    // Organizational natures (each row in the corresponding org table is
    // backed by an Organization-type Person carrying name / CNPJ / contacts /
    // addresses / documents).
    case NationalHeadquarters = 'national_headquarters';
    case EcclesiasticalRegion = 'ecclesiastical_region';
    case District = 'district';
    case Church = 'church';

    public function label(): string
    {
        return match ($this) {
            self::Member => __('Member'),
            self::Pastor => __('Pastor'),
            self::Visitor => __('Visitor'),
            self::Interested => __('Interested'),
            self::Youth => __('Youth'),
            self::Teenager => __('Teenager'),
            self::Child => __('Child'),
            self::NationalHeadquarters => __('National headquarters'),
            self::EcclesiasticalRegion => __('Ecclesiastical region'),
            self::District => __('District'),
            self::Church => __('Church'),
        };
    }

    /** Natures that an org-Person can carry (used to keep org Persons out of the people index by default). */
    public static function organizational(): array
    {
        return [
            self::NationalHeadquarters->value,
            self::EcclesiasticalRegion->value,
            self::District->value,
            self::Church->value,
        ];
    }

    public function isOrganizational(): bool
    {
        return in_array($this->value, self::organizational(), true);
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }

    /** Just the individual natures, for the People index filter and individual editor. */
    public static function individualOptions(): array
    {
        return collect(self::cases())
            ->reject(fn (self $case) => $case->isOrganizational())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }

    /** Just the organizational natures, for the org-Person editor. */
    public static function organizationalOptions(): array
    {
        return collect(self::cases())
            ->filter(fn (self $case) => $case->isOrganizational())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }

    /** Natures valid for a given person_type ('individual' or 'organization'). */
    public static function optionsForPersonType(PersonType|string $type): array
    {
        $value = $type instanceof PersonType ? $type->value : $type;

        return $value === PersonType::Organization->value
            ? self::organizationalOptions()
            : self::individualOptions();
    }
}
