<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\AssignmentRole;
use Illuminate\Validation\Rule;
use Livewire\Form;

class AssignmentRoleForm extends Form
{
    public ?AssignmentRole $assignmentRole = null;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public bool $is_active = true;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255',
                Rule::unique('assignment_roles', 'slug')->ignore($this->assignmentRole?->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
        ];
    }

    public function setAssignmentRole(AssignmentRole $assignmentRole): void
    {
        $this->assignmentRole = $assignmentRole;
        $this->name = $assignmentRole->name;
        $this->slug = $assignmentRole->slug;
        $this->description = $assignmentRole->description ?? '';
        $this->is_active = $assignmentRole->is_active;
    }

    public function save(): AssignmentRole
    {
        $data = $this->validate();

        foreach (['description', 'slug'] as $k) {
            if (($data[$k] ?? null) === '') {
                $data[$k] = null;
            }
        }

        if ($this->assignmentRole) {
            $this->assignmentRole->update($data);
        } else {
            $this->assignmentRole = AssignmentRole::create($data);
        }

        return $this->assignmentRole;
    }
}
