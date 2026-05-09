<?php

namespace Database\Factories;

use App\Enums\PersonNature;
use App\Enums\PersonType;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'person_type' => PersonType::Individual->value,
            'name' => fake()->name(),
            'preferred_name' => null,
            'tax_id' => null,
            'tax_id_type' => null,
            'birthdate' => fake()->dateTimeBetween('-70 years', '-12 years')->format('Y-m-d'),
            'gender' => null,
            'marital_status' => null,
            'photo_path' => null,
            'natures' => [PersonNature::Member->value],
            'additional_data' => [],
            'managing_church_id' => null,
            'notes' => null,
        ];
    }

    public function nature(PersonNature $nature): static
    {
        return $this->state(fn () => ['natures' => [$nature->value]]);
    }

    public function organization(): static
    {
        return $this->state(fn () => [
            'person_type' => PersonType::Organization->value,
            'natures' => [],
            'birthdate' => null,
        ]);
    }
}
