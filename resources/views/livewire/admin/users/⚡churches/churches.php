<?php

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public User $user;

    /** Currently picked church in the "Add church" listbox. */
    public ?int $selectedChurchId = null;

    public function mount(int $userId): void
    {
        $actor = auth()->user();
        abort_unless($actor && ($actor->can('users.manage') || $actor->can('users.manage.local')), 403);

        $this->user = User::with(['churches', 'person'])->findOrFail($userId);

        if (! $this->isSuper) {
            $allowed = $actor->manageableChurchIds();
            $userChurchIds = $this->user->churches->pluck('id')->all();
            // A master may open the page for any user already attached to one
            // of their churches, OR for a brand-new user with no attachments
            // yet (the per-church attach() guard takes over from there).
            abort_unless(
                empty($userChurchIds) || count(array_intersect($allowed, $userChurchIds)) > 0,
                403,
            );
        }
    }

    #[Computed]
    public function isSuper(): bool
    {
        return auth()->user()->can('users.manage');
    }

    /**
     * Churches the actor can attach to this user, minus the ones already
     * attached. Drives the "Add church" listbox.
     */
    #[Computed]
    public function selectableChurches()
    {
        $attached = $this->user->churches->pluck('id')->all();

        return auth()->user()->manageableChurches()
            ->reject(fn ($c) => in_array($c->id, $attached, true))
            ->values()
            ->map->only(['id', 'name', 'city', 'state']);
    }

    /** All current attachments, ordered with the primary first. */
    #[Computed]
    public function attachments()
    {
        return $this->user->churches()
            ->orderByDesc('church_user.is_primary')
            ->orderBy('churches.name')
            ->get(['churches.id', 'churches.name', 'churches.city', 'churches.state']);
    }

    public function attach(): void
    {
        $churchId = $this->selectedChurchId ? (int) $this->selectedChurchId : null;
        if (! $churchId) {
            return;
        }

        if (! $this->isSuper) {
            abort_unless(in_array($churchId, auth()->user()->manageableChurchIds(), true), 403);
        }

        // First attachment becomes the primary automatically — gives the
        // user a meaningful default context immediately.
        $isFirst = $this->user->churches()->count() === 0;

        $this->user->churches()->syncWithoutDetaching([
            $churchId => ['is_primary' => $isFirst],
        ]);

        if ($isFirst && $this->user->person) {
            $this->user->person->update(['managing_church_id' => $churchId]);
        }

        $this->selectedChurchId = null;
        unset($this->attachments, $this->selectableChurches);
    }

    public function detach(int $churchId): void
    {
        if (! $this->isSuper) {
            abort_unless(in_array($churchId, auth()->user()->manageableChurchIds(), true), 403);
        }

        $wasPrimary = $this->user->churches()
            ->wherePivot('church_id', $churchId)
            ->wherePivot('is_primary', true)
            ->exists();

        $this->user->churches()->detach($churchId);

        // If we just detached the primary, promote whatever's left to keep
        // the user with a meaningful default context.
        if ($wasPrimary) {
            $next = $this->user->churches()->orderBy('churches.name')->first();
            if ($next) {
                $this->user->churches()->updateExistingPivot($next->id, ['is_primary' => true]);
                $this->user->person?->update(['managing_church_id' => $next->id]);
            } else {
                $this->user->person?->update(['managing_church_id' => null]);
            }
        }

        unset($this->attachments, $this->selectableChurches);
    }

    public function setPrimary(int $churchId): void
    {
        if (! $this->isSuper) {
            abort_unless(in_array($churchId, auth()->user()->manageableChurchIds(), true), 403);
        }

        // The ChurchUserObserver demotes other primaries on save — we just
        // mark this one and rely on the observer to make it exclusive.
        $this->user->churches()->updateExistingPivot($churchId, ['is_primary' => true]);
        $this->user->person?->update(['managing_church_id' => $churchId]);

        unset($this->attachments);
    }
};
