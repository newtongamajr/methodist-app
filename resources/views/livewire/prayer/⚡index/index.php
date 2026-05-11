<?php

use App\Enums\LocationMode;
use App\Enums\SignupStatus;
use App\Models\PrayerCampaign;
use App\Models\PrayerSignup;
use App\Models\PrayerSlot;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public ?int $churchId = null;
    public ?int $campaignId = null;
    public string $selectedDate = '';
    public string $coverageFilter = 'all'; // all | mine | user
    public ?int $userFilterId = null;
    public string $modeFilter = 'any'; // any | presential | home

    /** Per-slot picker state for admins: prayer_slot_id => candidate user_id */
    public array $assignChoice = [];

    // ─── Bulk-signup state ──────────────────────────────────────────────────
    // Modal that signs the effective Person up for every slot matching a
    // (mode, start_time) shape across a date range. Skipped reasons are kept
    // so the post-execution report explains why each date didn't take.

    public bool $showBulkModal = false;

    public string $bulkMode = 'presential';

    public string $bulkStartTime = '06:00';

    public string $bulkFromDate = '';

    public string $bulkToDate = '';

    public bool $showReportModal = false;

    /** @var array{created: int, skipped: array<int, array{date: string, reason: string}>} */
    public array $bulkReport = ['created' => 0, 'skipped' => []];

    public function mount(): void
    {
        $this->churchId = auth()->user()->currentChurchId();

        $current = PrayerCampaign::current()->orderByDesc('start_date')->first()
            ?: $this->campaigns->first();
        $this->campaignId = $current?->id;

        $first = $this->days->first();
        $this->selectedDate = $first ?: ($current?->start_date?->toDateString() ?? now()->toDateString());
    }

    public function updatedCampaignId(): void
    {
        $first = $this->days->first();
        $this->selectedDate = $first ?: ($this->campaign?->start_date?->toDateString() ?? now()->toDateString());
    }

    public function previousDay(): void
    {
        $days = $this->days->values()->all();
        $current = (string) $this->selectedDate;
        $idx = array_search($current, $days, true);
        if ($idx !== false && $idx > 0) {
            $this->selectedDate = $days[$idx - 1];
        } elseif ($idx === false && ! empty($days)) {
            // selectedDate fell out of sync with the day list — snap to the
            // last available day so the user isn't stuck.
            $this->selectedDate = end($days);
        }
    }

    public function nextDay(): void
    {
        $days = $this->days->values()->all();
        $current = (string) $this->selectedDate;
        $idx = array_search($current, $days, true);
        if ($idx !== false && $idx < count($days) - 1) {
            $this->selectedDate = $days[$idx + 1];
        } elseif ($idx === false && ! empty($days)) {
            $this->selectedDate = $days[0];
        }
    }

    /** Active campaigns whose window overlaps the current calendar month. */
    #[Computed]
    public function campaigns()
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        return PrayerCampaign::active()
            ->whereDate('start_date', '<=', $monthEnd)
            ->whereDate('end_date', '>=', $monthStart)
            ->orderByDesc('start_date')
            ->get();
    }

    #[Computed]
    public function campaign(): ?PrayerCampaign
    {
        return $this->campaignId ? PrayerCampaign::find($this->campaignId) : null;
    }

    #[Computed]
    public function days()
    {
        if (! $this->churchId || ! $this->campaignId) {
            return collect();
        }

        // MySQL returns DATE() as a YYYY-MM-DD string but Livewire's hydration
        // cycle sometimes turns it into a richer datetime; normalize through
        // Carbon so the value always matches whatever wire:model captures
        // from the <option value="…"> attribute.
        return PrayerSlot::query()
            ->where('church_id', $this->churchId)
            ->where('prayer_campaign_id', $this->campaignId)
            ->selectRaw('DATE(starts_at) as day')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('day')
            ->map(fn ($d) => \Illuminate\Support\Carbon::parse((string) $d)->format('Y-m-d'))
            ->values();
    }

    #[Computed]
    public function daySlots()
    {
        if (! $this->churchId || ! $this->selectedDate || ! $this->campaignId) {
            return collect();
        }

        return PrayerSlot::query()
            ->where('church_id', $this->churchId)
            ->where('prayer_campaign_id', $this->campaignId)
            ->whereDate('starts_at', $this->selectedDate)
            // person.name is the participant; user.name is the actor that
            // recorded the row (parent recording for a child differs). The
            // user's person_id lets the Blade compare it against the signup's
            // person_id to detect proxy entries without an extra query.
            ->with(['confirmedSignups.user:id,name,person_id', 'confirmedSignups.person:id,name'])
            ->orderBy('starts_at')
            ->get();
    }

    #[Computed]
    public function mySignups(): array
    {
        // Reflect signups for the acted-as Person (or the user's own Person
        // when not acting-as). The same parent toggling between themselves
        // and a child sees only that one's slots highlighted at a time.
        $person = auth()->user()?->effectivePerson();
        if (! $person) {
            return [];
        }

        return PrayerSignup::query()
            ->where('person_id', $person->id)
            ->where('status', SignupStatus::Confirmed)
            ->pluck('prayer_slot_id')
            ->all();
    }

    #[Computed]
    public function churchUsers()
    {
        if (! $this->churchId) {
            return collect();
        }

        return User::query()
            ->whereIn('id', PrayerSignup::query()
                ->where('status', SignupStatus::Confirmed)
                ->whereHas('slot', fn ($q) => $q->where('church_id', $this->churchId))
                ->select('user_id')
                ->distinct()
            )
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function isAdminHere(): bool
    {
        return $this->churchId
            ? auth()->user()->canManageChurch($this->churchId)
            : false;
    }

    #[Computed]
    public function attachableUsers()
    {
        if (! $this->churchId || ! $this->isAdminHere) {
            return collect();
        }

        return User::query()
            ->whereHas('churches', fn ($q) => $q->where('churches.id', $this->churchId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function suggestions()
    {
        if (! $this->churchId || ! $this->campaignId) {
            return collect();
        }

        // Exclude slots the effective Person is already on — otherwise a slot
        // with spare capacity (e.g. 1/5 = 20% coverage) stays in the
        // suggestion list after the user joins it, and a subsequent click
        // looks like a no-op (the upsert just touches the same row). Take
        // the slot out of suggestions on join so the next under-covered slot
        // can rotate in.
        $excludeIds = $this->mySignups;

        return PrayerSlot::query()
            ->where('church_id', $this->churchId)
            ->where('prayer_campaign_id', $this->campaignId)
            ->when($excludeIds, fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->upcoming()
            ->withCount('confirmedSignups')
            ->get()
            ->filter(fn ($s) => $s->confirmed_signups_count === 0 || ($s->confirmed_signups_count / max($s->capacity, 1)) < 0.3)
            ->sortBy('starts_at')
            ->take(5)
            ->values();
    }

    public function join(int $slotId): void
    {
        $this->assignToSlot($slotId, (int) auth()->id());
    }

    public function openBulkModal(): void
    {
        $campaign = $this->campaign;
        $today = now()->startOfDay()->toDateString();
        // Default the range to "today (or campaign start) → campaign end" so
        // the user's first interaction is just picking a time + mode.
        $start = $campaign?->start_date?->toDateString() ?? $today;
        $this->bulkFromDate = $start < $today ? $today : $start;
        $this->bulkToDate = $campaign?->end_date?->toDateString() ?? $this->bulkFromDate;
        // Carry over the current mode filter as a sensible default (else fall
        // back to presential, the most common mode for in-person churches).
        $this->bulkMode = in_array($this->modeFilter, ['presential', 'home'], true)
            ? $this->modeFilter
            : 'presential';
        $this->bulkStartTime = '06:00';
        $this->resetErrorBag('bulk');
        $this->showBulkModal = true;
    }

    public function applyBulk(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $campaign = $this->campaign;
        if (! $campaign || ! $this->churchId) {
            $this->addError('bulk', __('Pick a church and a campaign first.'));

            return;
        }

        $person = $user->effectivePerson();
        if (! $person) {
            $this->addError('bulk', __('Your account has no Person record yet.'));

            return;
        }

        $allowedModes = array_map(fn ($m) => $m->value, LocationMode::cases());
        $data = $this->validate([
            'bulkMode' => ['required', 'in:'.implode(',', $allowedModes)],
            'bulkStartTime' => ['required', 'date_format:H:i'],
            'bulkFromDate' => ['required', 'date'],
            'bulkToDate' => ['required', 'date', 'after_or_equal:bulkFromDate'],
        ]);

        $from = Carbon::parse($data['bulkFromDate'])->startOfDay();
        $to = Carbon::parse($data['bulkToDate'])->startOfDay();
        $today = now()->startOfDay();

        $created = 0;
        $skipped = [];

        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $dateStr = $d->format('Y-m-d');

            if (! $campaign->includesDate($dateStr)) {
                $skipped[] = ['date' => $dateStr, 'reason' => 'out_of_window'];

                continue;
            }
            if ($d->lt($today)) {
                $skipped[] = ['date' => $dateStr, 'reason' => 'past'];

                continue;
            }

            $slot = PrayerSlot::query()
                ->where('prayer_campaign_id', $campaign->id)
                ->where('church_id', $this->churchId)
                ->where('mode', $data['bulkMode'])
                ->whereDate('starts_at', $dateStr)
                ->whereTime('starts_at', $data['bulkStartTime'].':00')
                ->withCount('confirmedSignups')
                ->first();

            if (! $slot) {
                $skipped[] = ['date' => $dateStr, 'reason' => 'not_found'];

                continue;
            }
            if ($slot->starts_at->isPast()) {
                $skipped[] = ['date' => $dateStr, 'reason' => 'past'];

                continue;
            }

            $existing = PrayerSignup::query()
                ->where('prayer_slot_id', $slot->id)
                ->where('person_id', $person->id)
                ->first();
            if ($existing) {
                $skipped[] = ['date' => $dateStr, 'reason' => 'already'];

                continue;
            }

            if ($slot->confirmed_signups_count >= $slot->capacity) {
                $skipped[] = ['date' => $dateStr, 'reason' => 'full'];

                continue;
            }

            PrayerSignup::create([
                'prayer_slot_id' => $slot->id,
                // user_id = who clicked save; person_id = whose signup this is.
                'user_id' => $user->id,
                'person_id' => $person->id,
                'status' => SignupStatus::Confirmed->value,
            ]);
            $created++;
        }

        $this->bulkReport = ['created' => $created, 'skipped' => $skipped];
        $this->showBulkModal = false;
        $this->showReportModal = true;

        unset($this->daySlots, $this->mySignups, $this->suggestions);
    }

    public function leave(int $slotId): void
    {
        $person = auth()->user()?->effectivePerson();
        if (! $person) {
            return;
        }

        PrayerSignup::where('prayer_slot_id', $slotId)
            ->where('person_id', $person->id)
            ->delete();

        unset($this->daySlots, $this->mySignups, $this->suggestions);
    }

    public function addAssigned(int $slotId): void
    {
        $userId = (int) ($this->assignChoice[$slotId] ?? 0);
        if (! $userId) {
            return;
        }

        $this->assignToSlot($slotId, $userId);
        unset($this->assignChoice[$slotId]);
    }

    public function removeSignup(int $signupId): void
    {
        $signup = PrayerSignup::with('slot')->findOrFail($signupId);
        $actor = auth()->user();

        if ($signup->user_id !== $actor->id) {
            abort_unless($actor->canManageChurch($signup->slot->church_id), 403);
        }

        $signup->delete();

        unset($this->daySlots, $this->mySignups, $this->suggestions);
    }

    private function assignToSlot(int $slotId, int $userId): void
    {
        $actor = auth()->user();
        $slot = PrayerSlot::withCount('confirmedSignups')->findOrFail($slotId);

        $isSelf = $userId === $actor->id;
        if ($isSelf) {
            abort_unless($slot->church_id === $this->churchId, 403);
            // When the actor is acting-as a minor, route the signup to that
            // Person; otherwise fall back to the actor's own Person.
            $personId = $actor->effectivePerson()?->id;
        } else {
            abort_unless($actor->canManageChurch($slot->church_id), 403);
            $assignee = User::whereKey($userId)
                ->whereHas('churches', fn ($q) => $q->where('churches.id', $slot->church_id))
                ->first();
            if (! $assignee) {
                $this->addError('slot', __('This user is not registered at this church.'));

                return;
            }
            $personId = $assignee->person_id;
        }

        if ($slot->starts_at->isPast()) {
            $this->addError('slot', __('This schedule has already started.'));

            return;
        }

        if ($slot->confirmed_signups_count >= $slot->capacity) {
            $this->addError('slot', __('This schedule is full.'));

            return;
        }

        PrayerSignup::updateOrCreate(
            ['prayer_slot_id' => $slot->id, 'person_id' => $personId],
            [
                // user_id captures who saved the row; person_id is who the
                // signup is for. They differ when a parent records on behalf
                // of a minor.
                'user_id' => $userId,
                'status' => SignupStatus::Confirmed->value,
            ],
        );

        unset($this->daySlots, $this->mySignups, $this->suggestions);
    }
};