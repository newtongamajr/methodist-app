<?php

use App\Livewire\Forms\RegionForm;
use App\Models\EcclesiasticalRegion;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public RegionForm $form;

    /** Active tab when editing — bookmarkable via ?tab=. */
    #[Url(as: 'tab')]
    public string $tab = 'details';

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
        $isCreating = $this->form->region === null;
        $region = $this->form->save();

        session()->flash('status', __('Region saved.'));

        if ($isCreating) {
            // First save: land on the edit page so the Person tabs become available.
            $this->redirect(route('admin.regions.edit', $region), navigate: true);
        }
    }
};
