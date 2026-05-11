<?php

use App\Livewire\Forms\RegionForm;
use App\Models\EcclesiasticalRegion;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public RegionForm $form;

    public function mount(?int $regionId = null): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);

        if ($regionId) {
            $this->form->setRegion(EcclesiasticalRegion::findOrFail($regionId));
        } else {
            $this->form->display_order = (int) (EcclesiasticalRegion::max('display_order') ?? 0) + 1;
        }
    }

    public function save(): void
    {
        $this->form->save();

        session()->flash('status', __('Region saved.'));

        $this->redirect(route('admin.regions.index'), navigate: true);
    }
};
