<?php

namespace Database\Factories;

use App\Enums\AppLocale;
use App\Enums\PersonNature;
use App\Models\Church;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $name = fake()->name();

        return [
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'locale' => fake()->randomElement(AppLocale::values()),
            'person_id' => fn (array $attrs) => Person::factory()->create([
                'name' => $attrs['name'] ?? $name,
            ])->id,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withPerson(Person $person): static
    {
        return $this->state(fn () => [
            'person_id' => $person->id,
            'name' => $person->name,
        ]);
    }

    public function nature(PersonNature $nature): static
    {
        return $this->state(fn (array $attrs) => [
            'person_id' => Person::factory()->create([
                'name' => $attrs['name'] ?? fake()->name(),
                'natures' => [$nature->value],
            ])->id,
        ]);
    }

    public function forChurch(Church $church): static
    {
        return $this->afterCreating(function (User $user) use ($church) {
            $user->person->update(['managing_church_id' => $church->id]);
            $user->churches()->syncWithoutDetaching([$church->id => ['is_primary' => true]]);
        });
    }
}
