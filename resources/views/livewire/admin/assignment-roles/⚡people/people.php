<?php

use App\Livewire\Concerns\HasSortableColumns;
use App\Models\AssignmentRole;
use App\Models\PersonRoleAssignment;
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

    public AssignmentRole $assignmentRole;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'active';

    public function mount(int $assignmentRoleId): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);
        $this->assignmentRole = AssignmentRole::findOrFail($assignmentRoleId);
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
        return ['started_at', 'ended_at'];
    }

    protected function defaultSortBy(): string
    {
        return 'started_at';
    }

    #[Computed]
    public function assignments()
    {
        $q = PersonRoleAssignment::query()
            ->where('assignment_role_id', $this->assignmentRole->id)
            ->with([
                'person:id,name',
                'group:id,name,kind,ecclesiastical_region_id,district_id,church_id',
                'group.region:id,code,name',
                'group.district:id,name',
                'group.church:id,name',
                'function:id,name',
            ])
            ->orderBy($this->sortBy, $this->sortDir);

        if ($this->statusFilter === 'active') {
            $q->where(fn ($qq) => $qq->whereNull('ended_at')->orWhere('ended_at', '>=', now()->toDateString()));
        } elseif ($this->statusFilter === 'ended') {
            $q->whereNotNull('ended_at')->where('ended_at', '<', now()->toDateString());
        }

        if ($this->search !== '') {
            $term = '%'.addcslashes($this->search, '%_\\').'%';
            $q->where(function ($qq) use ($term) {
                $qq->whereHas('person', fn ($p) => $p->where('name', 'like', $term))
                    ->orWhereHas('group', fn ($g) => $g->where('name', 'like', $term));
            });
        }

        return $q->paginate(20);
    }
};