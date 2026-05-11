<?php

use App\Enums\FastingRestriction;
use App\Enums\FastingType;
use App\Models\FastingCampaign;
use App\Models\FastingEntry;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public ?int $campaignId = null;
    public string $month = '';
    public bool $isModalOpen = false;
    public ?string $editingDate = null;
    public string $type = '';
    public array $restrictions = [];
    public string $notes = '';

    public function mount(): void
    {
        // Prefer a campaign that includes today, then any campaign that
        // overlaps the current month, then the most-recent active one.
        $current = FastingCampaign::query()->current()->orderByDesc('start_date')->first()
            ?: $this->campaigns->first()
            ?: FastingCampaign::query()->active()->orderByDesc('start_date')->first();

        $this->campaignId = $current?->id;
        $this->month = ($current?->start_date ?? now())->format('Y-m');
        $this->type = $this->defaultType();
    }

    public function updatedCampaignId(): void
    {
        $c = $this->campaign;
        if ($c) {
            $this->month = $c->start_date->format('Y-m');
            $this->type = $this->defaultType();
        }
    }

    private function defaultType(): string
    {
        $allowed = $this->campaign?->types ?? [];
        if (in_array(FastingType::TwelveHours->value, $allowed, true)) {
            return FastingType::TwelveHours->value;
        }
        return $allowed[0] ?? FastingType::TwelveHours->value;
    }

    /**
     * Active campaigns whose [start_date, end_date] window overlaps the
     * current calendar month. A campaign overlaps the month iff
     * start_date <= last day of month AND end_date >= first day of month.
     */
    #[Computed]
    public function campaigns()
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        return FastingCampaign::active()
            ->whereDate('start_date', '<=', $monthEnd)
            ->whereDate('end_date', '>=', $monthStart)
            ->orderByDesc('start_date')
            ->get();
    }

    #[Computed]
    public function campaign(): ?FastingCampaign
    {
        return $this->campaignId ? FastingCampaign::find($this->campaignId) : null;
    }

    #[Computed]
    public function allowedTypes(): array
    {
        return collect(FastingType::cases())
            ->filter(fn ($t) => in_array($t->value, $this->campaign?->types ?? [], true))
            ->values()
            ->all();
    }

    #[Computed]
    public function allowedRestrictions(): array
    {
        return collect(FastingRestriction::cases())
            ->filter(fn ($r) => in_array($r->value, $this->campaign?->restrictions ?? [], true))
            ->values()
            ->all();
    }

    public function previousMonth(): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->addMonth()->format('Y-m');
    }

    #[Computed]
    public function entries()
    {
        if (! $this->campaign) {
            return collect();
        }

        // Read by person — the calendar reflects the acted-as Person's fasts,
        // not the recording User's. When not acting-as, this is the user's own
        // Person, so the visible behavior is unchanged for solo users.
        $person = auth()->user()?->effectivePerson();
        if (! $person) {
            return collect();
        }

        return FastingEntry::query()
            ->where('person_id', $person->id)
            ->where('fasting_campaign_id', $this->campaign->id)
            ->get()
            ->keyBy(fn ($e) => $e->date->format('Y-m-d'));
    }

    #[Computed]
    public function calendar(): array
    {
        $start = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        $gridStart = $start->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $start->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $campaign = $this->campaign;

        $days = [];
        for ($d = $gridStart->copy(); $d->lte($gridEnd); $d->addDay()) {
            $days[] = [
                'date' => $d->format('Y-m-d'),
                'day' => $d->day,
                'inMonth' => $d->month === $start->month,
                'isToday' => $d->isToday(),
                'inCampaign' => $campaign ? $campaign->includesDate($d) : false,
            ];
        }

        return $days;
    }

    /**
     * Distinct member count per date (campaign-wide), keyed by Y-m-d.
     */
    #[Computed]
    public function participantsByDate(): array
    {
        if (! $this->campaign) {
            return [];
        }

        // Count distinct Persons (one fast per Person per day), not Users — a
        // single User logging fasts for themselves and a child should count as
        // two participants on a shared day.
        return FastingEntry::query()
            ->where('fasting_campaign_id', $this->campaign->id)
            ->selectRaw('`date` as day, COUNT(DISTINCT person_id) as total')
            ->groupBy('date')
            ->pluck('total', 'day')
            ->all();
    }

    public function openDay(string $date): void
    {
        if (! $this->campaign || ! $this->campaign->includesDate($date)) {
            return;
        }

        $this->editingDate = $date;

        $existing = $this->entries[$date] ?? null;
        if ($existing) {
            $this->type = $existing->type->value;
            $this->restrictions = $existing->restrictions ?? [];
            $this->notes = $existing->notes ?? '';
        } else {
            $this->type = $this->defaultType();
            $this->restrictions = [];
            $this->notes = '';
        }

        $this->isModalOpen = true;
    }

    public function close(): void
    {
        $this->isModalOpen = false;
        $this->editingDate = null;
    }

    public function save(): void
    {
        $campaign = $this->campaign;
        if (! $campaign) {
            return;
        }

        $data = $this->validate([
            'editingDate' => ['required', 'date'],
            'type' => ['required', 'string', 'in:'.implode(',', $campaign->types)],
            'restrictions' => ['array'],
            'restrictions.*' => ['string', 'in:'.implode(',', $campaign->restrictions ?: ['_none_'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $campaign->includesDate($data['editingDate'])) {
            $this->addError('editingDate', __('This date is outside the campaign window.'));
            return;
        }

        $person = auth()->user()?->effectivePerson();
        if (! $person) {
            return;
        }

        FastingEntry::updateOrCreate(
            [
                'person_id' => $person->id,
                'fasting_campaign_id' => $campaign->id,
                'date' => $data['editingDate'],
            ],
            [
                // user_id = who clicked save (may differ from person_id when
                // a parent records a fast on their child's behalf).
                'user_id' => auth()->id(),
                'type' => $data['type'],
                'restrictions' => $data['restrictions'] ?: null,
                'notes' => $data['notes'] ?: null,
            ],
        );

        unset($this->entries);
        $this->isModalOpen = false;
        $this->editingDate = null;
    }

    public function delete(string $date): void
    {
        if (! $this->campaign) {
            return;
        }

        $person = auth()->user()?->effectivePerson();
        if (! $person) {
            return;
        }

        FastingEntry::where('person_id', $person->id)
            ->where('fasting_campaign_id', $this->campaign->id)
            ->where('date', $date)
            ->delete();

        unset($this->entries);
        $this->isModalOpen = false;
        $this->editingDate = null;
    }
};