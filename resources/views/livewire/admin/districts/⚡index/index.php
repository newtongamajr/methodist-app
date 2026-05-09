<?php

use App\Livewire\Concerns\HasSortableColumns;
use App\Models\District;
use App\Models\EcclesiasticalRegion;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    use HasSortableColumns;

    #[Url(as: 'region')]
    public ?int $regionFilter = null;

    public function mount(?int $region = null): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);
        if ($region !== null) {
            $this->regionFilter = $region;
        }
    }

    protected function sortableColumns(): array
    {
        return ['display_order', 'name', 'churches_count', 'is_active'];
    }

    protected function defaultSortBy(): string
    {
        return 'display_order';
    }

    #[Computed]
    public function regions()
    {
        return EcclesiasticalRegion::orderBy('display_order')->get(['id', 'code', 'name']);
    }

    #[Computed]
    public function districts()
    {
        return District::query()
            ->with('region:id,code,name')
            ->withCount('churches')
            ->when($this->regionFilter, fn ($q) => $q->where('ecclesiastical_region_id', $this->regionFilter))
            ->orderBy($this->sortBy, $this->sortDir)
            ->orderBy('name')
            ->get();
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);
        $district = District::withCount('churches')->findOrFail($id);
        if ($district->churches_count > 0) {
            $this->addError('district', __('Cannot delete a district that still has churches.'));

            return;
        }
        $district->delete();
        unset($this->districts);
    }
};
