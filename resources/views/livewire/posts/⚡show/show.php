<?php

use App\Enums\CommentStatus;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public Post $post;

    public string $newComment = '';

    public function mount(string $slug): void
    {
        $this->post = Post::where('slug', $slug)
            ->with(['media', 'embeds'])
            ->published()
            ->firstOrFail();

        if (! Gate::allows('view', $this->post)) {
            abort(403);
        }
    }

    #[Computed]
    public function liked(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return $this->post->likes()->where('user_id', auth()->id())->exists();
    }

    #[Computed]
    public function likesCount(): int
    {
        return $this->post->likes()->count();
    }

    #[Computed]
    public function approvedComments(): Collection
    {
        return $this->post->approvedComments()->with('author')->latest()->get();
    }

    public function toggleLike(): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login', navigate: true);
            return;
        }

        $like = PostLike::where('post_id', $this->post->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($like) {
            $like->delete();
        } else {
            PostLike::create([
                'post_id' => $this->post->id,
                'user_id' => auth()->id(),
            ]);
        }

        unset($this->liked, $this->likesCount);
    }

    public function submitComment(): void
    {
        $user = auth()->user();
        if (! $user) {
            $this->redirectRoute('login', navigate: true);
            return;
        }

        $this->validate([
            'newComment' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        // Admins (global or local managers) bypass moderation — their comments
        // post immediately. Everyone else lands in the pending queue.
        $isAdmin = $user->hasRole('national_admin') || $user->hasRole('local_admin');

        PostComment::create([
            'post_id' => $this->post->id,
            'user_id' => $user->id,
            'body' => $this->newComment,
            'status' => $isAdmin ? CommentStatus::Approved : CommentStatus::Pending,
            'approved_by' => $isAdmin ? $user->id : null,
            'approved_at' => $isAdmin ? now() : null,
        ]);

        $this->newComment = '';

        unset($this->approvedComments);

        session()->flash(
            'comment-status',
            $isAdmin
                ? __('Your comment was published.')
                : __('Your comment was submitted and is awaiting approval.'),
        );
    }
};