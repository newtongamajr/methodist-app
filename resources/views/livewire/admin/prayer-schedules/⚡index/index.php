<?php

use App\Models\Church;
use App\Models\PrayerCampaign;
use App\Models\PrayerSchedule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
class extends Component
{
    use WithPagination;

    #[Url(as: 'church')]
    public ?int $churchFilter = null;

    #[Url(as: 'campaign')]
    public ?int $campaignFilter = null;

    public function mount(?int $church = null): void
    {
        $user = auth()->user();
        $this->churchFilter = $church ?: $user->currentChurchId();
        $current = PrayerCampaign::current()->orderByDesc('start_date')->first();
        $this->campaignFilter = $current?->id;
    }

    public function updatingChurchFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCampaignFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function churches()
    {
        $user = auth()->user();
        if ($user->hasRole('global_manager')) {
            return Church::orderBy('name')->get(['id', 'name']);
        }
        return Church::query()
            ->whereIn('id', $user->manageableChurchIds())
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function campaigns()
    {
        return PrayerCampaign::active()->orderByDesc('start_date')->get();
    }

    #[Computed]
    public function schedules()
    {
        $user = auth()->user();

        $q = PrayerSchedule::query()
            ->with(['church', 'campaign'])
            ->withCount('slots')
            ->orderByDesc('date')
            ->orderBy('start_time');

        if (! $user->hasRole('global_manager')) {
            $q->whereIn('church_id', $user->manageableChurchIds());
        }

        if ($this->churchFilter) {
            $q->where('church_id', $this->churchFilter);
        }

        if ($this->campaignFilter) {
            $q->where('prayer_campaign_id', $this->campaignFilter);
        }

        return $q->paginate(20);
    }

    public function delete(int $id): void
    {
        $user = auth()->user();
        $schedule = PrayerSchedule::findOrFail($id);
        if (! $user->hasRole('global_manager')) {
            abort_unless($user->canManageChurch($schedule->church_id), 403);
        }
        $schedule->delete();
        $this->dispatch('schedule-deleted');
    }
};