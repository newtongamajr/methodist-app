<?php

namespace Database\Factories;

use App\Enums\FunctionAppliesTo;
use App\Models\FunctionRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FunctionRole>
 */
class FunctionRoleFactory extends Factory
{
    protected $model = FunctionRole::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'applies_to' => [FunctionAppliesTo::Council->value],
            'max_holders' => null,
            'is_active' => true,
            'display_order' => 0,
        ];
    }
}
