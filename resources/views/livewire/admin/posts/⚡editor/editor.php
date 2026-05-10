<?php

use App\Enums\EmbedProvider;
use App\Enums\PostStatus;
use App\Livewire\Forms\PostForm;
use App\Models\Church;
use App\Models\District;
use App\Models\EcclesiasticalRegion;
use App\Models\Post;
use App\Models\PostEmbed;
use App\Models\PostScope as PostScopeRow;
use App\Services\EmbedLookupService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts.app')]
class extends Component
{
    use WithFileUploads;

    public PostForm $form;

    public $newCover = null;

    public array $newImages = [];

    public array $newVideos = [];

    public array $newAudios = [];

    public array $newDocuments = [];

    public string $newEmbedUrl = '';

    public function mount(?int $postId = null): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->can('posts.create.shared') || $user->can('posts.create.local')), 403);

        if ($postId) {
            $post = Post::with(['media', 'embeds', 'scopes'])->findOrFail($postId);
            $this->authorize('update', $post);
            $this->form->setPost($post);
        } else {
            $this->form->status = PostStatus::Draft->value;

            // Every audience picker starts off — the author explicitly opts
            // in to national / region / district / church scopes per post,
            // so a national admin doesn't accidentally publish nationally
            // when they meant a single church. Local-only admins still get
            // their primary church pre-checked since that's the only scope
            // they can publish to anyway.
            if (! $user->hasAnyRole(['national_admin', 'regional_admin', 'district_admin']) && $user->hasRole('local_admin')) {
                $primary = $user->churches()->wherePivot('is_primary', true)->first();
                if ($primary) {
                    $this->form->church_ids = [$primary->id];
                }
            }
        }
    }

    /** Whether the actor is allowed to flip the "national" checkbox. */
    public function canPublishNational(): bool
    {
        return auth()->user()?->hasRole('national_admin') === true;
    }

    /** Regions the actor is allowed to publish to. */
    #[Computed]
    public function availableRegions(): Collection
    {
        $ids = auth()->user()->manageableRegionIds();
        if (empty($ids)) {
            return collect();
        }

        return EcclesiasticalRegion::query()
            ->whereIn('id', $ids)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }

    /** Districts the actor is allowed to publish to. */
    #[Computed]
    public function availableDistricts(): Collection
    {
        $ids = auth()->user()->manageableDistrictIds();
        if (empty($ids)) {
            return collect();
        }

        return District::query()
            ->whereIn('id', $ids)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name', 'ecclesiastical_region_id']);
    }

    /** Churches the actor is allowed to publish to. */
    #[Computed]
    public function availableChurches(): Collection
    {
        return auth()->user()
            ->manageableChurches()
            ->map(fn ($c) => (object) [
                'id' => $c->id,
                'name' => $c->name,
                'city' => $c->city,
                'state' => $c->state,
                'district_id' => $c->district_id,
                'ecclesiastical_region_id' => $c->ecclesiastical_region_id,
            ]);
    }

    public function save(): void
    {
        $user = auth()->user();

        $data = $this->form->validate();

        $this->validate([
            'newCover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'newImages.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
            'newVideos.*' => ['file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime', 'max:102400'],
            'newAudios.*' => ['file', 'mimetypes:audio/mp4,audio/mpeg,audio/ogg,audio/wav,audio/webm', 'max:51200'],
            'newDocuments.*' => ['file', 'mimes:pdf', 'max:20480'],
        ]);

        // ─── Scope authorization ────────────────────────────────────────
        // Reject the save when any picked scope falls outside what this
        // user is allowed to publish to. National flag is only for
        // national_admin; every region/district/church id has to live in
        // the actor's manageable set.
        if ($data['national_post'] && ! $this->canPublishNational()) {
            abort(403);
        }

        $allowedRegions = array_map('intval', $user->manageableRegionIds());
        $allowedDistricts = array_map('intval', $user->manageableDistrictIds());
        $allowedChurches = array_map('intval', $user->manageableChurchIds());

        // Cast picked IDs to int — Livewire deserializes pillbox values as
        // strings, and a strict in_array against an int-keyed allow-list
        // would otherwise reject a national admin's own regions.
        $data['region_ids'] = array_map('intval', $data['region_ids']);
        $data['district_ids'] = array_map('intval', $data['district_ids']);
        $data['church_ids'] = array_map('intval', $data['church_ids']);

        foreach ($data['region_ids'] as $rid) {
            abort_unless(in_array($rid, $allowedRegions, true), 403);
        }
        foreach ($data['district_ids'] as $did) {
            abort_unless(in_array($did, $allowedDistricts, true), 403);
        }
        foreach ($data['church_ids'] as $cid) {
            abort_unless(in_array($cid, $allowedChurches, true), 403);
        }

        // Need at least one audience picked, otherwise the post has no readers.
        if (! $data['national_post']
            && empty($data['region_ids'])
            && empty($data['district_ids'])
            && empty($data['church_ids'])) {
            $this->addError('form.scopes', __('Pick at least one audience for this post.'));

            return;
        }

        if ($data['status'] === PostStatus::Published->value && empty($data['published_at'])) {
            $data['published_at'] = now();
        } elseif ($data['status'] !== PostStatus::Published->value) {
            $data['published_at'] = null;
        }

        // ─── Persist the post + its scope rows ──────────────────────────
        $postPayload = collect($data)->only([
            'title', 'excerpt', 'body', 'status', 'published_at',
        ])->all();

        if ($this->form->post) {
            $this->authorize('update', $this->form->post);
            $this->form->post->update($postPayload);
        } else {
            $postPayload['author_id'] = $user->id;
            $this->form->post = Post::create($postPayload);
        }

        $this->syncScopes($data);

        $this->persistPendingMedia();

        session()->flash('status', __('Post saved.'));

        $this->redirect(route('admin.posts.edit', $this->form->post), navigate: true);
    }

    /**
     * Replace the post's scope rows with the freshly-picked set. Each
     * shape (national / region / district / church) maps to its own row;
     * district rows derive their region from the District model;
     * church rows derive both. Idempotent — wipes and rewrites.
     */
    private function syncScopes(array $data): void
    {
        $post = $this->form->post;

        $rows = [];

        if ($data['national_post']) {
            $rows[] = ['national_post' => true];
        }

        foreach ($data['region_ids'] as $rid) {
            $rows[] = ['region_id' => $rid];
        }

        $districts = District::query()->whereIn('id', $data['district_ids'])->get(['id', 'ecclesiastical_region_id']);
        foreach ($districts as $d) {
            $rows[] = [
                'region_id' => $d->ecclesiastical_region_id,
                'district_id' => $d->id,
            ];
        }

        $churches = Church::query()->whereIn('id', $data['church_ids'])->get(['id', 'district_id', 'ecclesiastical_region_id']);
        foreach ($churches as $c) {
            $rows[] = [
                'region_id' => $c->ecclesiastical_region_id,
                'district_id' => $c->district_id,
                'church_id' => $c->id,
            ];
        }

        $post->scopes()->delete();
        foreach ($rows as $row) {
            PostScopeRow::create(['post_id' => $post->id] + $row);
        }
    }

    protected function persistPendingMedia(): void
    {
        if ($this->newCover) {
            $this->form->post->clearMediaCollection('cover');
            $this->form->post->addMedia($this->newCover->getRealPath())
                ->usingFileName('cover.'.$this->newCover->getClientOriginalExtension())
                ->toMediaCollection('cover');
        }

        $collections = [
            'images' => 'newImages',
            'videos' => 'newVideos',
            'audios' => 'newAudios',
            'documents' => 'newDocuments',
        ];

        foreach ($collections as $collection => $property) {
            foreach ($this->{$property} as $file) {
                if (! $file) {
                    continue;
                }
                $this->form->post->addMedia($file->getRealPath())
                    ->usingFileName($file->getClientOriginalName())
                    ->toMediaCollection($collection);
            }
        }

        $this->newCover = null;
        $this->newImages = [];
        $this->newVideos = [];
        $this->newAudios = [];
        $this->newDocuments = [];
    }

    public function removeMedia(int $mediaId): void
    {
        if (! $this->form->post) {
            return;
        }
        $this->authorize('update', $this->form->post);

        $media = $this->form->post->media()->whereKey($mediaId)->first();
        $media?->delete();

        $this->form->post->load('media');
    }

    public function removeCover(): void
    {
        if (! $this->form->post) {
            return;
        }
        $this->authorize('update', $this->form->post);
        $this->form->post->clearMediaCollection('cover');
        $this->form->post->load('media');
    }

    public function addEmbed(): void
    {
        if (! $this->form->post) {
            session()->flash('embed-status', __('Save the post first to attach embeds.'));

            return;
        }

        $this->authorize('update', $this->form->post);

        $this->validate(['newEmbedUrl' => ['required', 'url', 'max:2048']]);

        $url = trim($this->newEmbedUrl);
        $provider = EmbedProvider::detect($url);
        $lookup = app(EmbedLookupService::class)->lookup($url);

        $nextOrder = ($this->form->post->embeds()->max('display_order') ?? -1) + 1;

        PostEmbed::create([
            'post_id' => $this->form->post->id,
            'provider' => $provider,
            'url' => $url,
            'title' => $lookup['title'],
            'thumbnail_url' => $lookup['thumbnail_url'],
            'display_order' => $nextOrder,
        ]);

        $this->newEmbedUrl = '';
        $this->form->post->load('embeds');
    }

    public function removeEmbed(int $embedId): void
    {
        if (! $this->form->post) {
            return;
        }
        $this->authorize('update', $this->form->post);
        $this->form->post->embeds()->whereKey($embedId)->delete();
        $this->form->post->load('embeds');
    }

    public function with(): array
    {
        return [
            'coverUrl' => $this->form->post?->coverUrl('card'),
            'images' => $this->form->post?->getMedia('images') ?? collect(),
            'videos' => $this->form->post?->getMedia('videos') ?? collect(),
            'audios' => $this->form->post?->getMedia('audios') ?? collect(),
            'documents' => $this->form->post?->getMedia('documents') ?? collect(),
            'embeds' => $this->form->post?->embeds ?? collect(),
        ];
    }
};
