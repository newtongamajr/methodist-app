<?php

namespace Database\Factories;

use App\Enums\AppLocale;
use App\Enums\MemberType;
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
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'member_type' => fake()->randomElement(MemberType::cases())->value,
            'church_id' => null,
            'locale' => fake()->randomElement(AppLocale::values()),
            'phone' => fake()->numerify('(##) #####-####'),
            'birthdate' => fake()->dateTimeBetween('-70 years', '-12 years')->format('Y-m-d'),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function memberType(MemberType $type): static
    {
        return $this->state(fn () => ['member_type' => $type->value]);
    }
}
