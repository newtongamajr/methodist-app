<?php

use App\Models\FastingCampaign;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->can('fasting.calendar.manage'), 403);
    }

    #[Computed]
    public function campaigns()
    {
        return FastingCampaign::query()
            ->withCount('entries')
            ->orderByDesc('start_date')
            ->get();
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->can('fasting.calendar.manage'), 403);
        FastingCampaign::findOrFail($id)->delete();
        unset($this->campaigns);
    }
};