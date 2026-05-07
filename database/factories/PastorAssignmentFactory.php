<?php

namespace Database\Factories;

use App\Enums\PastorRole;
use App\Models\Church;
use App\Models\Pastor;
use App\Models\PastorAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PastorAssignment>
 */
class PastorAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pastor_id' => Pastor::factory(),
            'church_id' => Church::factory(),
            'role' => PastorRole::Auxiliary->value,
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'display_order' => 0,
        ];
    }

    public function main(): static
    {
        return $this->state(fn () => ['role' => PastorRole::Main->value]);
    }

    public function seminarist(): static
    {
        return $this->state(fn () => ['role' => PastorRole::Seminarist->value]);
    }

    public function ended(?string $on = null): static
    {
        return $this->state(fn () => ['end_date' => $on ?? now()->subDay()->toDateString()]);
    }
}
