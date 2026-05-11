<?php

use App\Enums\SignupStatus;
use App\Models\PrayerCampaign;
use App\Models\PrayerSignup;
use App\Models\PrayerSlot;
use App\Models\User;
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

    /** Per-slot picker state for admins: prayer_slot_id => candidate user_id */
    public array $assignChoice = [];

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
            ->with(['confirmedSignups.user:id,name'])
            ->orderBy('starts_at')
            ->get();
    }

    #[Computed]
    public function mySignups(): array
    {
        return PrayerSignup::query()
            ->where('user_id', auth()->id())
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

        return PrayerSlot::query()
            ->where('church_id', $this->churchId)
            ->where('prayer_campaign_id', $this->campaignId)
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

    public function leave(int $slotId): void
    {
        PrayerSignup::where('prayer_slot_id', $slotId)
            ->where('user_id', auth()->id())
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
        } else {
            abort_unless($actor->canManageChurch($slot->church_id), 403);
            $isMember = User::whereKey($userId)
                ->whereHas('churches', fn ($q) => $q->where('churches.id', $slot->church_id))
                ->exists();
            if (! $isMember) {
                $this->addError('slot', __('This user is not registered at this church.'));
                return;
            }
        }

        if ($slot->starts_at->isPast()) {
            $this->addError('slot', __('This slot has already started.'));
            return;
        }

        if ($slot->confirmed_signups_count >= $slot->capacity) {
            $this->addError('slot', __('This slot is full.'));
            return;
        }

        PrayerSignup::updateOrCreate(
            ['prayer_slot_id' => $slot->id, 'user_id' => $userId],
            ['status' => SignupStatus::Confirmed->value],
        );

        unset($this->daySlots, $this->mySignups, $this->suggestions);
    }
};