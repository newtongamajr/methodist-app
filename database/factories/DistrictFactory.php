<?php

namespace Database\Factories;

use App\Enums\PersonNature;
use App\Enums\PersonType;
use App\Models\District;
use App\Models\EcclesiasticalRegion;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<District>
 */
class DistrictFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->city().' District';

        return [
            'ecclesiastical_region_id' => EcclesiasticalRegion::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'code' => null,
            'display_order' => 0,
            'is_active' => true,
            'person_id' => fn () => Person::create([
                'person_type' => PersonType::Organization->value,
                'name' => $name,
                'natures' => [PersonNature::District->value],
            ])->id,
        ];
    }
}
