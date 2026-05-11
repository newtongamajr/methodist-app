<?php

use App\Enums\CommentStatus;
use App\Models\PostComment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    #[Url(as: 'status')]
    public string $statusFilter = 'pending';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function comments(): LengthAwarePaginator
    {
        $user = auth()->user();

        $q = PostComment::query()
            ->with(['author', 'post.church'])
            ->latest();

        if ($this->statusFilter !== '') {
            $q->where('status', $this->statusFilter);
        }

        if (! $user->can('posts.update.any')) {
            $manageable = $user->manageableChurchIds();
            $q->whereHas('post', fn ($qp) => $qp
                ->where(function ($qq) use ($manageable) {
                    $qq->whereIn('church_id', $manageable)
                       ->orWhereNull('church_id');
                }));
        }

        return $q->paginate(20);
    }

    public function approve(int $id): void
    {
        $comment = PostComment::with('post')->findOrFail($id);
        $this->authorize('moderate', $comment);
        $comment->approve(auth()->user());
    }

    public function reject(int $id): void
    {
        $comment = PostComment::with('post')->findOrFail($id);
        $this->authorize('moderate', $comment);
        $comment->reject(auth()->user());
    }

    public function render()
    {
        return view('livewire.admin.comments.⚡index.index', [
            'statuses' => CommentStatus::cases(),
        ]);
    }
};