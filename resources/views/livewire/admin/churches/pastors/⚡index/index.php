<?php

use App\Livewire\Concerns\HasSortableColumns;
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
        return ['pastor', 'role', 'start_date', 'end_date'];
    }

    protected function defaultSortBy(): string
    {
        return 'start_date';
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