<?php

use App\Models\Post;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
class extends Component
{
    use WithPagination;

    public function getPostsProperty()
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