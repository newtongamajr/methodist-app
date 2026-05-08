<?php

use App\Enums\FastingRestriction;
use App\Enums\FastingType;
use App\Livewire\Forms\FastingCampaignForm;
use App\Models\FastingCampaign;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public FastingCampaignForm $form;

    public function mount(?int $campaignId = null): void
    {
        abort_unless(auth()->user()?->can('fasting.calendar.manage'), 403);

        if ($campaignId) {
            $this->form->setCampaign(FastingCampaign::findOrFail($campaignId));
        } else {
            $this->form->types = array_map(fn ($t) => $t->value, FastingType::cases());
            $this->form->restrictions = array_map(fn ($r) => $r->value, FastingRestriction::cases());
            $this->form->start_date = now()->toDateString();
            $this->form->end_date = now()->addWeeks(3)->toDateString();
        }
    }

    public function save(): void
    {
        $campaign = $this->form->save();

        session()->flash('status', __('Campaign saved.'));

        $this->redirect(route('admin.fasting-campaigns.edit', $campaign), navigate: true);
    }
};
