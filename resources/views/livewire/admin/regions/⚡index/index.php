<?php

use App\Models\EcclesiasticalRegion;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    #[Url(as: 'sort')]
    public string $sortBy = 'display_order';

    #[Url(as: 'dir')]
    public string $sortDir = 'asc';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);
    }

    public function sort(string $column): void
    {
        if (! in_array($column, ['display_order', 'code', 'name', 'kind', 'churches_count'], true)) {
            return;
        }
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = $column === 'churches_count' ? 'desc' : 'asc';
        }
    }

    #[Computed]
    public function regions()
    {
        return EcclesiasticalRegion::query()
            ->withCount('churches')
            ->orderBy($this->sortBy, $this->sortDir)
            ->orderBy('name')
            ->get();
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);
        $region = EcclesiasticalRegion::withCount('churches')->findOrFail($id);
        if ($region->churches_count > 0) {
            $this->addError('region', __('Cannot delete a region that still has churches.'));
            return;
        }
        $region->delete();
        unset($this->regions);
    }
};