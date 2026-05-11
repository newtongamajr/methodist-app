<?php

use App\Livewire\Forms\PrayerScheduleForm;
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
    public PrayerScheduleForm $form;

    public function mount(?int $scheduleId = null): void
    {
        $user = auth()->user();
        abort_unless($user && $user->can('prayer.schedule.manage'), 403);

        if ($scheduleId) {
            $this->form->setSchedule(PrayerSchedule::findOrFail($scheduleId));
        } else {
            $this->form->church_id = $user->currentChurchId();
            $current = PrayerCampaign::current()->orderByDesc('start_date')->first()
                ?: PrayerCampaign::active()->orderByDesc('start_date')->first();
            $this->form->prayer_campaign_id = $current?->id;
            $this->form->date = ($current?->start_date ?? now()->addDay())->toDateString();
        }
    }

    public function updatedFormPrayerCampaignId(): void
    {
        $c = $this->campaign;
        if ($c && (! $this->form->date || ! $c->includesDate($this->form->date))) {
            $this->form->date = $c->start_date->format('Y-m-d');
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
        return $this->form->prayer_campaign_id ? PrayerCampaign::find($this->form->prayer_campaign_id) : null;
    }

    public function save(): void
    {
        $data = $this->form->validate();

        $user = auth()->user();
        if (! $user->hasRole('global_manager')) {
            abort_unless($user->canManageChurch((int) $data['church_id']), 403);
        }

        $campaign = PrayerCampaign::find($data['prayer_campaign_id']);
        if (! $campaign?->includesDate($data['date'])) {
            $this->addError('form.date', __('This date must be inside the campaign window (:start to :end).', [
                'start' => $campaign?->start_date?->isoFormat('LL') ?? '—',
                'end' => $campaign?->end_date?->isoFormat('LL') ?? '—',
            ]));

            return;
        }

        if ($this->form->schedule) {
            $this->form->schedule->update($data);
        } else {
            $this->form->schedule = PrayerSchedule::create($data);
        }

        $this->form->schedule->refresh()->regenerateSlots();

        session()->flash('status', __('Schedule saved.'));

        $this->redirect(route('admin.prayer-schedules.edit', $this->form->schedule), navigate: true);
    }
};
