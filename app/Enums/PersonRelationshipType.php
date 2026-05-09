<?php

namespace App\Enums;

enum PersonRelationshipType: string
{
    case ParentOf = 'parent_of';
    case ChildOf = 'child_of';
    case GodparentOf = 'godparent_of';
    case GodchildOf = 'godchild_of';
    case GuardianOf = 'guardian_of';
    case WardOf = 'ward_of';
    case Spouse = 'spouse';

    public function label(): string
    {
        return match ($this) {
            self::ParentOf => __('Parent of'),
            self::ChildOf => __('Child of'),
            self::GodparentOf => __('Godparent of'),
            self::GodchildOf => __('Godchild of'),
            self::GuardianOf => __('Guardian of'),
            self::WardOf => __('Ward of'),
            self::Spouse => __('Spouse'),
        };
    }

    public function inverse(): self
    {
        return match ($this) {
            self::ParentOf => self::ChildOf,
            self::ChildOf => self::ParentOf,
            self::GodparentOf => self::GodchildOf,
            self::GodchildOf => self::GodparentOf,
            self::GuardianOf => self::WardOf,
            self::WardOf => self::GuardianOf,
            self::Spouse => self::Spouse,
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
