<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\PastorRole;
use App\Models\PastorAssignment;
use Illuminate\Validation\Rule;
use Livewire\Form;

class PastorAssignmentForm extends Form
{
    public ?PastorAssignment $assignment = null;

    public string $pastorMode = 'existing';

    public ?int $pastor_id = null;

    public string $pastor_name = '';

    public string $pastor_email = '';

    public string $pastor_phone = '';

    public string $role = 'auxiliary';

    public ?string $start_date = null;

    public ?string $end_date = null;

    public int $display_order = 0;

    public function rules(): array
    {
        $rules = [
            'pastorMode' => ['required', 'in:existing,new'],
            'role' => ['required', 'in:'.implode(',', PastorRole::values())],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'display_order' => ['integer', 'min:0', 'max:99'],
        ];

        if ($this->pastorMode === 'existing') {
            $rules['pastor_id'] = ['required', 'integer', 'exists:pastors,id'];
        } else {
            $rules += [
                'pastor_name' => ['required', 'string', 'max:255'],
                'pastor_email' => ['nullable', 'email', 'max:255', Rule::unique('pastors', 'email')],
                'pastor_phone' => ['nullable', 'string', 'max:32'],
            ];
        }

        return $rules;
    }

    public function setAssignment(PastorAssignment $assignment): void
    {
        $this->assignment = $assignment;
        $this->pastorMode = 'existing';
        $this->pastor_id = $assignment->pastor_id;
        $this->role = $assignment->role->value;
        $this->start_date = $assignment->start_date?->format('Y-m-d');
        $this->end_date = $assignment->end_date?->format('Y-m-d');
        $this->display_order = $assignment->display_order;
    }
}
