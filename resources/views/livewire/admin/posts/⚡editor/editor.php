<?php

use App\Enums\EmbedProvider;
use App\Enums\PostScope;
use App\Enums\PostStatus;
use App\Livewire\Forms\PostForm;
use App\Models\Church;
use App\Models\Post;
use App\Models\PostEmbed;
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
            $post = Post::with(['media', 'embeds'])->findOrFail($postId);
            $this->authorize('update', $post);
            $this->form->setPost($post);
        } else {
            $this->form->scope = $user->can('posts.create.shared') ? PostScope::Shared->value : PostScope::Local->value;
            $this->form->status = PostStatus::Draft->value;
            $this->form->church_id = $user->currentChurchId();
        }
    }

    #[Computed]
    public function churches(): Collection
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

        $data = $this->form->validate();

        $this->validate([
            'newCover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'newImages.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
            'newVideos.*' => ['file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime', 'max:102400'],
            'newAudios.*' => ['file', 'mimetypes:audio/mp4,audio/mpeg,audio/ogg,audio/wav,audio/webm', 'max:51200'],
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

        if ($this->form->post) {
            $this->authorize('update', $this->form->post);
            $this->form->post->update($data);
        } else {
            $data['author_id'] = $user->id;
            $this->form->post = Post::create($data);
        }

        $this->persistPendingMedia();

        session()->flash('status', __('Post saved.'));

        $this->redirect(route('admin.posts.edit', $this->form->post), navigate: true);
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
