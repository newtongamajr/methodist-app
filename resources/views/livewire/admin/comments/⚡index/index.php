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
            ->with(['author', 'post.scopes.church:id,name'])
            ->where('status', $status)
            ->latest()
            ->limit(50);

        if (! $user->can('posts.update.any')) {
            // Show comments on posts that target a scope this admin
            // manages, plus all national posts. Mirrors the post-list
            // visibility on the admin Posts index.
            $regions = $user->manageableRegionIds();
            $districts = $user->manageableDistrictIds();
            $churches = $user->manageableChurchIds();

            $q->whereHas('post.scopes', function ($qs) use ($regions, $districts, $churches) {
                $qs->where('national_post', true);
                if ($regions) {
                    $qs->orWhereIn('region_id', $regions);
                }
                if ($districts) {
                    $qs->orWhereIn('district_id', $districts);
                }
                if ($churches) {
                    $qs->orWhereIn('church_id', $churches);
                }
            });
        }

        return $q->get();
    }

    protected function bustBuckets(): void
    {
        unset($this->pendingComments, $this->approvedComments, $this->rejectedComments);
    }
};
