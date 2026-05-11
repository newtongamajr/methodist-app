<?php

use App\Livewire\Concerns\HasSortableColumns;
use App\Models\PrayerCampaign;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    use HasSortableColumns;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('prayer.schedule.manage'), 403);
    }

    protected function sortableColumns(): array
    {
        return ['name', 'start_date', 'schedules_count', 'is_active'];
    }

    protected function defaultSortBy(): string
    {
        return 'start_date';
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