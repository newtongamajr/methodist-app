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

        // Liked-by is per-Person now, so an act-as parent toggling on behalf
        // of their child sees the heart filled iff the CHILD has liked.
        $person = auth()->user()?->effectivePerson();
        if (! $person) {
            return false;
        }

        return $this->post->likes()->where('person_id', $person->id)->exists();
    }

    #[Computed]
    public function likesCount(): int
    {
        return $this->post->likes()->count();
    }

    #[Computed]
    public function approvedComments(): Collection
    {
        // Eager-load both the recording user (`author`) and the participant
        // (`person`) so the Blade can render "Parent in the name of Child"
        // when they differ — and just the participant otherwise.
        return $this->post->approvedComments()
            ->with(['author:id,name', 'person:id,name'])
            ->latest()
            ->get();
    }

    public function toggleLike(): void
    {
        $user = auth()->user();
        if (! $user) {
            $this->redirectRoute('login', navigate: true);

            return;
        }
        $person = $user->effectivePerson();
        if (! $person) {
            return;
        }

        $like = PostLike::where('post_id', $this->post->id)
            ->where('person_id', $person->id)
            ->first();

        if ($like) {
            $like->delete();
        } else {
            PostLike::create([
                'post_id' => $this->post->id,
                'user_id' => $user->id,
                'person_id' => $person->id,
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
            // person_id captures whose voice the comment is in. When acting-
            // as a child, that's the child; otherwise it's the user's own
            // Person and the UI just shows one name.
            'person_id' => $user->effectivePerson()?->id,
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