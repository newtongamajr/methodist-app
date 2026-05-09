<?php

use App\Livewire\Forms\PersonForm;
use App\Models\Church;
use App\Models\District;
use App\Models\EcclesiasticalRegion;
use App\Models\Person;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public PersonForm $form;

    public ?int $personId = null;

    public ?int $region_id = null;

    public ?int $district_id = null;

    public function mount(?int $personId = null, ?string $natureSeed = null): void
    {
        abort_unless(auth()->user()?->can('users.manage') || auth()->user()?->can('users.manage.local'), 403);

        $this->personId = $personId;

        if ($personId) {
            $person = Person::with('managingChurch')->findOrFail($personId);
            $this->form->setPerson($person);
            $this->district_id = $person->managingChurch?->district_id;
            $this->region_id = $person->managingChurch?->ecclesiastical_region_id;

            return;
        }

        // New-person flow: pre-seed nature when called via the visitor (or
        // any nature) quick-add link. Validates the value is a real nature
        // and matches the default person_type=individual.
        if ($natureSeed && \App\Enums\PersonNature::tryFrom($natureSeed) instanceof \App\Enums\PersonNature) {
            $nature = \App\Enums\PersonNature::from($natureSeed);
            if (! $nature->isOrganizational()) {
                $this->form->natures = [$nature->value];
            }
        }
    }

    public function updatedFormPersonType(): void
    {
        // Drop natures that don't apply to the new person_type so the
        // checkbox state matches the rendered list.
        $allowed = array_keys(\App\Enums\PersonNature::optionsForPersonType($this->form->person_type));
        $this->form->natures = array_values(array_intersect($this->form->natures, $allowed));

        // Org rows can't carry gender / marital_status — clear stale values
        // so the form rules don't reject the save with `prohibited`.
        if ($this->form->person_type === \App\Enums\PersonType::Organization->value) {
            $this->form->gender = '';
            $this->form->marital_status = '';
        }
    }

    public function updatedRegionId(): void
    {
        if ($this->district_id) {
            $district = District::find($this->district_id);
            if (! $district || $district->ecclesiastical_region_id !== $this->region_id) {
                $this->district_id = null;
            }
        }
        if ($this->form->managing_church_id) {
            $church = Church::find($this->form->managing_church_id);
            if (! $church || $church->ecclesiastical_region_id !== $this->region_id) {
                $this->form->managing_church_id = null;
            }
        }
    }

    public function updatedDistrictId(): void
    {
        if ($this->form->managing_church_id) {
            $church = Church::find($this->form->managing_church_id);
            if (! $church || $church->district_id !== $this->district_id) {
                $this->form->managing_church_id = null;
            }
        }
    }

    public function updatedFormManagingChurchId(): void
    {
        if (! $this->form->managing_church_id) {
            return;
        }
        $church = Church::find($this->form->managing_church_id);
        if (! $church) {
            return;
        }
        $this->region_id = $church->ecclesiastical_region_id;
        $this->district_id = $church->district_id;
    }

    #[Computed]
    public function regions()
    {
        return EcclesiasticalRegion::orderBy('display_order')->get(['id', 'code', 'name']);
    }

    #[Computed]
    public function districts()
    {
        if (! $this->region_id) {
            return collect();
        }

        return District::query()
            ->where('ecclesiastical_region_id', $this->region_id)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function churches()
    {
        $q = Church::query()->where('is_active', true)->orderBy('name');

        if ($this->district_id) {
            $q->where('district_id', $this->district_id);
        } elseif ($this->region_id) {
            $q->where('ecclesiastical_region_id', $this->region_id);
        }

        return $q->get(['id', 'name', 'city', 'state']);
    }

    public function save(): void
    {
        $isCreating = $this->form->person === null;

        $person = $this->form->save();

        session()->flash('status', $isCreating ? __('Person created.') : __('Person updated.'));

        if ($isCreating) {
            $this->redirect(route('admin.people.edit', $person), navigate: true);
        }
    }
};
