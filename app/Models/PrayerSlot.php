<?php

namespace App\Models;

use App\Enums\LocationMode;
use App\Enums\SignupStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrayerSlot extends Model
{
    protected $fillable = [
        'prayer_schedule_id',
        'church_id',
        'prayer_campaign_id',
        'starts_at',
        'ends_at',
        'capacity',
        'mode',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'capacity' => 'integer',
            'mode' => LocationMode::class,
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(PrayerSchedule::class, 'prayer_schedule_id');
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PrayerCampaign::class, 'prayer_campaign_id');
    }

    public function signups(): HasMany
    {
        return $this->hasMany(PrayerSignup::class);
    }

    public function confirmedSignups(): HasMany
    {
        return $this->signups()->where('status', SignupStatus::Confirmed);
    }

    public function getRemainingCapacityAttribute(): int
    {
        return max(0, $this->capacity - ($this->confirmed_signups_count ?? $this->confirmedSignups()->count()));
    }

    public function isFull(): bool
    {
        return $this->remaining_capacity <= 0;
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now());
    }
}
