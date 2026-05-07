<?php

namespace Database\Factories;

use App\Models\PrayerCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PrayerCampaign>
 */
class PrayerCampaignFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Prayer Campaign '.fake()->words(2, true);
        $start = now()->startOfDay();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'description' => fake()->sentence(),
            'objectives' => fake()->paragraph(),
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays(20)->toDateString(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function range(string $start, string $end): static
    {
        return $this->state(fn () => [
            'start_date' => $start,
            'end_date' => $end,
        ]);
    }
}
