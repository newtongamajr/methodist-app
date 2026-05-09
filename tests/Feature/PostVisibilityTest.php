<?php

use App\Models\Church;
use App\Models\Post;
use App\Models\User;

it('shows shared published posts to everyone', function () {
    $shared = Post::factory()->published()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('posts.index'))
        ->assertOk()
        ->assertSee($shared->title);
});

it('hides local posts from users in other churches', function () {
    $churchA = Church::factory()->create();
    $churchB = Church::factory()->create();

    $localA = Post::factory()->published()->local($churchA)->create(['title' => 'Only for A']);

    $userB = User::factory()->forChurch($churchB)->create();

    $this->actingAs($userB)
        ->get(route('posts.index'))
        ->assertOk()
        ->assertDontSee('Only for A');
});

it('shows local posts to members of the same church', function () {
    $church = Church::factory()->create();
    $local = Post::factory()->published()->local($church)->create(['title' => 'Local note']);

    $user = User::factory()->forChurch($church)->create();

    $this->actingAs($user)
        ->get(route('posts.index'))
        ->assertOk()
        ->assertSee('Local note');
});

it('rejects access to a local post detail for a non-member', function () {
    $church = Church::factory()->create();
    $localPost = Post::factory()->published()->local($church)->create();

    $other = User::factory()->create();

    $this->actingAs($other)
        ->get(route('posts.show', $localPost->slug))
        ->assertForbidden();
});

it('hides drafts from the public feed', function () {
    Post::factory()->create(['title' => 'Draft only', 'status' => 'draft']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('posts.index'))
        ->assertOk()
        ->assertDontSee('Draft only');
});
