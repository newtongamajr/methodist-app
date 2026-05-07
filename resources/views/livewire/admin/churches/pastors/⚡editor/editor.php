<?php

use App\Enums\PastorRole;
use App\Models\Church;
use App\Models\Pastor;
use App\Models\PastorAssignment;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public Church $church;
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

    public function mount(int $churchId, ?int $assignmentId = null): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);

        $this->church = Church::findOrFail($churchId);
        $this->start_date = now()->toDateString();

        if ($assignmentId) {
            $this->assignment = PastorAssignment::where('church_id', $this->church->id)->findOrFail($assignmentId);
            $this->pastorMode = 'existing';
            $this->pastor_id = $this->assignment->pastor_id;
            $this->role = $this->assignment->role->value;
            $this->start_date = $this->assignment->start_date?->format('Y-m-d');
            $this->end_date = $this->assignment->end_date?->format('Y-m-d');
            $this->display_order = $this->assignment->display_order;
        }
    }

    #[Computed]
    public function pastors()
    {
        return Pastor::orderBy('name')->get(['id', 'name', 'email']);
    }

    public function save(): void
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

        $data = $this->validate($rules);

        $pastorId = $data['pastor_id'] ?? null;

        if ($this->pastorMode === 'new') {
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

        if ($this->assignment) {
            $this->assignment->update($payload);
        } else {
            $this->assignment = PastorAssignment::create($payload);
        }

        session()->flash('status', __('Assignment saved.'));

        $this->redirect(route('admin.churches.pastors.index', $this->church), navigate: true);
    }
};