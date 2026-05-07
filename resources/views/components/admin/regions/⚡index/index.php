<?php

use App\Models\EcclesiasticalRegion;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);
    }

    #[Computed]
    public function regions()
    {
        return EcclesiasticalRegion::query()
            ->withCount('churches')
            ->orderBy('display_order')
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