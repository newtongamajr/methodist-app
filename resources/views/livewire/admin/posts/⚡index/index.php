<?php

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

    /** Bucket filter on the audience shape: national / regional / district / local. */
    #[Url(as: 'audience')]
    public string $audienceFilter = '';

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

    public function updatingAudienceFilter(): void
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
            ->select(['id', 'title', 'slug', 'status', 'author_id', 'updated_at', 'published_at', 'created_at', 'deleted_at'])
            ->with([
                'author:id,name',
                'scopes.region:id,name',
                'scopes.district:id,name',
                'scopes.church:id,name',
                // Eager-load only the cover collection so the per-row
                // thumbnail render doesn't trigger N+1 queries against the
                // media table.
                'media' => fn ($q) => $q->where('collection_name', 'cover'),
            ]);

        // Authors always see their own posts. Admins additionally see any
        // post that targets a scope they manage.
        if (! $user->can('posts.update.any')) {
            $regions = $user->manageableRegionIds();
            $districts = $user->manageableDistrictIds();
            $churches = $user->manageableChurchIds();

            $q->where(function ($qq) use ($user, $regions, $districts, $churches) {
                $qq->where('author_id', $user->id);

                if ($regions || $districts || $churches) {
                    $qq->orWhereHas('scopes', function ($q3) use ($regions, $districts, $churches) {
                        $q3->where(function ($q4) use ($regions, $districts, $churches) {
                            if ($regions) {
                                $q4->orWhereIn('region_id', $regions);
                            }
                            if ($districts) {
                                $q4->orWhereIn('district_id', $districts);
                            }
                            if ($churches) {
                                $q4->orWhereIn('church_id', $churches);
                            }
                        });
                    });
                }
            });
        }

        if ($this->search !== '') {
            $q->where('title', 'like', '%'.addcslashes($this->search, '%_\\').'%');
        }
        if ($this->statusFilter !== '') {
            $q->where('status', $this->statusFilter);
        }
        if ($this->audienceFilter !== '') {
            $q->whereHas('scopes', function ($q3) {
                match ($this->audienceFilter) {
                    'national' => $q3->where('national_post', true),
                    'regional' => $q3->whereNotNull('region_id')->whereNull('district_id')->whereNull('church_id'),
                    'district' => $q3->whereNotNull('district_id')->whereNull('church_id'),
                    'local' => $q3->whereNotNull('church_id'),
                    default => null,
                };
            });
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
            // Audience buckets shown in the filter dropdown.
            'audiences' => [
                'national' => __('National'),
                'regional' => __('Regional'),
                'district' => __('District'),
                'local' => __('Local'),
            ],
        ]);
    }
};
