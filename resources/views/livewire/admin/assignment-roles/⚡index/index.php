<?php

use App\Livewire\Concerns\HasSortableColumns;
use App\Models\AssignmentRole;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
class extends Component
{
    use HasSortableColumns;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public ?int $viewingId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    protected function sortableColumns(): array
    {
        return ['name', 'slug', 'is_active', 'assignments_count'];
    }

    protected function defaultSortBy(): string
    {
        return 'name';
    }

    #[Computed]
    public function assignmentRoles()
    {
        $q = AssignmentRole::query()
            ->withCount('assignments')
            ->orderBy($this->sortBy, $this->sortDir);

        if ($this->statusFilter === 'active') {
            $q->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $q->where('is_active', false);
        }

        if ($this->search !== '') {
            $term = '%'.addcslashes($this->search, '%_\\').'%';
            $q->where(fn ($qq) => $qq->where('name', 'like', $term)->orWhere('slug', 'like', $term));
        }

        return $q->paginate(20);
    }

    #[Computed]
    public function viewingRole(): ?AssignmentRole
    {
        return $this->viewingId ? AssignmentRole::withCount('assignments')->find($this->viewingId) : null;
    }

    public function openView(int $id): void
    {
        $this->viewingId = $id;
        $this->dispatch('modal-show', name: 'view-assignment-role');
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);
        AssignmentRole::findOrFail($id)->delete();
        unset($this->assignmentRoles);
    }
};