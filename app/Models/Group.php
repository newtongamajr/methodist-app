<?php

namespace App\Models;

use App\Enums\GroupKind;
use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    protected $fillable = [
        'kind',
        'name',
        'slug',
        'description',
        'ecclesiastical_region_id',
        'district_id',
        'church_id',
        'started_at',
        'ended_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'kind' => GroupKind::class,
            'started_at' => 'date',
            'ended_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(EcclesiasticalRegion::class, 'ecclesiastical_region_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(PersonRoleAssignment::class);
    }

    public function level(): string
    {
        return match (true) {
            $this->church_id !== null => 'church',
            $this->district_id !== null => 'district',
            $this->ecclesiastical_region_id !== null => 'region',
            default => 'national',
        };
    }

    public function scopeNational(Builder $q): Builder
    {
        return $q->whereNull('ecclesiastical_region_id')
            ->whereNull('district_id')
            ->whereNull('church_id');
    }

    public function scopeOfKind(Builder $q, GroupKind|string $kind): Builder
    {
        $value = $kind instanceof GroupKind ? $kind->value : $kind;

        return $q->where('kind', $value);
    }

    /**
     * All Persons currently active in this group, regardless of which function
     * they hold. Active = no ended_at or ended_at in the future.
     */
    public function members(): Collection
    {
        $today = now()->toDateString();

        return $this->assignments()
            ->with('person:id,name')
            ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>=', $today))
            ->get()
            ->map(fn (PersonRoleAssignment $a) => $a->person)
            ->filter()
            ->unique('id')
            ->values();
    }

    /** First active holder of a given function slug in this group, or null. */
    public function functionHolder(string $functionSlug): ?Person
    {
        $today = now()->toDateString();

        return $this->assignments()
            ->whereHas('function', fn ($q) => $q->where('slug', $functionSlug))
            ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>=', $today))
            ->with('person')
            ->first()?->person;
    }

    public function activeAssignments(): HasMany
    {
        return $this->assignments()
            ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>=', now()->toDateString()));
    }
}
