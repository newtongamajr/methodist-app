<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        return $this->belongsToMany(Church::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Churches this user is allowed to administer.
     * - national_admin → every church.
     * - regional_admin → every church inside the user's scoped region(s).
     * - district_admin → every church inside the user's scoped district(s).
     * - local_admin → only the church(es) explicitly assigned.
     * - everyone else → empty.
     */
    public function manageableChurches(): Collection
    {
        return once(function () {
            if ($this->hasRole('national_admin')) {
                return Church::query()->orderBy('name')->get();
            }

            $assignments = $this->person?->roleAssignments()
                ->whereNull('ended_at')
                ->get() ?? collect();

            $churchIds = collect();

            if ($this->hasRole('regional_admin')) {
                $regionIds = $assignments->pluck('ecclesiastical_region_id')->filter()->unique();
                $churchIds = $churchIds->merge(
                    Church::query()
                        ->whereIn('ecclesiastical_region_id', $regionIds)
                        ->pluck('id'),
                );
            }

            if ($this->hasRole('district_admin')) {
                $districtIds = $assignments->pluck('district_id')->filter()->unique();
                $churchIds = $churchIds->merge(
                    Church::query()
                        ->whereIn('district_id', $districtIds)
                        ->pluck('id'),
                );
            }

            if ($this->hasRole('local_admin')) {
                $churchIds = $churchIds->merge($this->churches()->pluck('churches.id'));
            }

            $ids = $churchIds->unique()->values();

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

        $this->addMediaConversion('md')
            ->performOnCollections('avatar')
            ->fit(Fit::Crop, 256, 256);

        $this->addMediaConversion('lg')
            ->performOnCollections('avatar')
            ->fit(Fit::Crop, 512, 512);
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
