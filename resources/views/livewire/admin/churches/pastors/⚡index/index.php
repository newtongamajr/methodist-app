<?php

use App\Livewire\Concerns\HasSortableColumns;
use App\Models\Church;
use App\Models\Person;
use App\Models\PersonRoleAssignment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    use HasSortableColumns;

    public Church $church;

    #[Url(as: 'f')]
    public string $filter = 'current';

    public function mount(int $churchId): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);
        $this->church = Church::findOrFail($churchId);
    }

    protected function sortableColumns(): array
    {
        return ['person', 'function', 'started_at', 'ended_at'];
    }

    protected function defaultSortBy(): string
    {
        return 'started_at';
    }

    #[Computed]
    public function assignments()
    {
        $today = now()->toDateString();

        $q = PersonRoleAssignment::query()
            ->with(['person', 'function'])
            ->where('church_id', $this->church->id)
            ->whereHas('function', fn ($qq) => $qq->whereJsonContains('applies_to', 'pastor'));

        if ($this->filter === 'current') {
            $q->where(fn ($qq) => $qq->whereNull('started_at')->orWhere('started_at', '<=', $today))
                ->where(fn ($qq) => $qq->whereNull('ended_at')->orWhere('ended_at', '>=', $today));
        } elseif ($this->filter === 'past') {
            $q->whereNotNull('ended_at')->where('ended_at', '<', $today);
        } elseif ($this->filter === 'future') {
            $q->whereNotNull('started_at')->where('started_at', '>', $today);
        }

        $orderColumn = match ($this->sortBy) {
            'person' => Person::query()->select('name')->whereColumn('persons.id', 'person_role_assignments.person_id'),
            'function' => \App\Models\FunctionRole::query()->select('name')->whereColumn('functions.id', 'person_role_assignments.function_id'),
            default => $this->sortBy,
        };
        $q->orderBy($orderColumn, $this->sortDir);

        return $q->get();
    }

    public function endAssignment(int $id): void
    {
        $a = PersonRoleAssignment::where('church_id', $this->church->id)->findOrFail($id);
        $a->update(['ended_at' => now()->toDateString()]);
        unset($this->assignments);
    }

    public function delete(int $id): void
    {
        $a = PersonRoleAssignment::where('church_id', $this->church->id)->findOrFail($id);
        $a->delete();
        unset($this->assignments);
    }
};
