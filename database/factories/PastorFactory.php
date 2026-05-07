<?php

namespace Database\Factories;

use App\Models\Pastor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pastor>
 */
class PastorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => 'Pr. '.fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('(##) #####-####'),
        ];
    }
}
