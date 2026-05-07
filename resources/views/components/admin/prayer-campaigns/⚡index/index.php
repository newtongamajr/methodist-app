<?php

use App\Models\PrayerCampaign;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->can('prayer.schedule.manage'), 403);
    }

    #[Computed]
    public function campaigns()
    {
        return PrayerCampaign::query()
            ->withCount('schedules')
            ->orderByDesc('start_date')
            ->get();
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->can('prayer.schedule.manage'), 403);
        PrayerCampaign::findOrFail($id)->delete();
        unset($this->campaigns);
    }
};