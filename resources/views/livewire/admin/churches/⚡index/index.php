<?php

use App\Livewire\Concerns\HasSortableColumns;
use App\Models\Church;
use App\Models\District;
use App\Models\EcclesiasticalRegion;
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

    #[Url(as: 'region')]
    public ?int $regionFilter = null;

    #[Url(as: 'district')]
    public ?int $districtFilter = null;

    public function mount(?int $region = null, ?int $district = null): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);
        if ($region !== null) {
            $this->regionFilter = $region;
        }
        if ($district !== null) {
            $this->districtFilter = $district;
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRegionFilter(): void
    {
        $this->resetPage();
        $this->districtFilter = null;
    }

    public function updatingDistrictFilter(): void
    {
        $this->resetPage();
    }

    protected function sortableColumns(): array
    {
        return ['name', 'type', 'city', 'is_active', 'members_count'];
    }

    protected function defaultSortBy(): string
    {
        return 'name';
    }

    #[Computed]
    public function regions()
    {
        return EcclesiasticalRegion::orderBy('display_order')->get(['id', 'code', 'name']);
    }

    #[Computed]
    public function districts()
    {
        if (! $this->regionFilter) {
            return collect();
        }

        return District::query()
            ->where('ecclesiastical_region_id', $this->regionFilter)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function churches()
    {
        return Church::query()
            ->with(['region', 'district'])
            ->withCount('members')
            ->when($this->regionFilter, fn ($q) => $q->where('ecclesiastical_region_id', $this->regionFilter))
            ->when($this->districtFilter, fn ($q) => $q->where('district_id', $this->districtFilter))
            ->when($this->search, function ($q) {
                $term = '%'.addcslashes($this->search, '%_\\').'%';
                $q->where(fn ($qq) => $qq->where('name', 'like', $term)
                    ->orWhere('city', 'like', $term));
            })
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(20);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);
        Church::findOrFail($id)->delete();
        unset($this->churches);
    }
};