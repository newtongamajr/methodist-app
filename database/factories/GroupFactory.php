<?php

namespace Database\Factories;

use App\Enums\GroupKind;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'kind' => GroupKind::Council->value,
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => null,
            'ecclesiastical_region_id' => null,
            'district_id' => null,
            'church_id' => null,
            'started_at' => null,
            'ended_at' => null,
            'is_active' => true,
        ];
    }

    public function kind(GroupKind $kind): static
    {
        return $this->state(fn () => ['kind' => $kind->value]);
    }
}
