<?php

use App\Enums\EmbedProvider;
use App\Models\Post;
use App\Models\User;
use App\Services\EmbedLookupService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('media');
});

function actAsGlobalManager(): User
{
    $user = User::factory()->create();
    $user->assignRole('global_manager');
    test()->actingAs($user);

    return $user;
}

it('saves a new post and attaches a cover to the cover collection', function () {
    actAsGlobalManager();

    Livewire::test('admin.posts.editor')
        ->set('form.title', 'Hello world')
        ->set('form.body', '<p>Body</p>')
        ->set('form.scope', 'shared')
        ->set('form.status', 'draft')
        ->set('newCover', UploadedFile::fake()->image('cover.png', 1200, 800))
        ->call('save')
        ->assertHasNoErrors();

    $post = Post::where('title', 'Hello world')->firstOrFail();

    expect($post->getMedia('cover'))->toHaveCount(1);
    expect($post->getFirstMedia('cover')->collection_name)->toBe('cover');
});

it('attaches multiple images to the images collection', function () {
    actAsGlobalManager();

    Livewire::test('admin.posts.editor')
        ->set('form.title', 'With images')
        ->set('form.body', '<p>x</p>')
        ->set('form.scope', 'shared')
        ->set('form.status', 'draft')
        ->set('newImages', [
            UploadedFile::fake()->image('a.png', 800, 600),
            UploadedFile::fake()->image('b.jpg', 800, 600),
        ])
        ->call('save')
        ->assertHasNoErrors();

    $post = Post::where('title', 'With images')->firstOrFail();

    expect($post->getMedia('images'))->toHaveCount(2);
});

it('attaches PDF documents to the documents collection', function () {
    actAsGlobalManager();

    Livewire::test('admin.posts.editor')
        ->set('form.title', 'With PDFs')
        ->set('form.body', '<p>x</p>')
        ->set('form.scope', 'shared')
        ->set('form.status', 'draft')
        ->set('newDocuments', [
            UploadedFile::fake()->createWithContent('a.pdf', "%PDF-1.4\n%fake pdf body\n%%EOF\n"),
        ])
        ->call('save')
        ->assertHasNoErrors();

    $post = Post::where('title', 'With PDFs')->firstOrFail();

    expect($post->getMedia('documents'))->toHaveCount(1);
});

it('detects the provider and persists embed metadata via the lookup service', function () {
    $this->app->instance(EmbedLookupService::class, new class extends EmbedLookupService
    {
        public function lookup(string $url): array
        {
            return ['title' => 'Fake Title', 'thumbnail_url' => 'https://img.example/x.jpg'];
        }
    });

    $user = actAsGlobalManager();
    $post = Post::factory()->create(['author_id' => $user->id]);

    Livewire::test('admin.posts.editor', ['postId' => $post->id])
        ->set('newEmbedUrl', 'https://www.youtube.com/watch?v=abc123')
        ->call('addEmbed')
        ->assertHasNoErrors()
        ->assertSet('newEmbedUrl', '');

    $embed = $post->embeds()->firstOrFail();

    expect($embed->provider)->toBe(EmbedProvider::YouTube)
        ->and($embed->title)->toBe('Fake Title')
        ->and($embed->thumbnail_url)->toBe('https://img.example/x.jpg')
        ->and($embed->url)->toBe('https://www.youtube.com/watch?v=abc123');
});

it('persists an embed even when the lookup returns nulls', function () {
    $this->app->instance(EmbedLookupService::class, new class extends EmbedLookupService
    {
        public function lookup(string $url): array
        {
            return ['title' => null, 'thumbnail_url' => null];
        }
    });

    $user = actAsGlobalManager();
    $post = Post::factory()->create(['author_id' => $user->id]);

    Livewire::test('admin.posts.editor', ['postId' => $post->id])
        ->set('newEmbedUrl', 'https://open.spotify.com/track/xyz')
        ->call('addEmbed')
        ->assertHasNoErrors();

    $embed = $post->embeds()->firstOrFail();

    expect($embed->provider)->toBe(EmbedProvider::Spotify)
        ->and($embed->title)->toBeNull()
        ->and($embed->thumbnail_url)->toBeNull();
});

it('removes embeds and existing media on the editor', function () {
    actAsGlobalManager();
    $post = Post::factory()->create();

    $upload = UploadedFile::fake()->image('foo.png', 800, 600);
    $post->addMedia($upload)
        ->usingFileName('foo.png')
        ->toMediaCollection('images');

    $post->embeds()->create([
        'provider' => EmbedProvider::YouTube,
        'url' => 'https://youtu.be/abc',
        'display_order' => 0,
    ]);

    expect($post->fresh()->getMedia('images'))->toHaveCount(1)
        ->and($post->fresh()->embeds)->toHaveCount(1);

    $mediaId = $post->getFirstMedia('images')->id;
    $embedId = $post->embeds()->first()->id;

    Livewire::test('admin.posts.editor', ['postId' => $post->id])
        ->call('removeMedia', $mediaId)
        ->call('removeEmbed', $embedId);

    expect($post->fresh()->getMedia('images'))->toBeEmpty()
        ->and($post->fresh()->embeds)->toBeEmpty();
});
