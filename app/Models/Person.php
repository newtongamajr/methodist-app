<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\PersonNature;
use App\Enums\PersonRelationshipType;
use App\Enums\PersonType;
use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Person extends Model implements HasMedia
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

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
            'gender' => Gender::class,
            'marital_status' => MaritalStatus::class,
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

    // ─────────────────────────────────────────────────────────────────────────
    // Family-tree query helpers.
    //
    // Relationships are stored as a single row with the inverse type cached
    // on the row itself (auto-set by PersonRelationshipObserver). So a given
    // pair of people only ever has ONE row, regardless of which side created
    // it. To find every Person related to me by some role, look at both:
    //   - rows where I'm the subject and the relationship_type matches
    //   - rows where I'm the related side and the inverse_type matches
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Persons related to me through a specific relationship type, regardless
     * of which side of the row I'm on.
     */
    protected function relatedPersonsByType(PersonRelationshipType $forward): Collection
    {
        $inverse = $forward->inverse();

        $forwardIds = PersonRelationship::query()
            ->where('person_id', $this->id)
            ->where('relationship_type', $forward->value)
            ->pluck('related_person_id');

        $reverseIds = PersonRelationship::query()
            ->where('related_person_id', $this->id)
            ->where('relationship_type', $inverse->value)
            ->pluck('person_id');

        $ids = $forwardIds->merge($reverseIds)->unique()->reject(fn ($id) => $id === $this->id)->values();

        if ($ids->isEmpty()) {
            return new Collection;
        }

        return self::query()->whereIn('id', $ids)->orderBy('name')->get();
    }

    public function parents(): Collection
    {
        // I am the child → the other person is my parent.
        return $this->relatedPersonsByType(PersonRelationshipType::ChildOf);
    }

    public function children(): Collection
    {
        return $this->relatedPersonsByType(PersonRelationshipType::ParentOf);
    }

    public function spouses(): Collection
    {
        return $this->relatedPersonsByType(PersonRelationshipType::Spouse);
    }

    /** Active spouse only — at most one per the observer guard. */
    public function spouse(): ?self
    {
        $forwardIds = PersonRelationship::query()
            ->where('person_id', $this->id)
            ->where('relationship_type', PersonRelationshipType::Spouse->value)
            ->whereNull('ended_at')
            ->pluck('related_person_id');

        $reverseIds = PersonRelationship::query()
            ->where('related_person_id', $this->id)
            ->where('relationship_type', PersonRelationshipType::Spouse->value)
            ->whereNull('ended_at')
            ->pluck('person_id');

        $id = $forwardIds->merge($reverseIds)->unique()->reject(fn ($x) => $x === $this->id)->first();

        return $id ? self::query()->find($id) : null;
    }

    public function godparents(): Collection
    {
        return $this->relatedPersonsByType(PersonRelationshipType::GodchildOf);
    }

    public function godchildren(): Collection
    {
        return $this->relatedPersonsByType(PersonRelationshipType::GodparentOf);
    }

    public function guardians(): Collection
    {
        return $this->relatedPersonsByType(PersonRelationshipType::WardOf);
    }

    public function wards(): Collection
    {
        return $this->relatedPersonsByType(PersonRelationshipType::GuardianOf);
    }

    /** Children of any of my parents, excluding me. */
    public function siblings(): Collection
    {
        return $this->parents()
            ->flatMap(fn (self $p) => $p->children())
            ->unique('id')
            ->reject(fn (self $p) => $p->id === $this->id)
            ->values();
    }

    public function grandparents(): Collection
    {
        return $this->parents()
            ->flatMap(fn (self $p) => $p->parents())
            ->unique('id')
            ->values();
    }

    public function grandchildren(): Collection
    {
        return $this->children()
            ->flatMap(fn (self $c) => $c->children())
            ->unique('id')
            ->values();
    }

    /** Aunts AND uncles — siblings of my parents, excluding me. */
    public function auntsAndUncles(): Collection
    {
        return $this->parents()
            ->flatMap(fn (self $p) => $p->siblings())
            ->unique('id')
            ->reject(fn (self $p) => $p->id === $this->id)
            ->values();
    }

    /** Nieces AND nephews — children of my siblings. */
    public function niecesAndNephews(): Collection
    {
        return $this->siblings()
            ->flatMap(fn (self $s) => $s->children())
            ->unique('id')
            ->values();
    }

    /**
     * Parents-in-law: my active spouse's parents. Active spouse only — an ex's
     * parents are not currently in-laws.
     */
    public function parentsInLaw(): Collection
    {
        $spouse = $this->spouse();
        if (! $spouse) {
            return new Collection;
        }

        return $spouse->parents()
            ->reject(fn (self $p) => $p->id === $this->id)
            ->values();
    }

    /**
     * Children-in-law: spouses of my children. Each child's active spouse is
     * counted at most once; my own spouse is excluded (in case of a graph loop).
     */
    public function childrenInLaw(): Collection
    {
        $myId = $this->id;

        return $this->children()
            ->map(fn (self $c) => $c->spouse())
            ->filter()
            ->unique('id')
            ->reject(fn (self $sp) => $sp->id === $myId)
            ->values();
    }

    /**
     * Brothers- and sisters-in-law: spouse's siblings UNION my siblings'
     * spouses. Active spouse only — past unions don't generate ongoing in-laws.
     */
    public function siblingsInLaw(): Collection
    {
        $spouse = $this->spouse();
        $fromSpouse = $spouse ? $spouse->siblings() : new Collection;
        $fromMySiblings = $this->siblings()
            ->map(fn (self $s) => $s->spouse())
            ->filter()
            ->values();

        return $fromSpouse->concat($fromMySiblings)
            ->unique('id')
            ->reject(fn (self $p) => $p->id === $this->id)
            ->values();
    }

    /**
     * Step-children: my active spouse's children that aren't already mine.
     * This is what makes the user's child appear on the spouse's family tab
     * without storing a duplicate relationship row.
     */
    public function stepchildren(): Collection
    {
        $spouse = $this->spouse();
        if (! $spouse) {
            return new Collection;
        }
        $myChildIds = $this->children()->pluck('id');

        return $spouse->children()
            ->reject(fn (self $c) => $myChildIds->contains($c->id))
            ->values();
    }

    /**
     * Step-parents: spouses of my parents that aren't themselves my parents.
     * Inverse of stepchildren() — surfaces on the child's family tab.
     */
    public function stepparents(): Collection
    {
        $myParentIds = $this->parents()->pluck('id');

        return $this->parents()
            ->map(fn (self $p) => $p->spouse())
            ->filter()
            ->unique('id')
            ->reject(fn (self $sp) => $myParentIds->contains($sp->id) || $sp->id === $this->id)
            ->values();
    }

    public function cousins(): Collection
    {
        return $this->auntsAndUncles()
            ->flatMap(fn (self $au) => $au->children())
            ->unique('id')
            ->values();
    }

    // ─── Group helpers (Phase 6) ────────────────────────────────────────────

    /** All groups this person is currently active in. */
    public function activeGroups(): Collection
    {
        $today = now()->toDateString();

        return $this->roleAssignments()
            ->whereNotNull('group_id')
            ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>=', $today))
            ->with('group:id,name,kind')
            ->get()
            ->map(fn (PersonRoleAssignment $a) => $a->group)
            ->filter()
            ->unique('id')
            ->values();
    }

    // ─── Age-based nature helpers (Phase 7) ─────────────────────────────────
    //
    // Thresholds (Brazilian Methodist convention):
    //   Child:    0–11
    //   Teenager: 12–17
    //   Adult:    18+   (Youth is opt-in, not derived from age)
    //
    // The inference is advisory — admins can still pick natures explicitly.
    // It powers (a) auto-suggest in the People editor, and (b) the act-as
    // gate (only minors can be acted-as).

    public const CHILD_MAX_AGE = 11;

    public const TEENAGER_MAX_AGE = 17;

    /**
     * Suggested nature based on birthdate. Returns null if birthdate is
     * absent or person_type is not individual.
     */
    public function inferAgeBasedNature(): ?PersonNature
    {
        if (! $this->birthdate || $this->person_type?->value !== PersonType::Individual->value) {
            return null;
        }

        $age = $this->birthdate->age;

        return match (true) {
            $age <= self::CHILD_MAX_AGE => PersonNature::Child,
            $age <= self::TEENAGER_MAX_AGE => PersonNature::Teenager,
            default => null, // adult — no auto-suggest, admin picks Member/Youth/etc.
        };
    }

    /** Whether the person is a minor (child or teenager) by birthdate. */
    public function isMinor(): bool
    {
        if (! $this->birthdate) {
            // Without a birthdate we trust the natures the admin picked.
            return $this->hasNature(PersonNature::Child) || $this->hasNature(PersonNature::Teenager);
        }

        return $this->birthdate->age <= self::TEENAGER_MAX_AGE;
    }

    /** Groups where this person currently holds a "lead" or "co_lead" function. */
    public function groupsAsLeader(): Collection
    {
        $today = now()->toDateString();

        return $this->roleAssignments()
            ->whereNotNull('group_id')
            ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>=', $today))
            ->whereHas('function', fn ($q) => $q->whereIn('slug', ['lead', 'co_lead']))
            ->with('group:id,name,kind')
            ->get()
            ->map(fn (PersonRoleAssignment $a) => $a->group)
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * Depth-bounded family tree centered on this Person, suitable for the
     * Family tab UI. Default depth = 2 covers grandparents → grandchildren.
     *
     * @return array{
     *     person: self,
     *     parents: array<int, array>,
     *     children: array<int, array>,
     *     spouse: ?self,
     * }
     */
    public function familyTree(int $depth = 2, ?array $visited = null): array
    {
        $visited = $visited ?? [];
        $visited[$this->id] = true;

        $build = function (self $person) use ($depth, $visited) {
            if ($depth <= 0 || isset($visited[$person->id])) {
                return [
                    'person' => $person,
                    'parents' => [],
                    'children' => [],
                    'spouse' => null,
                ];
            }

            return $person->familyTree($depth - 1, $visited);
        };

        return [
            'person' => $this,
            'parents' => $this->parents()->map($build)->values()->all(),
            'children' => $this->children()->map($build)->values()->all(),
            'spouse' => $this->spouse(),
        ];
    }

    // ─── Media: photo collection (replaces the old persons.photo_path) ──────

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // quality(95) matches the User avatar conversions — Spatie's
        // default ~75 makes a 256×256 portrait look noticeably soft.
        $this->addMediaConversion('thumb')
            ->performOnCollections('photo')
            ->fit(Fit::Crop, 64, 64)
            ->quality(95)
            ->nonQueued();

        $this->addMediaConversion('sm')
            ->performOnCollections('photo')
            ->fit(Fit::Crop, 128, 128)
            ->quality(95)
            ->nonQueued();

        // Match the User avatar conversions: keep `md` / `lg` synchronous
        // so callers don't need a queue worker to display them.
        $this->addMediaConversion('md')
            ->performOnCollections('photo')
            ->fit(Fit::Crop, 256, 256)
            ->quality(95)
            ->nonQueued();

        $this->addMediaConversion('lg')
            ->performOnCollections('photo')
            ->fit(Fit::Crop, 512, 512)
            ->quality(95)
            ->nonQueued();
    }

    public function photoUrl(string $conversion = 'md'): ?string
    {
        $url = $this->getFirstMediaUrl('photo', $conversion);

        return $url !== '' ? $url : null;
    }
}
