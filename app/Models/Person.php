<?php

namespace App\Models;

use App\Enums\PersonNature;
use App\Enums\PersonType;
use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'persons';

    protected $fillable = [
        'person_type',
        'name',
        'preferred_name',
        'tax_id',
        'tax_id_type',
        'birthdate',
        'gender',
        'marital_status',
        'photo_path',
        'natures',
        'additional_data',
        'managing_church_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'person_type' => PersonType::class,
            'birthdate' => 'date',
            'natures' => 'array',
            'additional_data' => 'array',
        ];
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function managingChurch(): BelongsTo
    {
        return $this->belongsTo(Church::class, 'managing_church_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(PersonContact::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(PersonAddress::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PersonDocument::class);
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(PersonRelationship::class, 'person_id');
    }

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(PersonRoleAssignment::class);
    }

    public function hasNature(PersonNature|string $nature): bool
    {
        $value = $nature instanceof PersonNature ? $nature->value : $nature;

        return in_array($value, $this->natures ?? [], true);
    }

    public function addNature(PersonNature|string $nature): void
    {
        $value = $nature instanceof PersonNature ? $nature->value : $nature;
        $natures = $this->natures ?? [];
        if (! in_array($value, $natures, true)) {
            $natures[] = $value;
            $this->natures = $natures;
        }
    }

    public function removeNature(PersonNature|string $nature): void
    {
        $value = $nature instanceof PersonNature ? $nature->value : $nature;
        $this->natures = array_values(array_filter(
            $this->natures ?? [],
            fn (string $n) => $n !== $value,
        ));
    }

    /** @return array<string, mixed> */
    public function dataForNature(PersonNature|string $nature): array
    {
        $value = $nature instanceof PersonNature ? $nature->value : $nature;

        return ($this->additional_data ?? [])[$value] ?? [];
    }
}
