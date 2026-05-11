<?php

use App\Enums\CommentStatus;
use App\Models\Church;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('toggles a like idempotently', function () {
    $post = Post::factory()->published()->create();
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('posts.show', ['slug' => $post->slug])
        ->call('toggleLike')
        ->assertSet('liked', true);

    expect($post->likes()->count())->toBe(1);

    Livewire::test('posts.show', ['slug' => $post->slug])
        ->call('toggleLike')
        ->assertSet('liked', false);

    expect($post->likes()->count())->toBe(0);
});

it('saves comments as pending and hides them until approved', function () {
    $post = Post::factory()->published()->create();
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('posts.show', ['slug' => $post->slug])
        ->set('newComment', 'Glory to God!')
        ->call('submitComment')
        ->assertHasNoErrors();

    $comment = PostComment::firstWhere('user_id', $user->id);
    expect($comment->status)->toBe(CommentStatus::Pending);

    $this->get(route('posts.show', $post->slug))
        ->assertOk()
        ->assertDontSee('Glory to God!');
});

it('shows approved comments on the post', function () {
    $post = Post::factory()->published()->create();
    $author = User::factory()->create(['name' => 'Maria']);
    PostComment::factory()->for($post)->for($author, 'author')->approved()->create([
        'body' => 'Praying with you.',
    ]);

    $reader = User::factory()->create();

    $this->actingAs($reader)
        ->get(route('posts.show', $post->slug))
        ->assertOk()
        ->assertSee('Praying with you.')
        ->assertSee('Maria');
});

it('auto-approves comments from global and local managers', function () {
    $church = Church::factory()->create();
    $post = Post::factory()->published()->create();

    $globalAdmin = User::factory()->create();
    $globalAdmin->assignRole('national_admin');
    $this->actingAs($globalAdmin);

    Livewire::test('posts.show', ['slug' => $post->slug])
        ->set('newComment', 'Glory to God!')
        ->call('submitComment');

    $globalComment = PostComment::where('user_id', $globalAdmin->id)->first();
    expect($globalComment->status)->toBe(CommentStatus::Approved);
    expect($globalComment->approved_by)->toBe($globalAdmin->id);
    expect($globalComment->approved_at)->not->toBeNull();

    $localAdmin = User::factory()->create();
    $localAdmin->assignRole('local_admin');
    $localAdmin->churches()->syncWithoutDetaching([$church->id => ['is_primary' => true]]);
    $this->actingAs($localAdmin);

    Livewire::test('posts.show', ['slug' => $post->slug])
        ->set('newComment', 'Praise God!')
        ->call('submitComment');

    $localComment = PostComment::where('user_id', $localAdmin->id)->first();
    expect($localComment->status)->toBe(CommentStatus::Approved);
    expect($localComment->approved_by)->toBe($localAdmin->id);

    // A regular user's comment still goes to pending.
    $regular = User::factory()->create();
    $regular->assignRole('user');
    $this->actingAs($regular);

    Livewire::test('posts.show', ['slug' => $post->slug])
        ->set('newComment', 'Hello.')
        ->call('submitComment');

    $regularComment = PostComment::where('user_id', $regular->id)->first();
    expect($regularComment->status)->toBe(CommentStatus::Pending);
});

it('lets a global manager approve a comment via the moderation queue', function () {
    $post = Post::factory()->published()->create();
    $comment = PostComment::factory()->for($post)->create();

    $admin = User::factory()->create();
    $admin->assignRole('national_admin');
    $this->actingAs($admin);

    Livewire::test('admin.comments.index')
        ->call('approve', $comment->id);

    $comment->refresh();
    expect($comment->status)->toBe(CommentStatus::Approved);
    expect($comment->approved_by)->toBe($admin->id);
});

it('forbids regular users from accessing the moderation queue', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $this->actingAs($user);

    $this->get(route('admin.comments.index'))->assertForbidden();
});
