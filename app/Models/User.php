<?php

namespace App\Models;

use App\Models\Pivots\ChurchUser;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, InteractsWithMedia, Notifiable;

    protected $fillable = [
        'person_id',
        'name',
        'email',
        'password',
        'locale',
        'appearance',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function churches(): BelongsToMany
    {
        // Only the rows that actually point at a specific church count as
        // "membership". Region-only and district-only rows live in the
        // same table but represent admin scope, not membership — they're
        // accessed via the dedicated relations below.
        return $this->belongsToMany(Church::class)
            ->using(ChurchUser::class)
            ->withPivot(['is_primary', 'region_id', 'district_id'])
            ->wherePivotNotNull('church_id')
            ->withTimestamps();
    }

    /**
     * Raw church_user pivot rows owned by this user, regardless of shape.
     * Drives manageableRegions / manageableDistricts / manageableChurches
     * for non-national admins.
     */
    public function adminScopes(): HasMany
    {
        return $this->hasMany(ChurchUser::class);
    }

    /**
     * Region IDs this user can administer.
     * National admin → every region.
     * Regional admin → the regions referenced by their region-only rows
     * AND any region implied by district-only / local rows.
     */
    public function manageableRegionIds(): array
    {
        if ($this->hasRole('national_admin')) {
            return EcclesiasticalRegion::query()->pluck('id')->all();
        }

        if (! $this->hasAnyRole(['regional_admin', 'district_admin', 'local_admin'])) {
            return [];
        }

        return ChurchUser::query()
            ->where('user_id', $this->id)
            ->whereNotNull('region_id')
            ->pluck('region_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * District IDs this user can administer. Regional admins see every
     * district inside their region(s); district/local admins see only the
     * districts referenced on their rows.
     */
    public function manageableDistrictIds(): array
    {
        if ($this->hasRole('national_admin')) {
            return District::query()->pluck('id')->all();
        }

        if (! $this->hasAnyRole(['regional_admin', 'district_admin', 'local_admin'])) {
            return [];
        }

        $rows = ChurchUser::query()->where('user_id', $this->id)->get();

        $direct = $rows->pluck('district_id')->filter()->unique();

        $regionOnly = $rows->whereStrict('district_id', null)->pluck('region_id')->filter()->unique();
        $impliedByRegion = $regionOnly->isNotEmpty()
            ? District::query()->whereIn('ecclesiastical_region_id', $regionOnly)->pluck('id')
            : collect();

        return $direct->merge($impliedByRegion)->unique()->values()->all();
    }

    /**
     * Churches this user is allowed to administer.
     * - national_admin → every church.
     * - otherwise: derived from church_user rows according to the row shape.
     *   Region-only rows expand to every church in that region; district-
     *   only rows expand to every church in that district; local rows
     *   contribute their explicit church_id.
     */
    public function manageableChurches(): Collection
    {
        return once(function () {
            if ($this->hasRole('national_admin')) {
                return Church::query()->orderBy('name')->get();
            }

            if (! $this->hasAnyRole(['regional_admin', 'district_admin', 'local_admin'])) {
                return collect();
            }

            $rows = ChurchUser::query()->where('user_id', $this->id)->get();

            $regionOnly = $rows->whereStrict('district_id', null)->whereStrict('church_id', null)
                ->pluck('region_id')->filter()->unique();
            $districtOnly = $rows->whereStrict('church_id', null)->whereNotNull('district_id')
                ->pluck('district_id')->filter()->unique();
            $direct = $rows->whereNotNull('church_id')->pluck('church_id')->filter()->unique();

            $ids = collect();
            if ($regionOnly->isNotEmpty()) {
                $ids = $ids->merge(Church::query()->whereIn('ecclesiastical_region_id', $regionOnly)->pluck('id'));
            }
            if ($districtOnly->isNotEmpty()) {
                $ids = $ids->merge(Church::query()->whereIn('district_id', $districtOnly)->pluck('id'));
            }
            $ids = $ids->merge($direct)->unique()->values();

            if ($ids->isEmpty()) {
                return collect();
            }

            return Church::query()->whereIn('id', $ids)->orderBy('name')->get();
        });
    }

    /** @return array<int> */
    public function manageableChurchIds(): array
    {
        return $this->manageableChurches()->pluck('id')->all();
    }

    public function canManageChurch(int $churchId): bool
    {
        if ($this->hasRole('national_admin')) {
            return true;
        }

        return in_array($churchId, $this->manageableChurchIds(), true);
    }

    public function canManageDistrict(int $districtId): bool
    {
        if ($this->hasRole('national_admin')) {
            return true;
        }

        return in_array($districtId, $this->manageableDistrictIds(), true);
    }

    public function canManageRegion(int $regionId): bool
    {
        if ($this->hasRole('national_admin')) {
            return true;
        }

        return in_array($regionId, $this->manageableRegionIds(), true);
    }

    /**
     * Churches this user is attached to via the church_user pivot, regardless
     * of role. Used to drive the church-context badge for regular members.
     */
    public function attachedChurches(): Collection
    {
        return $this->churches()->orderBy('churches.name')->get();
    }

    /**
     * The set of churches the user can pick from in the church context
     * switcher. National admins see every church; everyone else sees the
     * churches they're attached to via the pivot.
     */
    public function contextChurches(): Collection
    {
        if ($this->hasRole('national_admin')) {
            return Church::query()->orderBy('name')->get();
        }

        return $this->attachedChurches();
    }

    public function isAdminUser(): bool
    {
        return $this->hasAnyRole(['national_admin', 'regional_admin', 'district_admin', 'local_admin']);
    }

    // ─── Parental act-as (Phase 7) ──────────────────────────────────────────
    //
    // A logged-in User can "act as" a related Person when:
    //   1. They have a Person of their own (almost always the case)
    //   2. Their Person has a parent_of (or guardian_of) relationship with the target
    //   3. The target Person is a minor (child or teenager nature, OR age <=17)
    //
    // The current acting-as target is stored on the session as
    // `acting_as_person_id` and consumed by request()-scoped helpers.

    public function canActAs(Person $target): bool
    {
        $self = $this->person;
        if (! $self || $self->id === $target->id) {
            return false;
        }

        if (! $target->isMinor()) {
            return false;
        }

        // Check for a parental link in either direction (parent_of OR guardian_of).
        $children = $self->children()->pluck('id')->merge($self->wards()->pluck('id'));

        return $children->contains($target->id);
    }

    /** The Person the current session is acting as, if any. */
    public function actingAsPerson(): ?Person
    {
        $id = session('acting_as_person_id');
        if (! $id) {
            return null;
        }

        $target = Person::find($id);
        if (! $target || ! $this->canActAs($target)) {
            session()->forget('acting_as_person_id');

            return null;
        }

        return $target;
    }

    /**
     * Effective Person for the current request — the acted-as Person if any,
     * else the user's own Person. Used by controllers that record an action
     * on someone's behalf (prayer signups, fasting entries — wired in a
     * follow-up PR).
     */
    public function effectivePerson(): ?Person
    {
        return $this->actingAsPerson() ?? $this->person;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->performOnCollections('avatar')
            ->fit(Fit::Crop, 64, 64)
            ->nonQueued();

        $this->addMediaConversion('sm')
            ->performOnCollections('avatar')
            ->fit(Fit::Crop, 128, 128)
            ->nonQueued();

        // `md` and `lg` run synchronously too: the avatar URL on the
        // menubar uses `md` and breaks if it's queued without a worker
        // running. Each resize is a few hundred ms; cheap enough to do
        // inline at upload time.
        $this->addMediaConversion('md')
            ->performOnCollections('avatar')
            ->fit(Fit::Crop, 256, 256)
            ->nonQueued();

        $this->addMediaConversion('lg')
            ->performOnCollections('avatar')
            ->fit(Fit::Crop, 512, 512)
            ->nonQueued();
    }

    public function avatarUrl(string $conversion = 'md'): ?string
    {
        $url = $this->getFirstMediaUrl('avatar', $conversion);

        return $url !== '' ? $url : null;
    }

    /**
     * Active admin context (which church the user is currently acting on).
     * Falls back to the Person's managing_church_id, then to the first
     * manageable, then to the user's primary attached church.
     */
    public function currentChurchId(): ?int
    {
        $sessionId = session('admin_church_id');
        $allowed = $this->manageableChurchIds();

        if ($sessionId && (in_array($sessionId, $allowed, true) || $this->hasRole('national_admin'))) {
            return (int) $sessionId;
        }

        $managingId = $this->person?->managing_church_id;
        if ($managingId && in_array($managingId, $allowed, true)) {
            return $managingId;
        }

        if ($allowed) {
            return $allowed[0];
        }

        if ($managingId) {
            return $managingId;
        }

        $primaryAttached = $this->churches()->wherePivot('is_primary', true)->value('churches.id');

        return $primaryAttached ? (int) $primaryAttached : null;
    }
}
