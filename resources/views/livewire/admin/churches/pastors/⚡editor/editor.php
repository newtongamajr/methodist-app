<?php

use App\Enums\PersonContactType;
use App\Enums\PersonNature;
use App\Enums\PersonType;
use App\Livewire\Forms\PersonRoleAssignmentForm;
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

    public PersonRoleAssignmentForm $form;

    /** existing | new — controls the person picker vs. inline create. */
    public string $personMode = 'existing';

    public string $person_name = '';

    public string $person_email = '';

    public string $person_phone = '';

    public function mount(int $churchId, ?int $assignmentId = null): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);

        $this->church = Church::findOrFail($churchId);
        $this->form->church_id = $this->church->id;
        $this->form->started_at = now()->toDateString();
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
        $this->form->church_id = $this->church->id;

        // For "new" mode, create the Person first (and contacts) before
        // running the form validator so person_id is populated.
        DB::transaction(function () {
            if ($this->personMode === 'new' && ! $this->form->person_id) {
                $this->validate([
                    'person_name' => ['required', 'string', 'max:255'],
                    'person_email' => ['nullable', 'email', 'max:255'],
                    'person_phone' => ['nullable', 'string', 'max:32'],
                ]);

                $person = Person::create([
                    'person_type' => PersonType::Individual->value,
                    'name' => $this->person_name,
                    'natures' => [PersonNature::Pastor->value],
                    'managing_church_id' => $this->church->id,
                ]);

                if ($this->person_email !== '') {
                    $person->contacts()->create([
                        'type' => PersonContactType::Email->value,
                        'value' => $this->person_email,
                        'is_primary' => true,
                    ]);
                }
                if ($this->person_phone !== '') {
                    $person->contacts()->create([
                        'type' => PersonContactType::Phone->value,
                        'value' => $this->person_phone,
                        'is_primary' => true,
                    ]);
                }

                $this->form->person_id = $person->id;
            }

            $this->form->save();
        });

        session()->flash('status', __('Assignment saved.'));

        $this->redirect(route('admin.churches.pastors.index', $this->church), navigate: true);
    }
};
