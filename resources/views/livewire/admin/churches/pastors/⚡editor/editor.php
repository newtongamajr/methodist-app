<?php

use App\Enums\PersonContactType;
use App\Enums\PersonNature;
use App\Enums\PersonType;
use App\Livewire\Forms\PastorAssignmentForm;
use App\Models\Church;
use App\Models\FunctionRole;
use App\Models\Person;
use App\Models\PersonRoleAssignment;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public Church $church;

    public PastorAssignmentForm $form;

    public function mount(int $churchId, ?int $assignmentId = null): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);

        $this->church = Church::findOrFail($churchId);
        $this->form->start_date = now()->toDateString();
        $this->form->function_id = $this->pastorFunctions->first()?->id;

        if ($assignmentId) {
            $assignment = PersonRoleAssignment::where('church_id', $this->church->id)->findOrFail($assignmentId);
            $this->form->setAssignment($assignment);
        }
    }

    #[Computed]
    public function pastorFunctions()
    {
        return FunctionRole::query()
            ->where('is_active', true)
            ->whereJsonContains('applies_to', 'pastor')
            ->orderBy('display_order')
            ->get(['id', 'name', 'slug']);
    }

    #[Computed]
    public function pastors()
    {
        return Person::query()
            ->whereJsonContains('natures', PersonNature::Pastor->value)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function save(): void
    {
        $data = $this->form->validate();

        $personId = $data['person_id'] ?? null;

        DB::transaction(function () use ($data, &$personId) {
            if ($this->form->pastorMode === 'new') {
                $person = Person::create([
                    'person_type' => PersonType::Individual->value,
                    'name' => $data['person_name'],
                    'natures' => [PersonNature::Pastor->value],
                    'managing_church_id' => $this->church->id,
                ]);

                if (! empty($data['person_email'])) {
                    $person->contacts()->create([
                        'type' => PersonContactType::Email->value,
                        'value' => $data['person_email'],
                        'is_primary' => true,
                    ]);
                }
                if (! empty($data['person_phone'])) {
                    $person->contacts()->create([
                        'type' => PersonContactType::Phone->value,
                        'value' => $data['person_phone'],
                        'is_primary' => true,
                    ]);
                }

                $personId = $person->id;
            }

            $payload = [
                'person_id' => $personId,
                'function_id' => $data['function_id'],
                'church_id' => $this->church->id,
                'started_at' => $data['start_date'] ?: null,
                'ended_at' => $data['end_date'] ?: null,
            ];

            if ($this->form->assignment) {
                $this->form->assignment->update($payload);
            } else {
                $this->form->assignment = PersonRoleAssignment::create($payload);
            }
        });

        session()->flash('status', __('Assignment saved.'));

        $this->redirect(route('admin.churches.pastors.index', $this->church), navigate: true);
    }
};
