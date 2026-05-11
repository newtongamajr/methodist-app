<?php

namespace App\Models;

use App\Enums\ChurchType;
use App\Enums\GroupKind;
use App\Enums\LocationMode;
use Database\Factories\ChurchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Church extends Model
{
    /** @use HasFactory<ChurchFactory> */
    use HasFactory;

    protected $fillable = [
        'person_id',
        'ecclesiastical_region_id',
        'district_id',
        'type',
        'name',
        'slug',
        'code',
        'address',
        'city',
        'state',
        'zip',
        'latitude',
        'longitude',
        'timezone',
        'max_prayers_per_slot',
        'default_mode',
        'phone',
        'email',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'max_prayers_per_slot' => 'integer',
            'type' => ChurchType::class,
            'default_mode' => LocationMode::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /**
     * Each Church row is backed by an Organization-type Person carrying the
     * authoritative name / CNPJ / contacts / addresses / documents. The
     * cached `name` / `email` / `phone` / `address` columns on this table
     * mirror Person values for query convenience but Person is the source
     * of truth.
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(EcclesiasticalRegion::class, 'ecclesiastical_region_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function pastorAssignments(): HasMany
    {
        return $this->hasMany(PersonRoleAssignment::class)
            ->whereHas('function', fn ($q) => $q->whereJsonContains('applies_to', 'pastor'))
            ->orderBy('started_at');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /** Groups scoped to this church (church-level councils/ministries/commissions). */
    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function groupsByKind(GroupKind|string $kind): HasMany
    {
        $value = $kind instanceof GroupKind ? $kind->value : $kind;

        return $this->groups()->where('kind', $value);
    }
}
