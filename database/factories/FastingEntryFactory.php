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
            // Resolve person_id lazily from the user_id once the row is being
            // built, so test overrides like ['user_id' => $someUser->id] still
            // pick up the correct Person without forcing every caller to set
            // person_id explicitly.
            'person_id' => fn (array $attrs) => User::find($attrs['user_id'])?->person_id,
            'fasting_campaign_id' => null,
            'date' => now()->toDateString(),
            'type' => fake()->randomElement(FastingType::cases())->value,
            'restrictions' => [fake()->randomElement(FastingRestriction::cases())->value],
            'notes' => null,
        ];
    }
}
