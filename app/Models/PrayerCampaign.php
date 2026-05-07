<?php

namespace App\Models;

use Database\Factories\PrayerCampaignFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class PrayerCampaign extends Model
{
    /** @use HasFactory<PrayerCampaignFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'objectives',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(PrayerSchedule::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(PrayerSlot::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent(Builder $query, ?Carbon $on = null): Builder
    {
        $on = $on?->copy()->startOfDay() ?: now()->startOfDay();

        return $query->active()
            ->whereDate('start_date', '<=', $on)
            ->whereDate('end_date', '>=', $on);
    }

    public function includesDate(string|Carbon $date): bool
    {
        $d = $date instanceof Carbon ? $date->copy()->startOfDay() : Carbon::parse($date)->startOfDay();

        return $this->start_date->lte($d) && $this->end_date->gte($d);
    }
}
