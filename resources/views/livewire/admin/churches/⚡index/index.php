<?php

use App\Models\Church;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function churches()
    {
        return Church::query()
            ->with(['region'])
            ->withCount('primaryUsers')
            ->when($this->search, function ($q) {
                $term = '%'.addcslashes($this->search, '%_\\').'%';
                $q->where(fn ($qq) => $qq->where('name', 'like', $term)
                    ->orWhere('city', 'like', $term));
            })
            ->orderBy('name')
            ->paginate(20);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);
        Church::findOrFail($id)->delete();
        unset($this->churches);
    }
};