<?php

use App\Livewire\Forms\DistrictForm;
use App\Models\District;
use App\Models\EcclesiasticalRegion;
use App\Support\GenerateUniqueSlug;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public DistrictForm $form;

    public function mount(?int $districtId = null, ?int $region = null): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);

        if ($districtId) {
            $this->form->setDistrict(District::findOrFail($districtId));
        } else {
            if ($region) {
                $this->form->ecclesiastical_region_id = $region;
            }
            $this->form->display_order = (int) (
                District::when(
                    $this->form->ecclesiastical_region_id,
                    fn ($q) => $q->where('ecclesiastical_region_id', $this->form->ecclesiastical_region_id),
                )->max('display_order') ?? 0
            ) + 1;
        }
    }

    #[Computed]
    public function regions()
    {
        return EcclesiasticalRegion::orderBy('display_order')->get(['id', 'code', 'name']);
    }

    public function save(): void
    {
        $data = $this->form->validate();

        if (empty($data['slug'])) {
            $data['slug'] = (new GenerateUniqueSlug)(
                $data['name'],
                District::query()
                    ->where('ecclesiastical_region_id', $data['ecclesiastical_region_id'])
                    ->whereKeyNot($this->form->district?->id ?? 0),
            );
            $this->form->slug = $data['slug'];
        }

        if ($this->form->district) {
            $this->form->district->update($data);
        } else {
            $this->form->district = District::create($data);
        }

        session()->flash('status', __('District saved.'));

        $this->redirect(
            route('admin.districts.index', ['region' => $this->form->ecclesiastical_region_id]),
            navigate: true,
        );
    }
};
