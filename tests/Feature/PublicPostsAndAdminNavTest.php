<?php

use App\Models\Church;
use App\Models\EcclesiasticalRegion;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\App;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    App::setLocale('en');
});

it('serves the public posts feed to guests with shared published posts only', function () {
    Post::factory()->published()->create(['title' => 'Open to all']);
    Post::factory()->local(Church::factory()->create())->published()->create(['title' => 'Members only']);

    $this->withSession(['locale' => 'en'])
        ->get(route('posts.index'))
        ->assertOk()
        ->assertSee('Open to all')
        ->assertDontSee('Members only');
});

it('allows a guest to view a shared post detail and shows a sign-in prompt instead of the comment form', function () {
    $post = Post::factory()->published()->create(['title' => 'Devotional 1']);

    $response = $this->get(route('posts.show', $post->slug))
        ->assertOk()
        ->assertSee('Devotional 1');

    expect($response->getContent())->toContain(route('login'));
    expect($response->getContent())->not->toContain('wire:submit="submitComment"');
});

it('hides admin dropdown from regular users', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->withSession(['locale' => 'en'])
        ->get(route('posts.index'))
        ->assertOk()
        ->assertDontSee('Posts manager')
        ->assertDontSee('Moderate comments')
        ->assertDontSee('Ecclesiastical regions');
});

it('shows the full admin menu only to a global manager', function () {
    $super = User::factory()->create();
    $super->assignRole('national_admin');
    $super->locale = 'en';
    $super->save();

    $this->actingAs($super)
        ->withSession(['locale' => 'en'])
        ->get(route('posts.index'))
        ->assertOk()
        ->assertSee('Posts manager')
        ->assertSee('Moderate comments')
        ->assertSee('Ecclesiastical regions');
});

it('lets a super-user CRUD an ecclesiastical region', function () {
    $super = User::factory()->create();
    $super->assignRole('national_admin');
    $this->actingAs($super);

    Livewire::test('admin.regions.editor')
        ->set('form.code', 'RE9')
        ->set('form.name', '9ª Região Eclesiástica')
        ->set('form.kind', 'regular')
        ->set('form.display_order', 50)
        ->call('save')
        ->assertHasNoErrors();

    expect(EcclesiasticalRegion::where('code', 'RE9')->exists())->toBeTrue();
});

it('blocks region CRUD for users without church.manage', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $this->actingAs($user)
        ->get(route('admin.regions.index'))
        ->assertForbidden();
});
