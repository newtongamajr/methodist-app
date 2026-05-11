<?php

namespace App\Models;

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

    public function cousins(): Collection
    {
        return $this->auntsAndUncles()
            ->flatMap(fn (self $au) => $au->children())
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
}
