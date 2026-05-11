<?php

namespace Database\Factories;

use App\Enums\PersonNature;
use App\Enums\PersonType;
use App\Enums\RegionKind;
use App\Models\EcclesiasticalRegion;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EcclesiasticalRegion>
 */
class EcclesiasticalRegionFactory extends Factory
{
    public function definition(): array
    {
        $n = fake()->unique()->numberBetween(1, 9999);
        $name = $n.'ª Região Eclesiástica';

        return [
            'code' => 'RE'.$n,
            'name' => $name,
            'kind' => RegionKind::Regular->value,
            'display_order' => $n,
            'person_id' => fn () => Person::create([
                'person_type' => PersonType::Organization->value,
                'name' => $name,
                'natures' => [PersonNature::EcclesiasticalRegion->value],
            ])->id,
        ];
    }

    public function missionary(): static
    {
        return $this->state(fn () => ['kind' => RegionKind::Missionary->value]);
    }

    public function nationalHeadquarters(): static
    {
        return $this->state(fn (array $attrs) => [
            'kind' => RegionKind::NationalHeadquarters->value,
            'person_id' => Person::create([
                'person_type' => PersonType::Organization->value,
                'name' => $attrs['name'] ?? 'Sede Nacional',
                'natures' => [PersonNature::NationalHeadquarters->value],
            ])->id,
        ]);
    }
}
