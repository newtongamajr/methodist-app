<?php

use App\Enums\EmbedProvider;
use App\Enums\PostScope;
use App\Enums\PostStatus;
use App\Models\Church;
use App\Models\Post;
use App\Models\PostEmbed;
use App\Services\EmbedLookupService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts.app')]
class extends Component
{
    use WithFileUploads;

    public ?Post $post = null;

    public string $title = '';

    public string $excerpt = '';

    public string $body = '';

    public string $scope = '';

    public string $status = '';

    public ?int $church_id = null;

    public ?string $published_at = null;

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
            $this->post = Post::with(['media', 'embeds'])->findOrFail($postId);
            $this->authorize('update', $this->post);
            $this->title = $this->post->title;
            $this->excerpt = $this->post->excerpt ?? '';
            $this->body = $this->post->body;
            $this->scope = $this->post->scope->value;
            $this->status = $this->post->status->value;
            $this->church_id = $this->post->church_id;
            $this->published_at = $this->post->published_at?->format('Y-m-d\TH:i');
        } else {
            $this->scope = $user->can('posts.create.shared') ? PostScope::Shared->value : PostScope::Local->value;
            $this->status = PostStatus::Draft->value;
            $this->church_id = $user->currentChurchId();
        }
    }

    public function getChurchesProperty()
    {
        $user = auth()->user();
        if ($user->can('posts.create.shared')) {
            return Church::orderBy('name')->get(['id', 'name', 'city', 'state']);
        }

        return Church::query()
            ->whereIn('id', $user->manageableChurchIds())
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'state']);
    }

    public function save(): void
    {
        $user = auth()->user();

        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:5000'],
            'body' => ['required', 'string'],
            'scope' => ['required', 'string', 'in:'.implode(',', PostScope::values())],
            'status' => ['required', 'string', 'in:'.implode(',', PostStatus::values())],
            'church_id' => ['nullable', 'integer', 'exists:churches,id'],
            'published_at' => ['nullable', 'date'],
            'newCover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'newImages.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
            'newVideos.*' => ['file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime', 'max:102400'],
            'newAudios.*' => ['file', 'mimetypes:audio/mpeg,audio/mp4,audio/ogg,audio/wav,audio/webm', 'max:51200'],
            'newDocuments.*' => ['file', 'mimes:pdf', 'max:20480'],
        ]);

        if ($data['scope'] === PostScope::Shared->value) {
            abort_unless($user->can('posts.create.shared'), 403);
            $data['church_id'] = null;
        } else {
            abort_unless($user->can('posts.create.local'), 403);
            if (! $data['church_id']) {
                $data['church_id'] = $user->currentChurchId();
            }
            if (! $user->can('posts.update.any')) {
                abort_unless($user->canManageChurch((int) $data['church_id']), 403);
            }
        }

        if ($data['status'] === PostStatus::Published->value && empty($data['published_at'])) {
            $data['published_at'] = now();
        } elseif ($data['status'] !== PostStatus::Published->value) {
            $data['published_at'] = null;
        }

        unset($data['newCover'], $data['newImages'], $data['newVideos'], $data['newAudios'], $data['newDocuments']);

        if ($this->post) {
            $this->authorize('update', $this->post);
            $this->post->update($data);
        } else {
            $data['author_id'] = $user->id;
            $this->post = Post::create($data);
        }

        $this->persistPendingMedia();

        session()->flash('status', __('Post saved.'));

        $this->redirect(route('admin.posts.edit', $this->post), navigate: true);
    }

    protected function persistPendingMedia(): void
    {
        if ($this->newCover) {
            $this->post->clearMediaCollection('cover');
            $this->post->addMedia($this->newCover->getRealPath())
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
                $this->post->addMedia($file->getRealPath())
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
        if (! $this->post) {
            return;
        }
        $this->authorize('update', $this->post);

        $media = $this->post->media()->whereKey($mediaId)->first();
        $media?->delete();

        $this->post->load('media');
    }

    public function removeCover(): void
    {
        if (! $this->post) {
            return;
        }
        $this->authorize('update', $this->post);
        $this->post->clearMediaCollection('cover');
        $this->post->load('media');
    }

    public function addEmbed(): void
    {
        if (! $this->post) {
            session()->flash('embed-status', __('Save the post first to attach embeds.'));

            return;
        }

        $this->authorize('update', $this->post);

        $this->validate(['newEmbedUrl' => ['required', 'url', 'max:2048']]);

        $url = trim($this->newEmbedUrl);
        $provider = EmbedProvider::detect($url);
        $lookup = app(EmbedLookupService::class)->lookup($url);

        $nextOrder = ($this->post->embeds()->max('display_order') ?? -1) + 1;

        PostEmbed::create([
            'post_id' => $this->post->id,
            'provider' => $provider,
            'url' => $url,
            'title' => $lookup['title'],
            'thumbnail_url' => $lookup['thumbnail_url'],
            'display_order' => $nextOrder,
        ]);

        $this->newEmbedUrl = '';
        $this->post->load('embeds');
    }

    public function removeEmbed(int $embedId): void
    {
        if (! $this->post) {
            return;
        }
        $this->authorize('update', $this->post);
        $this->post->embeds()->whereKey($embedId)->delete();
        $this->post->load('embeds');
    }

    public function with(): array
    {
        return [
            'coverUrl' => $this->post?->coverUrl('card'),
            'images' => $this->post?->getMedia('images') ?? collect(),
            'videos' => $this->post?->getMedia('videos') ?? collect(),
            'audios' => $this->post?->getMedia('audios') ?? collect(),
            'documents' => $this->post?->getMedia('documents') ?? collect(),
            'embeds' => $this->post?->embeds ?? collect(),
        ];
    }
};