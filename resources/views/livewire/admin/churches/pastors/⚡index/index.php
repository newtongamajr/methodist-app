<?php

use App\Models\Church;
use App\Models\Pastor;
use App\Models\PastorAssignment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public Church $church;

    #[Url(as: 'f')]
    public string $filter = 'current';

    #[Url(as: 'sort')]
    public string $sortBy = 'start_date';

    #[Url(as: 'dir')]
    public string $sortDir = 'desc';

    public function mount(int $churchId): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);
        $this->church = Church::findOrFail($churchId);
    }

    public function sort(string $column): void
    {
        if (! in_array($column, ['pastor', 'role', 'start_date', 'end_date'], true)) {
            return;
        }
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = in_array($column, ['start_date', 'end_date'], true) ? 'desc' : 'asc';
        }
    }

    #[Computed]
    public function assignments()
    {
        $today = now()->toDateString();

        $q = PastorAssignment::query()
            ->with('pastor')
            ->where('church_id', $this->church->id);

        if ($this->filter === 'current') {
            $q->activeOn(now());
        } elseif ($this->filter === 'past') {
            $q->whereNotNull('end_date')->where('end_date', '<', $today);
        } elseif ($this->filter === 'future') {
            $q->whereNotNull('start_date')->where('start_date', '>', $today);
        }

        $orderColumn = match ($this->sortBy) {
            'pastor' => Pastor::query()->select('name')->whereColumn('pastors.id', 'pastor_assignments.pastor_id'),
            default => $this->sortBy,
        };
        $q->orderBy($orderColumn, $this->sortDir)->orderBy('display_order');

        return $q->get();
    }

    public function endAssignment(int $id): void
    {
        $a = PastorAssignment::where('church_id', $this->church->id)->findOrFail($id);
        $a->update(['end_date' => now()->toDateString()]);
        unset($this->assignments);
    }

    public function delete(int $id): void
    {
        $a = PastorAssignment::where('church_id', $this->church->id)->findOrFail($id);
        $a->delete();
        unset($this->assignments);
    }
};