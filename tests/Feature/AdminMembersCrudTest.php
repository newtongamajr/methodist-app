<?php

use App\Models\Church;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lists only non-admin members in the members CRUD', function () {
    $church = Church::factory()->create();

    $member = User::factory()->create(['name' => 'Plain Member']);
    $member->assignRole('user');
    $member->churches()->syncWithoutDetaching([$church->id => ['is_primary' => true]]);

    $admin = User::factory()->create(['name' => 'Some Admin']);
    $admin->assignRole('local_admin');
    $admin->churches()->syncWithoutDetaching([$church->id => ['is_primary' => true]]);

    $super = User::factory()->create();
    $super->assignRole('national_admin');

    $this->actingAs($super)
        ->get(route('admin.members.index'))
        ->assertOk()
        ->assertSee('Plain Member')
        ->assertDontSee('Some Admin');
});

it('lets a global manager create a member with multi-church attachments', function () {
    $a = Church::factory()->create();
    $b = Church::factory()->create();

    $super = User::factory()->create();
    $super->assignRole('national_admin');
    $this->actingAs($super);

    Livewire::test('admin.members.editor')
        ->set('form.name', 'New Member')
        ->set('form.email', 'newmember@demo.test')
        ->set('form.password', 'secret-password')
        ->set('form.nature', 'interested')
        ->set('form.church_ids', [$a->id, $b->id])
        ->set('form.primary_church_id', $b->id)
        ->set('form.locale', 'pt_BR')
        ->call('save')
        ->assertHasNoErrors();

    $created = User::firstWhere('email', 'newmember@demo.test');
    expect($created)->not->toBeNull();
    expect($created->hasRole('user'))->toBeTrue();
    expect($created->churches->pluck('id')->all())->toEqualCanonicalizing([$a->id, $b->id]);
    expect($created->person->managing_church_id)->toBe($b->id);
});

it('forces a master\'s new member into the master\'s church pool', function () {
    $own = Church::factory()->create();
    $foreign = Church::factory()->create();

    $master = User::factory()->create();
    $master->assignRole('local_admin');
    $master->churches()->syncWithoutDetaching([$own->id => ['is_primary' => true]]);

    $this->actingAs($master);

    Livewire::test('admin.members.editor')
        ->set('form.name', 'Local Newbie')
        ->set('form.email', 'newbie@demo.test')
        ->set('form.password', 'secret-password')
        ->set('form.nature', 'member')
        ->set('form.church_ids', [$foreign->id]) // attempts to escape
        ->set('form.primary_church_id', $foreign->id)
        ->set('form.locale', 'pt_BR')
        ->call('save')
        ->assertHasNoErrors();

    $created = User::firstWhere('email', 'newbie@demo.test');
    expect($created->churches->pluck('id')->all())->toEqualCanonicalizing([$own->id]);
    expect($created->person->managing_church_id)->toBe($own->id);
});

it('returns 404 when trying to edit an admin via the members editor', function () {
    $church = Church::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('local_admin');
    $admin->churches()->syncWithoutDetaching([$church->id => ['is_primary' => true]]);

    $super = User::factory()->create();
    $super->assignRole('national_admin');
    $this->actingAs($super);

    $this->get(route('admin.members.edit', $admin))->assertNotFound();
});
