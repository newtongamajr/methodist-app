<?php

use App\Models\Church;
use App\Models\EcclesiasticalRegion;
use App\Models\Person;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('persists identity fields', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);

    $this->actingAs($user);

    Livewire::test('profile.update-identity')
        ->set('name', 'Updated Name')
        ->set('email', 'new@example.com')
        ->call('updateIdentity')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toBe('Updated Name');
    expect($user->email)->toBe('new@example.com');
});

it('persists membership fields and syncs the church pivot', function () {
    $region = EcclesiasticalRegion::factory()->create();
    $church = Church::factory()->create(['ecclesiastical_region_id' => $region->id]);
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('profile.update-membership')
        ->set('nature', 'teenager')
        ->set('region_id', $region->id)
        ->set('church_id', $church->id)
        ->call('updateMembership')
        ->assertHasNoErrors();

    $user->refresh()->load('person');

    expect($user->person->natures)->toBe(['teenager']);
    expect($user->person->managing_church_id)->toBe($church->id);
    expect($user->churches->contains($church))->toBeTrue();
});

it('clears the church when the region changes to one that does not contain it', function () {
    $regionA = EcclesiasticalRegion::factory()->create();
    $regionB = EcclesiasticalRegion::factory()->create();
    $church = Church::factory()->create(['ecclesiastical_region_id' => $regionA->id]);
    $user = User::factory()->create();
    $user->person->update(['managing_church_id' => $church->id]);

    $this->actingAs($user);

    Livewire::test('profile.update-membership')
        ->set('region_id', $regionA->id)
        ->set('church_id', $church->id)
        ->set('region_id', $regionB->id)
        ->assertSet('church_id', null);
});

it('lets a non-admin user manage their own contacts via the People components on profile', function () {
    // The legacy single-field profile.update-contact component was replaced
    // by the full Contacts tab on the profile, which delegates to the same
    // admin.people.contacts component admins use. The mount gate now allows
    // a non-admin to render that component when personId matches their own.
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('admin.people.contacts', ['personId' => $user->person->id])
        ->call('openCreate')
        ->set('form.type', 'phone')
        ->set('form.country', 'BR')
        ->set('form.value', '(21) 88888-0000')
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh()->load('person.contacts');

    // Phone contacts are stored with the country prefix baked in.
    expect($user->person->contacts()->where('type', 'phone')->value('value'))
        ->toBe('+55 (21) 88888-0000');
});

it('blocks a non-admin from managing someone else\'s contacts', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $otherPerson = Person::factory()->create();

    Livewire::test('admin.people.contacts', ['personId' => $otherPerson->id])
        ->assertStatus(403);
});

it('persists preferences and reflects them in the session', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('profile.update-preferences')
        ->set('locale', 'en')
        ->set('appearance', 'dark')
        ->call('updatePreferences')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->locale)->toBe('en');
    expect($user->appearance)->toBe('dark');
    expect(session('locale'))->toBe('en');
    expect(session('appearance'))->toBe('dark');
});
