<?php

use App\Models\Church;
use App\Models\User;
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

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'church')]
    public ?int $churchFilter = null;

    #[Url(as: 'type')]
    public string $memberTypeFilter = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'name';

    #[Url(as: 'dir')]
    public string $sortDir = 'asc';

    public function mount(?int $church = null): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->can('users.manage') || $user->can('users.manage.local')), 403);

        $this->churchFilter = $church;
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingChurchFilter(): void { $this->resetPage(); }
    public function updatingMemberTypeFilter(): void { $this->resetPage(); }

    public function sort(string $column): void
    {
        if (! in_array($column, ['name', 'email', 'member_type'], true)) {
            return;
        }
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'asc';
        }
        $this->resetPage();
    }

    #[Computed]
    public function isSuper(): bool
    {
        return auth()->user()->can('users.manage');
    }

    #[Computed]
    public function churches()
    {
        return $this->isSuper
            ? Church::orderBy('name')->get(['id', 'name'])
            : auth()->user()->manageableChurches()->map->only(['id', 'name']);
    }

    #[Computed]
    public function members()
    {
        $q = User::query()
            ->with(['primaryChurch', 'churches'])
            ->orderBy($this->sortBy, $this->sortDir)
            // Members = anyone WITHOUT an admin role.
            ->whereDoesntHave('roles', fn ($qq) => $qq->whereIn('name', ['global_manager', 'local_manager']));

        if (! $this->isSuper) {
            $allowedIds = auth()->user()->manageableChurchIds();
            $q->whereHas('churches', fn ($qq) => $qq->whereIn('churches.id', $allowedIds));
        }

        if ($this->churchFilter) {
            $q->whereHas('churches', fn ($qq) => $qq->where('churches.id', $this->churchFilter));
        }

        if ($this->memberTypeFilter !== '') {
            $q->where('member_type', $this->memberTypeFilter);
        }

        if ($this->search) {
            $term = '%'.addcslashes($this->search, '%_\\').'%';
            $q->where(fn ($qq) => $qq->where('name', 'like', $term)
                ->orWhere('email', 'like', $term));
        }

        return $q->paginate(20);
    }

    public function delete(int $id): void
    {
        $actor = auth()->user();
        $target = User::with('churches')->findOrFail($id);

        if (! $this->isSuper) {
            $allowed = $actor->manageableChurchIds();
            abort_unless(
                $target->churches->pluck('id')->intersect($allowed)->isNotEmpty(),
                403
            );
        }
        abort_if($target->id === $actor->id, 422, __('You cannot delete your own account here.'));

        $target->delete();
        unset($this->members);
    }
};