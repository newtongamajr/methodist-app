<?php

use App\Models\Church;
use App\Models\EcclesiasticalRegion;
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
        ->set('member_type', 'teenager')
        ->set('region_id', $region->id)
        ->set('church_id', $church->id)
        ->call('updateMembership')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->member_type->value)->toBe('teenager');
    expect($user->church_id)->toBe($church->id);
    expect($user->churches->contains($church))->toBeTrue();
});

it('clears the church when the region changes to one that does not contain it', function () {
    $regionA = EcclesiasticalRegion::factory()->create();
    $regionB = EcclesiasticalRegion::factory()->create();
    $church = Church::factory()->create(['ecclesiastical_region_id' => $regionA->id]);
    $user = User::factory()->create(['church_id' => $church->id]);

    $this->actingAs($user);

    Livewire::test('profile.update-membership')
        ->set('region_id', $regionA->id)
        ->set('church_id', $church->id)
        ->set('region_id', $regionB->id)
        ->assertSet('church_id', null);
});

it('persists contact fields', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('profile.update-contact')
        ->set('phone', '(21) 88888-0000')
        ->set('birthdate', '2010-05-01')
        ->call('updateContact')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->phone)->toBe('(21) 88888-0000');
    expect($user->birthdate->format('Y-m-d'))->toBe('2010-05-01');
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
