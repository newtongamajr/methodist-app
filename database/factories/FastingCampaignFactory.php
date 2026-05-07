<?php

namespace Database\Factories;

use App\Enums\FastingRestriction;
use App\Enums\FastingType;
use App\Models\FastingCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FastingCampaign>
 */
class FastingCampaignFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Campaign '.fake()->words(2, true);
        $start = now()->startOfDay();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'description' => fake()->sentence(),
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays(20)->toDateString(),
            'types' => array_map(fn ($t) => $t->value, FastingType::cases()),
            'restrictions' => array_map(fn ($r) => $r->value, FastingRestriction::cases()),
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
