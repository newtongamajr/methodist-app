<?php

use App\Enums\FunctionAppliesTo;
use App\Livewire\Forms\PersonRoleAssignmentForm;
use App\Models\Church;
use App\Models\District;
use App\Models\EcclesiasticalRegion;
use App\Models\FunctionRole;
use App\Models\Person;
use App\Models\PersonRoleAssignment;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public PersonRoleAssignmentForm $form;

    public Person $person;

    public bool $showModal = false;

    public function mount(int $personId): void
    {
        abort_unless(auth()->user()?->can('users.manage') || auth()->user()?->can('users.manage.local'), 403);
        $this->person = Person::findOrFail($personId);
    }

    #[Computed]
    public function assignments()
    {
        return $this->person->roleAssignments()
            ->with(['function', 'church:id,name', 'group:id,name', 'region:id,code,name', 'district:id,name'])
            ->orderByDesc('started_at')
            ->orderBy('id')
            ->get();
    }

    /** All seeded functions, grouped by applies_to for the modal picker. */
    #[Computed]
    public function functions()
    {
        return FunctionRole::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'applies_to', 'max_holders']);
    }

    /** Function whose id the form currently points to (for scope-picker UX). */
    #[Computed]
    public function selectedFunction(): ?FunctionRole
    {
        return $this->form->function_id
            ? FunctionRole::query()->find($this->form->function_id)
            : null;
    }

    #[Computed]
    public function regions()
    {
        return EcclesiasticalRegion::orderBy('display_order')->get(['id', 'code', 'name']);
    }

    #[Computed]
    public function districts()
    {
        return District::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'ecclesiastical_region_id']);
    }

    #[Computed]
    public function churches()
    {
        return Church::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'state']);
    }

    public function openCreate(): void
    {
        $this->form->reset();
        $this->form->person_id = $this->person->id;
        $this->form->started_at = now()->toDateString();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $assignment = $this->person->roleAssignments()->findOrFail($id);
        $this->form->setAssignment($assignment);
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->form->person_id = $this->person->id;

        // Clear scope FKs that don't match the chosen function's context, so
        // the observer's XOR + applies_to guards don't trip on stale picks.
        $function = $this->selectedFunction;
        if ($function) {
            $kind = $this->scopeKindFor($function);
            if ($kind !== 'group') {
                $this->form->group_id = null;
            }
            if ($kind !== 'church') {
                $this->form->church_id = null;
            }
            if ($kind !== 'district') {
                $this->form->district_id = null;
            }
            if (! in_array($kind, ['region', 'district', 'church'], true)) {
                $this->form->ecclesiastical_region_id = null;
            }
        }

        $this->form->save();
        $this->showModal = false;
        $this->form->reset();
        unset($this->assignments);
    }

    public function endAssignment(int $id): void
    {
        $this->person->roleAssignments()->where('id', $id)->update([
            'ended_at' => now()->toDateString(),
        ]);
        unset($this->assignments);
    }

    public function delete(int $id): void
    {
        $this->person->roleAssignments()->where('id', $id)->delete();
        unset($this->assignments);
    }

    /**
     * What scope this function expects, derived from applies_to. Drives the
     * scope-picker UX in the modal. national_admin → null (no scope).
     */
    public function scopeKindFor(FunctionRole $function): ?string
    {
        if ($function->appliesTo(FunctionAppliesTo::Pastor->value)) {
            return 'church';
        }
        if ($function->appliesTo(FunctionAppliesTo::Council->value)
            || $function->appliesTo(FunctionAppliesTo::Ministry->value)
            || $function->appliesTo(FunctionAppliesTo::Commission->value)) {
            return 'group';
        }
        if ($function->appliesTo(FunctionAppliesTo::Admin->value)) {
            return match ($function->slug) {
                'national_admin' => null,
                'regional_admin' => 'region',
                'district_admin' => 'district',
                'local_admin' => 'church',
                default => null,
            };
        }

        return null;
    }
};
