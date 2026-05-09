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

        // max_holders cap: if the function caps how many active holders a
        // single scope can have (e.g. Main Pastor = 1 per church), refuse
        // a save that would push past the cap. "Active" = ended_at IS NULL.
        // The scope-match uses every FK on the assignment, so the rule
        // works the same for pastors (church-scoped), admin levels
        // (region/district/church-scoped, or all-NULL for national), and
        // group-scoped functions in Phase 6.
        if ($function->max_holders === null) {
            return;
        }

        if ($assignment->ended_at !== null) {
            return; // a closed assignment never counts toward the cap
        }

        $existing = PersonRoleAssignment::query()
            ->where('function_id', $function->id)
            ->whereNull('ended_at')
            ->where('group_id', $assignment->group_id)
            ->where('church_id', $assignment->church_id)
            ->where('district_id', $assignment->district_id)
            ->where('ecclesiastical_region_id', $assignment->ecclesiastical_region_id)
            ->when($assignment->exists, fn ($q) => $q->whereKeyNot($assignment->id))
            ->count();

        if ($existing >= $function->max_holders) {
            throw ValidationException::withMessages([
                'function_id' => __(':function is limited to :max active holder(s) per scope; one is already assigned.', [
                    'function' => $function->name,
                    'max' => $function->max_holders,
                ]),
            ]);
        }
    }
}
