<?php

use App\Enums\RegionKind;
use App\Models\EcclesiasticalRegion;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public ?EcclesiasticalRegion $region = null;

    public string $code = '';
    public string $name = '';
    public string $kind = 'regular';
    public int $display_order = 0;

    public function mount(?int $regionId = null): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);

        if ($regionId) {
            $this->region = EcclesiasticalRegion::findOrFail($regionId);
            $this->code = $this->region->code;
            $this->name = $this->region->name;
            $this->kind = $this->region->kind->value;
            $this->display_order = $this->region->display_order;
        } else {
            $this->display_order = (int) (EcclesiasticalRegion::max('display_order') ?? 0) + 1;
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'code' => ['required', 'string', 'max:16', Rule::unique('ecclesiastical_regions', 'code')->ignore($this->region?->id)],
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', 'in:'.implode(',', array_map(fn ($c) => $c->value, RegionKind::cases()))],
            'display_order' => ['integer', 'min:0', 'max:9999'],
        ]);

        if ($this->region) {
            $this->region->update($data);
        } else {
            $this->region = EcclesiasticalRegion::create($data);
        }

        session()->flash('status', __('Region saved.'));

        $this->redirect(route('admin.regions.index'), navigate: true);
    }
};