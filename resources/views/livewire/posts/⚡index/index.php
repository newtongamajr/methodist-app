<?php

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
class extends Component
{
    use WithPagination;

    #[Computed]
    public function posts(): LengthAwarePaginator
    {
        return Post::query()
            ->published()
            ->visibleTo(auth()->user())
            ->with(['author', 'church', 'media'])
            ->withCount(['likes', 'approvedComments as comments_count'])
            ->latest('published_at')
            ->paginate(10);
    }
};