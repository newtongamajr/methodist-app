<?php

namespace Database\Factories;

use App\Enums\LocationMode;
use App\Models\Church;
use App\Models\PrayerSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrayerSchedule>
 */
class PrayerScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            // Schedules must belong to a campaign whose window covers the date.
            'prayer_campaign_id' => PrayerCampaignFactory::new()->range(
                now()->subDay()->toDateString(),
                now()->addDays(30)->toDateString(),
            ),
            'date' => now()->addDays(fake()->numberBetween(1, 7))->toDateString(),
            'start_time' => '06:00:00',
            'end_time' => '20:00:00',
            'slot_minutes' => 60,
            'capacity_per_slot' => 5,
            'mode' => LocationMode::Presential->value,
            'notes' => null,
        ];
    }
}
