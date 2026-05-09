<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\PersonRoleAssignment;
use Livewire\Form;

class PersonRoleAssignmentForm extends Form
{
    public ?PersonRoleAssignment $assignment = null;

    public ?int $person_id = null;

    public ?int $function_id = null;

    public ?int $assignment_role_id = null;

    // Scope FKs — exactly one of (group_id, church_id) is enforced by the
    // observer; region_id / district_id are denormalized from there.
    public ?int $group_id = null;

    public ?int $church_id = null;

    public ?int $ecclesiastical_region_id = null;

    public ?int $district_id = null;

    public string $started_at = '';

    public string $ended_at = '';

    public bool $is_primary = false;

    public function rules(): array
    {
        return [
            'person_id' => ['required', 'integer', 'exists:persons,id'],
            'function_id' => ['required', 'integer', 'exists:functions,id'],
            'assignment_role_id' => ['nullable', 'integer', 'exists:assignment_roles,id'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'church_id' => ['nullable', 'integer', 'exists:churches,id'],
            'ecclesiastical_region_id' => ['nullable', 'integer', 'exists:ecclesiastical_regions,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'is_primary' => ['boolean'],
        ];
    }

    public function setAssignment(PersonRoleAssignment $assignment): void
    {
        $this->assignment = $assignment;
        $this->person_id = $assignment->person_id;
        $this->function_id = $assignment->function_id;
        $this->assignment_role_id = $assignment->assignment_role_id;
        $this->group_id = $assignment->group_id;
        $this->church_id = $assignment->church_id;
        $this->ecclesiastical_region_id = $assignment->ecclesiastical_region_id;
        $this->district_id = $assignment->district_id;
        $this->started_at = $assignment->started_at?->format('Y-m-d') ?? '';
        $this->ended_at = $assignment->ended_at?->format('Y-m-d') ?? '';
        $this->is_primary = $assignment->is_primary;
    }

    public function save(): PersonRoleAssignment
    {
        $data = $this->validate();
        foreach (['started_at', 'ended_at'] as $k) {
            if (($data[$k] ?? null) === '') {
                $data[$k] = null;
            }
        }

        if ($this->assignment) {
            $this->assignment->update($data);
        } else {
            $this->assignment = PersonRoleAssignment::create($data);
        }

        return $this->assignment;
    }
}
