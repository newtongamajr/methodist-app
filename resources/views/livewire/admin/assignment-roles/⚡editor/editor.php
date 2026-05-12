<?php

use App\Livewire\Forms\AssignmentRoleForm;
use App\Models\AssignmentRole;
use App\Support\GenerateUniqueSlug;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public AssignmentRoleForm $form;

    public function mount(?int $assignmentRoleId = null): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);

        if ($assignmentRoleId) {
            $this->form->setAssignmentRole(AssignmentRole::findOrFail($assignmentRoleId));
        }
    }

    public function save(): void
    {
        if (empty($this->form->slug)) {
            $this->form->slug = (new GenerateUniqueSlug)(
                $this->form->name,
                AssignmentRole::query()->whereKeyNot($this->form->assignmentRole?->id ?? 0),
            );
        }

        $isCreating = $this->form->assignmentRole === null;
        $role = $this->form->save();

        session()->flash('status', $isCreating ? __('Assignment role created.') : __('Assignment role updated.'));

        if ($isCreating) {
            $this->redirect(route('admin.assignment-roles.edit', $role), navigate: true);
        }
    }
};