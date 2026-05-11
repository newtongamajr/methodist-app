<?php

use App\Livewire\Forms\PastorAssignmentForm;
use App\Models\Church;
use App\Models\Pastor;
use App\Models\PastorAssignment;
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

        if ($assignmentId) {
            $assignment = PastorAssignment::where('church_id', $this->church->id)->findOrFail($assignmentId);
            $this->form->setAssignment($assignment);
        }
    }

    #[Computed]
    public function pastors()
    {
        return Pastor::orderBy('name')->get(['id', 'name', 'email']);
    }

    public function save(): void
    {
        $data = $this->form->validate();

        $pastorId = $data['pastor_id'] ?? null;

        if ($this->form->pastorMode === 'new') {
            $pastor = Pastor::create([
                'name' => $data['pastor_name'],
                'email' => $data['pastor_email'] ?: null,
                'phone' => $data['pastor_phone'] ?: null,
            ]);
            $pastorId = $pastor->id;
        }

        $payload = [
            'pastor_id' => $pastorId,
            'church_id' => $this->church->id,
            'role' => $data['role'],
            'start_date' => $data['start_date'] ?: null,
            'end_date' => $data['end_date'] ?: null,
            'display_order' => $data['display_order'],
        ];

        if ($this->form->assignment) {
            $this->form->assignment->update($payload);
        } else {
            $this->form->assignment = PastorAssignment::create($payload);
        }

        session()->flash('status', __('Assignment saved.'));

        $this->redirect(route('admin.churches.pastors.index', $this->church), navigate: true);
    }
};
