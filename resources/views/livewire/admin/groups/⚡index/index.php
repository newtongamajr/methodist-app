<?php

use App\Enums\GroupKind;
use App\Livewire\Concerns\HasSortableColumns;
use App\Models\Church;
use App\Models\District;
use App\Models\EcclesiasticalRegion;
use App\Models\Group;
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

    #[Url(as: 'kind')]
    public string $kindFilter = '';

    #[Url(as: 'level')]
    public string $levelFilter = '';

    #[Url(as: 'region')]
    public ?int $regionFilter = null;

    #[Url(as: 'district')]
    public ?int $districtFilter = null;

    #[Url(as: 'church')]
    public ?int $churchFilter = null;

    public function mount(?string $kind = null): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);
        if ($kind && in_array($kind, array_map(fn ($c) => $c->value, GroupKind::cases()), true)) {
            $this->kindFilter = $kind;
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingKindFilter(): void
    {
        $this->resetPage();
    }

    public function updatingLevelFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRegionFilter(): void
    {
        $this->resetPage();
        $this->districtFilter = null;
        $this->churchFilter = null;
    }

    public function updatingDistrictFilter(): void
    {
        $this->resetPage();
        $this->churchFilter = null;
    }

    public function updatingChurchFilter(): void
    {
        $this->resetPage();
    }

    protected function sortableColumns(): array
    {
        return ['name', 'kind', 'is_active'];
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
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function churches()
    {
        $q = Church::query()->where('is_active', true)->orderBy('name');
        if ($this->districtFilter) {
            $q->where('district_id', $this->districtFilter);
        } elseif ($this->regionFilter) {
            $q->where('ecclesiastical_region_id', $this->regionFilter);
        }

        return $q->get(['id', 'name']);
    }

    #[Computed]
    public function groups()
    {
        $q = Group::query()
            ->with(['region:id,code,name', 'district:id,name', 'church:id,name'])
            ->withCount('activeAssignments as members_count')
            ->orderBy($this->sortBy, $this->sortDir);

        if ($this->kindFilter !== '') {
            $q->where('kind', $this->kindFilter);
        }

        if ($this->levelFilter === 'national') {
            $q->whereNull('ecclesiastical_region_id')->whereNull('district_id')->whereNull('church_id');
        } elseif ($this->levelFilter === 'region') {
            $q->whereNotNull('ecclesiastical_region_id')->whereNull('district_id')->whereNull('church_id');
        } elseif ($this->levelFilter === 'district') {
            $q->whereNotNull('district_id')->whereNull('church_id');
        } elseif ($this->levelFilter === 'church') {
            $q->whereNotNull('church_id');
        }

        if ($this->regionFilter) {
            $q->where('ecclesiastical_region_id', $this->regionFilter);
        }
        if ($this->districtFilter) {
            $q->where('district_id', $this->districtFilter);
        }
        if ($this->churchFilter) {
            $q->where('church_id', $this->churchFilter);
        }

        if ($this->search !== '') {
            $term = '%'.addcslashes($this->search, '%_\\').'%';
            $q->where(fn ($qq) => $qq->where('name', 'like', $term)->orWhere('slug', 'like', $term));
        }

        return $q->paginate(20);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);
        Group::findOrFail($id)->delete();
        unset($this->groups);
    }
};
