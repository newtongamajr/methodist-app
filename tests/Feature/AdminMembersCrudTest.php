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
    $admin->assignRole('local_manager');
    $admin->churches()->syncWithoutDetaching([$church->id => ['is_primary' => true]]);

    $super = User::factory()->create();
    $super->assignRole('global_manager');

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
    $super->assignRole('global_manager');
    $this->actingAs($super);

    Livewire::test('admin.members.editor')
        ->set('name', 'New Member')
        ->set('email', 'newmember@demo.test')
        ->set('password', 'secret-password')
        ->set('member_type', 'interested')
        ->set('church_ids', [$a->id, $b->id])
        ->set('primary_church_id', $b->id)
        ->set('locale', 'pt_BR')
        ->call('save')
        ->assertHasNoErrors();

    $created = User::firstWhere('email', 'newmember@demo.test');
    expect($created)->not->toBeNull();
    expect($created->hasRole('user'))->toBeTrue();
    expect($created->churches->pluck('id')->all())->toEqualCanonicalizing([$a->id, $b->id]);
    expect($created->church_id)->toBe($b->id);
});

it('forces a master\'s new member into the master\'s church pool', function () {
    $own = Church::factory()->create();
    $foreign = Church::factory()->create();

    $master = User::factory()->create(['church_id' => $own->id]);
    $master->assignRole('local_manager');
    $master->churches()->syncWithoutDetaching([$own->id => ['is_primary' => true]]);

    $this->actingAs($master);

    Livewire::test('admin.members.editor')
        ->set('name', 'Local Newbie')
        ->set('email', 'newbie@demo.test')
        ->set('password', 'secret-password')
        ->set('member_type', 'member')
        ->set('church_ids', [$foreign->id]) // attempts to escape
        ->set('primary_church_id', $foreign->id)
        ->set('locale', 'pt_BR')
        ->call('save')
        ->assertHasNoErrors();

    $created = User::firstWhere('email', 'newbie@demo.test');
    expect($created->churches->pluck('id')->all())->toEqualCanonicalizing([$own->id]);
    expect($created->church_id)->toBe($own->id);
});

it('returns 404 when trying to edit an admin via the members editor', function () {
    $church = Church::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('local_manager');
    $admin->churches()->syncWithoutDetaching([$church->id => ['is_primary' => true]]);

    $super = User::factory()->create();
    $super->assignRole('global_manager');
    $this->actingAs($super);

    $this->get(route('admin.members.edit', $admin))->assertNotFound();
});
