<?php

use App\Enums\CommentStatus;
use App\Models\PostComment;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public function approve(int $id): void
    {
        $comment = PostComment::with('post')->findOrFail($id);
        $this->authorize('moderate', $comment);
        $comment->approve(auth()->user());
        $this->bustBuckets();
    }

    public function reject(int $id): void
    {
        $comment = PostComment::with('post')->findOrFail($id);
        $this->authorize('moderate', $comment);
        $comment->reject(auth()->user());
        $this->bustBuckets();
    }

    #[Computed]
    public function pendingComments(): Collection
    {
        return $this->commentsByStatus(CommentStatus::Pending);
    }

    #[Computed]
    public function approvedComments(): Collection
    {
        return $this->commentsByStatus(CommentStatus::Approved);
    }

    #[Computed]
    public function rejectedComments(): Collection
    {
        return $this->commentsByStatus(CommentStatus::Rejected);
    }

    protected function commentsByStatus(CommentStatus $status): Collection
    {
        $user = auth()->user();

        $q = PostComment::query()
            ->with(['author', 'post.church'])
            ->where('status', $status)
            ->latest()
            ->limit(50);

        if (! $user->can('posts.update.any')) {
            $manageable = $user->manageableChurchIds();
            $q->whereHas('post', fn ($qp) => $qp
                ->where(function ($qq) use ($manageable) {
                    $qq->whereIn('church_id', $manageable)
                        ->orWhereNull('church_id');
                }));
        }

        return $q->get();
    }

    protected function bustBuckets(): void
    {
        unset($this->pendingComments, $this->approvedComments, $this->rejectedComments);
    }
};
