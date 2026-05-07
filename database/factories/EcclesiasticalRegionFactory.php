<?php

namespace Database\Factories;

use App\Enums\RegionKind;
use App\Models\EcclesiasticalRegion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EcclesiasticalRegion>
 */
class EcclesiasticalRegionFactory extends Factory
{
    public function definition(): array
    {
        $n = fake()->unique()->numberBetween(1, 9999);

        return [
            'code' => 'RE'.$n,
            'name' => $n.'ª Região Eclesiástica',
            'kind' => RegionKind::Regular->value,
            'display_order' => $n,
        ];
    }

    public function missionary(): static
    {
        return $this->state(fn () => ['kind' => RegionKind::Missionary->value]);
    }
}
