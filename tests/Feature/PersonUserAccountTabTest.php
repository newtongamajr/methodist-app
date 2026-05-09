<?php

use App\Enums\PersonType;
use App\Models\Person;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $u = User::factory()->create();
    $u->assignRole('national_admin');
    $this->actingAs($u);
});

it('creates a user account for an individual person', function () {
    $person = Person::factory()->create(['name' => 'Eve Doe']);

    Livewire::test('admin.people.user-account', ['personId' => $person->id])
        ->set('email', 'eve@example.test')
        ->set('password', 'secret-password')
        ->set('locale', 'pt_BR')
        ->call('createUser')
        ->assertHasNoErrors();

    $person->refresh();
    expect($person->user)->not->toBeNull();
    expect($person->user->email)->toBe('eve@example.test');
    expect($person->user->hasRole('user'))->toBeTrue();
});

it('refuses to create a second user for the same person', function () {
    $existingUser = User::factory()->create();

    Livewire::test('admin.people.user-account', ['personId' => $existingUser->person_id])
        ->set('email', 'duplicate@example.test')
        ->set('password', 'secret-password')
        ->set('locale', 'pt_BR')
        ->call('createUser')
        ->assertStatus(422);

    expect(User::where('email', 'duplicate@example.test')->exists())->toBeFalse();
});

it('refuses to create a user for an organization person', function () {
    $org = Person::factory()->organization()->create(['name' => 'Some Org']);

    Livewire::test('admin.people.user-account', ['personId' => $org->id])
        ->set('email', 'org@example.test')
        ->set('password', 'secret-password')
        ->set('locale', 'pt_BR')
        ->call('createUser')
        ->assertStatus(422);

    expect(User::where('email', 'org@example.test')->exists())->toBeFalse();
});

it('rejects a duplicate email when creating a user', function () {
    $existing = User::factory()->create(['email' => 'taken@example.test']);
    $person = Person::factory()->create();

    Livewire::test('admin.people.user-account', ['personId' => $person->id])
        ->set('email', 'taken@example.test')
        ->set('password', 'secret-password')
        ->set('locale', 'pt_BR')
        ->call('createUser')
        ->assertHasErrors(['email']);
});

it('disconnects an existing user account', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $personId = $user->person_id;

    Livewire::test('admin.people.user-account', ['personId' => $personId])
        ->call('disconnect');

    expect(User::find($user->id))->toBeNull();
    // Person record stays.
    expect(Person::find($personId))->not->toBeNull();
});

it('refuses to disconnect the last national_admin', function () {
    // Drop other national_admins so only the beforeEach actor remains, then
    // try to disconnect them — the guard should refuse.
    $actor = auth()->user();
    User::role('national_admin')->where('id', '!=', $actor->id)->each(fn ($u) => $u->forceDelete());

    Livewire::test('admin.people.user-account', ['personId' => $actor->person_id])
        ->call('disconnect')
        ->assertHasErrors(['user']);

    expect(User::find($actor->id))->not->toBeNull();
});
