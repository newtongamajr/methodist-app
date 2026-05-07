<?php

namespace Database\Factories;

use App\Enums\LocationMode;
use App\Models\Church;
use App\Models\EcclesiasticalRegion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Church>
 */
class ChurchFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Igreja Metodista '.fake()->city();

        return [
            'ecclesiastical_region_id' => EcclesiasticalRegion::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => strtoupper(fake()->randomElement(['SP', 'RJ', 'MG', 'RS', 'PR', 'BA', 'PE', 'CE', 'GO', 'AM'])),
            'zip' => fake()->numerify('#####-###'),
            'latitude' => fake()->latitude(-33, 5),
            'longitude' => fake()->longitude(-73, -34),
            'timezone' => 'America/Sao_Paulo',
            'max_prayers_per_slot' => fake()->numberBetween(3, 10),
            'default_mode' => fake()->randomElement([LocationMode::Presential->value, LocationMode::Home->value]),
            'phone' => fake()->numerify('(##) ####-####'),
            'email' => fake()->unique()->companyEmail(),
            'is_active' => true,
        ];
    }
}
