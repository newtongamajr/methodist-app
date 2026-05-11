<?php

use App\Enums\FunctionAppliesTo;
use App\Enums\GroupKind;
use App\Livewire\Forms\GroupForm;
use App\Livewire\Forms\PersonRoleAssignmentForm;
use App\Models\Church;
use App\Models\District;
use App\Models\EcclesiasticalRegion;
use App\Models\FunctionRole;
use App\Models\Group;
use App\Models\Person;
use App\Models\PersonRoleAssignment;
use App\Support\GenerateUniqueSlug;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public GroupForm $form;

    public PersonRoleAssignmentForm $memberForm;

    public bool $showMemberModal = false;

    /** Person picker search inside the member modal. */
    public string $personSearch = '';

    public function mount(?int $groupId = null, ?string $kind = null): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);

        if ($groupId) {
            $this->form->setGroup(Group::findOrFail($groupId));
        } else {
            if ($kind && in_array($kind, array_map(fn ($c) => $c->value, GroupKind::cases()), true)) {
                $this->form->kind = $kind;
            }
            $this->form->started_at = now()->toDateString();
        }
    }

    public function updatedFormLevel(): void
    {
        // Clear scope FKs that don't apply at the new level.
        if ($this->form->level === 'national') {
            $this->form->ecclesiastical_region_id = null;
            $this->form->district_id = null;
            $this->form->church_id = null;
        } elseif ($this->form->level === 'region') {
            $this->form->district_id = null;
            $this->form->church_id = null;
        } elseif ($this->form->level === 'district') {
            $this->form->church_id = null;
        }
    }

    public function updatedFormEcclesiasticalRegionId(): void
    {
        if ($this->form->district_id) {
            $district = District::find($this->form->district_id);
            if (! $district || $district->ecclesiastical_region_id !== $this->form->ecclesiastical_region_id) {
                $this->form->district_id = null;
            }
        }
        if ($this->form->church_id) {
            $church = Church::find($this->form->church_id);
            if (! $church || $church->ecclesiastical_region_id !== $this->form->ecclesiastical_region_id) {
                $this->form->church_id = null;
            }
        }
    }

    public function updatedFormDistrictId(): void
    {
        if ($this->form->church_id) {
            $church = Church::find($this->form->church_id);
            if (! $church || $church->district_id !== $this->form->district_id) {
                $this->form->church_id = null;
            }
        }
    }

    #[Computed]
    public function regions()
    {
        return EcclesiasticalRegion::orderBy('display_order')->get(['id', 'code', 'name']);
    }

    #[Computed]
    public function districts()
    {
        if (! $this->form->ecclesiastical_region_id) {
            return collect();
        }

        return District::query()
            ->where('ecclesiastical_region_id', $this->form->ecclesiastical_region_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function churches()
    {
        if (! $this->form->ecclesiastical_region_id) {
            return collect();
        }

        $q = Church::query()
            ->where('ecclesiastical_region_id', $this->form->ecclesiastical_region_id)
            ->where('is_active', true)
            ->orderBy('name');
        if ($this->form->district_id) {
            $q->where('district_id', $this->form->district_id);
        }

        return $q->get(['id', 'name']);
    }

    #[Computed]
    public function members()
    {
        if (! $this->form->group) {
            return collect();
        }

        return $this->form->group->assignments()
            ->with(['person:id,name', 'function:id,name,slug'])
            ->orderByDesc('started_at')
            ->orderBy('id')
            ->get();
    }

    /** Functions whose applies_to includes this group's kind. */
    #[Computed]
    public function eligibleFunctions()
    {
        return FunctionRole::query()
            ->where('is_active', true)
            ->whereJsonContains('applies_to', $this->form->kind)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'max_holders']);
    }

    #[Computed]
    public function candidatePersons()
    {
        $term = trim($this->personSearch);
        if ($term === '') {
            return collect();
        }
        $like = '%'.addcslashes($term, '%_\\').'%';

        return Person::query()
            ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('preferred_name', 'like', $like))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name']);
    }

    public function save(): void
    {
        if (empty($this->form->slug)) {
            $this->form->slug = (new GenerateUniqueSlug)(
                $this->form->name,
                Group::query()
                    ->where('ecclesiastical_region_id', $this->form->ecclesiastical_region_id)
                    ->where('district_id', $this->form->district_id)
                    ->where('church_id', $this->form->church_id)
                    ->whereKeyNot($this->form->group?->id ?? 0),
            );
        }

        $isCreating = $this->form->group === null;
        $group = $this->form->save();

        session()->flash('status', $isCreating ? __('Group created.') : __('Group updated.'));

        if ($isCreating) {
            $this->redirect(route('admin.groups.edit', $group), navigate: true);
        }
    }

    public function openMemberCreate(): void
    {
        $this->memberForm->reset();
        $this->personSearch = '';
        $this->memberForm->group_id = $this->form->group->id;
        $this->memberForm->started_at = now()->toDateString();
        $this->memberForm->function_id = $this->eligibleFunctions->first()?->id;
        $this->showMemberModal = true;
    }

    public function openMemberEdit(int $assignmentId): void
    {
        $assignment = $this->form->group->assignments()->findOrFail($assignmentId);
        $this->memberForm->setAssignment($assignment);
        $this->personSearch = '';
        $this->showMemberModal = true;
    }

    public function saveMember(): void
    {
        $this->memberForm->group_id = $this->form->group->id;
        $this->memberForm->save();
        $this->showMemberModal = false;
        $this->memberForm->reset();
        $this->personSearch = '';
        unset($this->members);
    }

    public function endMember(int $assignmentId): void
    {
        $this->form->group->assignments()->where('id', $assignmentId)->update([
            'ended_at' => now()->toDateString(),
        ]);
        unset($this->members);
    }

    public function deleteMember(int $assignmentId): void
    {
        $this->form->group->assignments()->where('id', $assignmentId)->delete();
        unset($this->members);
    }
};
