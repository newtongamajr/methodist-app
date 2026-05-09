<?php

use App\Models\PrayerCampaign;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    #[Url(as: 'sort')]
    public string $sortBy = 'start_date';

    #[Url(as: 'dir')]
    public string $sortDir = 'desc';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('prayer.schedule.manage'), 403);
    }

    public function sort(string $column): void
    {
        if (! in_array($column, ['name', 'start_date', 'schedules_count', 'is_active'], true)) {
            return;
        }
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = in_array($column, ['start_date', 'schedules_count'], true) ? 'desc' : 'asc';
        }
    }

    #[Computed]
    public function campaigns()
    {
        return PrayerCampaign::query()
            ->withCount('schedules')
            ->orderBy($this->sortBy, $this->sortDir)
            ->get();
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->can('prayer.schedule.manage'), 403);
        PrayerCampaign::findOrFail($id)->delete();
        unset($this->campaigns);
    }
};