<?php

use App\Enums\LocationMode;
use App\Models\Church;
use App\Models\PrayerCampaign;
use App\Models\PrayerSchedule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public ?PrayerSchedule $schedule = null;

    public ?int $church_id = null;
    public ?int $prayer_campaign_id = null;
    public string $date = '';
    public string $start_time = '06:00';
    public string $end_time = '20:00';
    public int $slot_minutes = 60;
    public int $capacity_per_slot = 5;
    public string $mode = 'presential';
    public string $notes = '';

    public function mount(?int $scheduleId = null): void
    {
        $user = auth()->user();
        abort_unless($user && $user->can('prayer.schedule.manage'), 403);

        if ($scheduleId) {
            $this->schedule = PrayerSchedule::findOrFail($scheduleId);
            $this->church_id = $this->schedule->church_id;
            $this->prayer_campaign_id = $this->schedule->prayer_campaign_id;
            $this->date = $this->schedule->date->format('Y-m-d');
            $this->start_time = substr($this->schedule->start_time, 0, 5);
            $this->end_time = substr($this->schedule->end_time, 0, 5);
            $this->slot_minutes = $this->schedule->slot_minutes;
            $this->capacity_per_slot = $this->schedule->capacity_per_slot;
            $this->mode = $this->schedule->mode->value;
            $this->notes = $this->schedule->notes ?? '';
        } else {
            $this->church_id = $user->currentChurchId();
            $current = PrayerCampaign::current()->orderByDesc('start_date')->first()
                ?: PrayerCampaign::active()->orderByDesc('start_date')->first();
            $this->prayer_campaign_id = $current?->id;
            $this->date = ($current?->start_date ?? now()->addDay())->toDateString();
        }
    }

    public function updatedPrayerCampaignId(): void
    {
        $c = $this->campaign;
        if ($c && (! $this->date || ! $c->includesDate($this->date))) {
            $this->date = $c->start_date->format('Y-m-d');
        }
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
    public function campaign(): ?PrayerCampaign
    {
        return $this->prayer_campaign_id ? PrayerCampaign::find($this->prayer_campaign_id) : null;
    }

    public function save(): void
    {
        $data = $this->validate([
            'church_id' => ['required', 'integer', 'exists:churches,id'],
            'prayer_campaign_id' => ['required', 'integer', 'exists:prayer_campaigns,id'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'slot_minutes' => ['required', 'integer', 'in:30,60'],
            'capacity_per_slot' => ['required', 'integer', 'min:1', 'max:200'],
            'mode' => ['required', 'in:'.implode(',', array_map(fn ($c) => $c->value, LocationMode::cases()))],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $user = auth()->user();
        if (! $user->hasRole('global_manager')) {
            abort_unless($user->canManageChurch((int) $data['church_id']), 403);
        }

        $campaign = PrayerCampaign::find($data['prayer_campaign_id']);
        if (! $campaign?->includesDate($data['date'])) {
            $this->addError('date', __('This date must be inside the campaign window (:start to :end).', [
                'start' => $campaign?->start_date?->isoFormat('LL') ?? '—',
                'end' => $campaign?->end_date?->isoFormat('LL') ?? '—',
            ]));
            return;
        }

        if ($this->schedule) {
            $this->schedule->update($data);
        } else {
            $this->schedule = PrayerSchedule::create($data);
        }

        $this->schedule->refresh()->regenerateSlots();

        session()->flash('status', __('Schedule saved.'));

        $this->redirect(route('admin.prayer-schedules.edit', $this->schedule), navigate: true);
    }
};
