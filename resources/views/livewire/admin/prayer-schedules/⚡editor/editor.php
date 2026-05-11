<?php

use App\Livewire\Forms\PrayerScheduleForm;
use App\Models\Church;
use App\Models\PrayerCampaign;
use App\Models\PrayerSchedule;
use Illuminate\Support\Carbon;
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
            // Create-mode uses `dates` (a multi-date pillbox); leave it empty
            // so the user must explicitly pick at least one date below.
            $this->form->dates = [];
        }
    }

    public function updatedFormPrayerCampaignId(): void
    {
        // Switching campaign invalidates any previously-picked dates that
        // fall outside the new window — drop them so the pillbox doesn't
        // try to render orphan values.
        $c = $this->campaign;
        if (! $c) {
            $this->form->dates = [];

            return;
        }
        $this->form->dates = array_values(array_filter(
            $this->form->dates,
            fn ($d) => is_string($d) && $c->includesDate($d),
        ));
        if ($this->form->schedule && (! $this->form->date || ! $c->includesDate($this->form->date))) {
            $this->form->date = $c->start_date->format('Y-m-d');
        }
    }

    /**
     * Pillbox option list: every day in the campaign window from today
     * (inclusive) through the campaign end. Past dates are dropped so an
     * admin can't schedule into the past.
     *
     * @return array<int, array{value: string, label: string}>
     */
    #[Computed]
    public function availableDates(): array
    {
        $c = $this->campaign;
        if (! $c) {
            return [];
        }

        $start = Carbon::parse($c->start_date)->startOfDay();
        $today = now()->startOfDay();
        if ($start->lt($today)) {
            $start = $today;
        }
        $end = Carbon::parse($c->end_date)->startOfDay();
        if ($end->lt($start)) {
            return [];
        }

        $out = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $out[] = [
                'value' => $d->format('Y-m-d'),
                // Short DD/MM/YY label for the pillbox tag.
                'label' => $d->format('d/m/y'),
            ];
        }

        return $out;
    }

    #[Computed]
    public function churches()
    {
        $user = auth()->user();
        if ($user->hasRole('national_admin')) {
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
        if (! $user->hasRole('national_admin')) {
            abort_unless($user->canManageChurch((int) $data['church_id']), 403);
        }

        $campaign = PrayerCampaign::find($data['prayer_campaign_id']);

        // Edit mode — single existing row gets updated, then its slots are
        // regenerated. Same as the original flow.
        if ($this->form->schedule) {
            if (! $campaign?->includesDate($data['date'])) {
                $this->addError('form.date', __('This date must be inside the campaign window (:start to :end).', [
                    'start' => $campaign?->start_date?->isoFormat('LL') ?? '—',
                    'end' => $campaign?->end_date?->isoFormat('LL') ?? '—',
                ]));

                return;
            }

            $this->form->schedule->update($data);
            $this->form->schedule->refresh()->regenerateSlots();
            session()->flash('status', __('Schedule saved.'));
            $this->redirect(route('admin.prayer-schedules.edit', $this->form->schedule), navigate: true);

            return;
        }

        // Create mode — fan out one PrayerSchedule per picked date, sharing
        // the rest of the configuration. Drop dates that fell out of the
        // window between selection and submit, and de-dupe.
        $today = now()->startOfDay()->toDateString();
        $picked = collect($data['dates'])
            ->filter(fn ($d) => $campaign?->includesDate($d) && $d >= $today)
            ->unique()
            ->values();

        if ($picked->isEmpty()) {
            $this->addError('form.dates', __('Pick at least one date inside the campaign window (:start to :end).', [
                'start' => $campaign?->start_date?->isoFormat('LL') ?? '—',
                'end' => $campaign?->end_date?->isoFormat('LL') ?? '—',
            ]));

            return;
        }

        $shared = collect($data)->except(['date', 'dates'])->all();
        $created = [];
        foreach ($picked as $d) {
            $schedule = PrayerSchedule::create($shared + ['date' => $d]);
            $schedule->refresh()->regenerateSlots();
            $created[] = $schedule;
        }

        session()->flash('status', __(':count schedules created.', ['count' => count($created)]));
        $this->redirect(route('admin.prayer-schedules.index'), navigate: true);
    }
};
