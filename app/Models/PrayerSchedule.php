<?php

namespace App\Models;

use App\Enums\LocationMode;
use Database\Factories\PrayerScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class PrayerSchedule extends Model
{
    /** @use HasFactory<PrayerScheduleFactory> */
    use HasFactory;

    protected $fillable = [
        'church_id',
        'prayer_campaign_id',
        'date',
        'start_time',
        'end_time',
        'slot_minutes',
        'capacity_per_slot',
        'mode',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'mode' => LocationMode::class,
            'slot_minutes' => 'integer',
            'capacity_per_slot' => 'integer',
        ];
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PrayerCampaign::class, 'prayer_campaign_id');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(PrayerSlot::class)->orderBy('starts_at');
    }

    public function regenerateSlots(): void
    {
        $existing = $this->slots()->get()->keyBy(fn (PrayerSlot $s) => $s->starts_at->format('Y-m-d H:i'));

        $start = Carbon::parse($this->date->format('Y-m-d').' '.$this->start_time);
        $end = Carbon::parse($this->date->format('Y-m-d').' '.$this->end_time);
        $minutes = max(15, (int) $this->slot_minutes);

        $kept = collect();
        for ($cursor = $start->copy(); $cursor->lt($end); $cursor->addMinutes($minutes)) {
            $slotEnd = $cursor->copy()->addMinutes($minutes);
            if ($slotEnd->gt($end)) {
                break;
            }

            $key = $cursor->format('Y-m-d H:i');

            if ($existing->has($key)) {
                $slot = $existing->get($key);
                $slot->update([
                    'ends_at' => $slotEnd,
                    'capacity' => $this->capacity_per_slot,
                    'mode' => $this->mode->value,
                    'prayer_campaign_id' => $this->prayer_campaign_id,
                ]);
                $kept->push($slot->id);
            } else {
                $slot = $this->slots()->create([
                    'church_id' => $this->church_id,
                    'prayer_campaign_id' => $this->prayer_campaign_id,
                    'starts_at' => $cursor->copy(),
                    'ends_at' => $slotEnd,
                    'capacity' => $this->capacity_per_slot,
                    'mode' => $this->mode->value,
                ]);
                $kept->push($slot->id);
            }
        }

        $this->slots()->whereNotIn('id', $kept)
            ->whereDoesntHave('signups')
            ->delete();
    }
}
