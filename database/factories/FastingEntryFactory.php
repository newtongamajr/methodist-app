<?php

namespace Database\Factories;

use App\Enums\FastingRestriction;
use App\Enums\FastingType;
use App\Models\FastingEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FastingEntry>
 */
class FastingEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'fasting_campaign_id' => null,
            'date' => now()->toDateString(),
            'type' => fake()->randomElement(FastingType::cases())->value,
            'restrictions' => [fake()->randomElement(FastingRestriction::cases())->value],
            'notes' => null,
        ];
    }
}
