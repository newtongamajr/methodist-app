<?php

namespace App\Observers;

use App\Enums\FunctionAppliesTo;
use App\Models\Church;
use App\Models\FunctionRole;
use App\Models\Group;
use App\Models\PersonRoleAssignment;
use Illuminate\Validation\ValidationException;

class PersonRoleAssignmentObserver
{
    public function saving(PersonRoleAssignment $assignment): void
    {
        if ($assignment->group_id !== null && $assignment->church_id !== null) {
            throw ValidationException::withMessages([
                'group_id' => __('An assignment cannot point to both a group and a church.'),
            ]);
        }

        if ($assignment->group_id !== null) {
            $group = Group::query()->find($assignment->group_id);
            if ($group) {
                $assignment->ecclesiastical_region_id = $group->ecclesiastical_region_id;
                $assignment->district_id = $group->district_id;
                $assignment->church_id = $group->church_id;
            }
        } elseif ($assignment->church_id !== null) {
            $church = Church::query()->find($assignment->church_id);
            if ($church) {
                $assignment->ecclesiastical_region_id = $church->ecclesiastical_region_id;
                $assignment->district_id = $church->district_id;
            }
        }

        $function = FunctionRole::query()->find($assignment->function_id);
        if (! $function) {
            return;
        }

        $contextOk = match (true) {
            $function->appliesTo(FunctionAppliesTo::Admin->value) => $assignment->group_id === null,
            $function->appliesTo(FunctionAppliesTo::Pastor->value) => $assignment->church_id !== null && $assignment->group_id === null,
            default => $assignment->group_id !== null,
        };

        if (! $contextOk) {
            throw ValidationException::withMessages([
                'function_id' => __('This function cannot be assigned in this context.'),
            ]);
        }
    }
}
