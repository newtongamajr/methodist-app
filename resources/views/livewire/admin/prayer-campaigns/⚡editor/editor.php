<?php

use App\Livewire\Forms\PrayerCampaignForm;
use App\Models\PrayerCampaign;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public PrayerCampaignForm $form;

    public function mount(?int $campaignId = null): void
    {
        abort_unless(auth()->user()?->can('prayer.schedule.manage'), 403);

        if ($campaignId) {
            $this->form->setCampaign(PrayerCampaign::findOrFail($campaignId));
        } else {
            $this->form->start_date = now()->toDateString();
            $this->form->end_date = now()->addWeeks(3)->toDateString();
        }
    }

    public function save(): void
    {
        $campaign = $this->form->save();

        session()->flash('status', __('Campaign saved.'));

        $this->redirect(route('admin.prayer-campaigns.edit', $campaign), navigate: true);
    }
};
