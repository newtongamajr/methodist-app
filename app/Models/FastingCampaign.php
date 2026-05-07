<?php

namespace App\Models;

use Database\Factories\FastingCampaignFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class FastingCampaign extends Model
{
    /** @use HasFactory<FastingCampaignFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'start_date',
        'end_date',
        'types',
        'restrictions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'types' => 'array',
            'restrictions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(FastingEntry::class);
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

    public function dateRange(): array
    {
        $days = [];
        for ($d = $this->start_date->copy(); $d->lte($this->end_date); $d->addDay()) {
            $days[] = $d->format('Y-m-d');
        }

        return $days;
    }
}
