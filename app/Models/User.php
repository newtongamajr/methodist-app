<?php

namespace App\Models;

use App\Enums\MemberType;
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
        'name',
        'email',
        'password',
        'member_type',
        'church_id',
        'locale',
        'appearance',
        'phone',
        'birthdate',
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
            'member_type' => MemberType::class,
            'birthdate' => 'date',
        ];
    }

    public function primaryChurch(): BelongsTo
    {
        return $this->belongsTo(Church::class, 'church_id');
    }

    public function churches(): BelongsToMany
    {
        return $this->belongsToMany(Church::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function pastorProfiles(): HasMany
    {
        return $this->hasMany(Pastor::class);
    }

    /**
     * Churches this user is allowed to administer.
     * - global_manager → every church.
     * - local_manager → all churches attached via the church_user pivot.
     * - everyone else → empty.
     */
    public function manageableChurches(): Collection
    {
        return once(function () {
            if ($this->hasRole('global_manager')) {
                return Church::query()->orderBy('name')->get();
            }

            if ($this->hasRole('local_manager')) {
                return $this->churches()->orderBy('churches.name')->get();
            }

            return collect();
        });
    }

    /** @return array<int> */
    public function manageableChurchIds(): array
    {
        return $this->manageableChurches()->pluck('id')->all();
    }

    public function canManageChurch(int $churchId): bool
    {
        if ($this->hasRole('global_manager')) {
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
     * switcher. Globals see every church; everyone else sees the churches
     * they're attached to via the pivot.
     */
    public function contextChurches(): Collection
    {
        if ($this->hasRole('global_manager')) {
            return Church::query()->orderBy('name')->get();
        }

        return $this->attachedChurches();
    }

    public function isAdminUser(): bool
    {
        return $this->hasRole('global_manager') || $this->hasRole('local_manager');
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
     * Falls back to primary church_id, then to the first manageable.
     */
    public function currentChurchId(): ?int
    {
        $sessionId = session('admin_church_id');
        $allowed = $this->manageableChurchIds();

        if ($sessionId && (in_array($sessionId, $allowed, true) || $this->hasRole('global_manager'))) {
            return (int) $sessionId;
        }

        if ($this->church_id && in_array($this->church_id, $allowed, true)) {
            return $this->church_id;
        }

        return $allowed[0] ?? $this->church_id;
    }
}
