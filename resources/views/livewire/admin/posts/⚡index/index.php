<?php

use App\Enums\PostScope;
use App\Enums\PostStatus;
use App\Livewire\Concerns\HasSortableColumns;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
class extends Component
{
    use HasSortableColumns;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'scope')]
    public string $scopeFilter = '';

    #[Url(as: 'church')]
    public ?int $churchFilter = null;

    #[Url(as: 'author')]
    public ?int $authorFilter = null;

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

    public function updatingChurchFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAuthorFilter(): void
    {
        $this->resetPage();
    }

    protected function sortableColumns(): array
    {
        return ['title', 'author', 'published_at', 'updated_at'];
    }

    protected function defaultSortBy(): string
    {
        return 'updated_at';
    }

    #[Computed]
    public function availableChurches(): Collection
    {
        return auth()->user()
            ->manageableChurches()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
            ->values();
    }

    #[Computed]
    public function availableAuthors(): Collection
    {
        return User::query()
            ->whereIn('id', Post::query()->select('author_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function posts(): LengthAwarePaginator
    {
        $user = auth()->user();

        $q = Post::query()
            ->select(['id', 'title', 'slug', 'scope', 'status', 'author_id', 'church_id', 'updated_at', 'published_at', 'created_at', 'deleted_at'])
            ->with(['author:id,name', 'church:id,name']);

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
            $q->where('title', 'like', '%'.addcslashes($this->search, '%_\\').'%');
        }
        if ($this->statusFilter !== '') {
            $q->where('status', $this->statusFilter);
        }
        if ($this->scopeFilter !== '') {
            $q->where('scope', $this->scopeFilter);
        }
        if ($this->churchFilter) {
            $q->where('church_id', $this->churchFilter);
        }
        if ($this->authorFilter) {
            $q->where('author_id', $this->authorFilter);
        }

        $orderColumn = match ($this->sortBy) {
            'title' => 'title',
            'published_at' => 'published_at',
            'author' => User::query()->select('name')->whereColumn('users.id', 'posts.author_id'),
            default => 'updated_at',
        };
        $q->orderBy($orderColumn, $this->sortDir);

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
        return view('livewire.admin.posts.⚡index.index', [
            'statuses' => PostStatus::cases(),
            'scopes' => PostScope::cases(),
        ]);
    }
};
