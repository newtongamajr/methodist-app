<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\PersonRoleAssignment;
use Livewire\Form;

class PastorAssignmentForm extends Form
{
    public ?PersonRoleAssignment $assignment = null;

    public string $pastorMode = 'existing';

    public ?int $person_id = null;

    public string $person_name = '';

    public string $person_email = '';

    public string $person_phone = '';

    public ?int $function_id = null;

    public ?string $start_date = null;

    public ?string $end_date = null;

    public function rules(): array
    {
        $rules = [
            'pastorMode' => ['required', 'in:existing,new'],
            'function_id' => ['required', 'integer', 'exists:functions,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];

        if ($this->pastorMode === 'existing') {
            $rules['person_id'] = ['required', 'integer', 'exists:persons,id'];
        } else {
            $rules += [
                'person_name' => ['required', 'string', 'max:255'],
                'person_email' => ['nullable', 'email', 'max:255'],
                'person_phone' => ['nullable', 'string', 'max:32'],
            ];
        }

        return $rules;
    }

    public function setAssignment(PersonRoleAssignment $assignment): void
    {
        $this->assignment = $assignment;
        $this->pastorMode = 'existing';
        $this->person_id = $assignment->person_id;
        $this->function_id = $assignment->function_id;
        $this->start_date = $assignment->started_at?->format('Y-m-d');
        $this->end_date = $assignment->ended_at?->format('Y-m-d');
    }
}
