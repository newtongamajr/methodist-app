<?php

namespace Database\Factories;

use App\Models\FunctionRole;
use App\Models\Person;
use App\Models\PersonRoleAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonRoleAssignment>
 */
class PersonRoleAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'function_id' => FunctionRole::query()->value('id') ?? FunctionRole::factory(),
            'assignment_role_id' => null,
            'group_id' => null,
            'church_id' => null,
            'ecclesiastical_region_id' => null,
            'district_id' => null,
            'started_at' => null,
            'ended_at' => null,
            'is_primary' => false,
            'context_data' => null,
        ];
    }
}
