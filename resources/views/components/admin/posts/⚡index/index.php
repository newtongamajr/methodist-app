<?php

use App\Enums\PostScope;
use App\Enums\PostStatus;
use App\Models\Post;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $scopeFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingScopeFilter(): void
    {
        $this->resetPage();
    }

    public function getPostsProperty()
    {
        $user = auth()->user();

        $q = Post::query()->with(['author', 'church'])->latest('updated_at');

        if (! $user->can('posts.update.any')) {
            $manageable = $user->manageableChurchIds();
            $q->where(function ($qq) use ($user, $manageable) {
                $qq->where('author_id', $user->id);

                if ($user->can('posts.create.local') && $manageable) {
                    $qq->orWhere(fn ($q3) => $q3
                        ->where('scope', PostScope::Local)
                        ->whereIn('church_id', $manageable));
                }
            });
        }

        if ($this->search !== '') {
            $q->where('title', 'like', '%'.$this->search.'%');
        }
        if ($this->statusFilter !== '') {
            $q->where('status', $this->statusFilter);
        }
        if ($this->scopeFilter !== '') {
            $q->where('scope', $this->scopeFilter);
        }

        return $q->paginate(15);
    }

    public function delete(int $id): void
    {
        $post = Post::findOrFail($id);
        $this->authorize('delete', $post);
        $post->delete();
        $this->dispatch('post-deleted');
    }

    public function render()
    {
        return view('components.admin.posts.⚡index.index', [
            'statuses' => PostStatus::cases(),
            'scopes' => PostScope::cases(),
        ]);
    }
};