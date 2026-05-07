<?php

use App\Models\Church;
use App\Models\EcclesiasticalRegion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function makeSuper(): User
{
    $u = User::factory()->create();
    $u->assignRole('global_manager');

    return $u;
}

function makeMaster(Church $church): User
{
    $u = User::factory()->create(['church_id' => $church->id]);
    $u->assignRole('local_manager');
    $u->churches()->syncWithoutDetaching([$church->id => ['is_primary' => true]]);

    return $u;
}

it('only super-users can reach the churches CRUD', function () {
    $regular = User::factory()->create();
    $regular->assignRole('user');

    $this->actingAs($regular)->get(route('admin.churches.index'))->assertForbidden();

    $this->actingAs(makeSuper())->get(route('admin.churches.index'))->assertOk();
});

it('creates a church and a master user in the same flow', function () {
    $region = EcclesiasticalRegion::factory()->create();

    $this->actingAs(makeSuper());

    Livewire::test('admin.churches.editor')
        ->set('ecclesiastical_region_id', $region->id)
        ->set('name', 'Igreja Demo')
        ->set('city', 'São Paulo')
        ->set('state', 'SP')
        ->set('master_name', 'Pastor Demo')
        ->set('master_email', 'pastor@demo.test')
        ->set('master_password', 'secret-password')
        ->call('save')
        ->assertHasNoErrors();

    $church = Church::firstWhere('name', 'Igreja Demo');
    expect($church)->not->toBeNull();

    $master = User::firstWhere('email', 'pastor@demo.test');
    expect($master)->not->toBeNull();
    expect($master->hasRole('local_manager'))->toBeTrue();
    expect($master->church_id)->toBe($church->id);
    expect($master->churches->contains($church))->toBeTrue();
});

it('lets a master user create another admin only for their own church', function () {
    $church = Church::factory()->create();
    $otherChurch = Church::factory()->create();
    $master = makeMaster($church);

    $this->actingAs($master);

    // Even if a master submits a foreign church id, the editor strips it back
    // to the master's own church set (and forces local_manager role).
    Livewire::test('admin.users.editor')
        ->set('name', 'Helper')
        ->set('email', 'helper@demo.test')
        ->set('password', 'secret-password')
        ->set('church_ids', [$otherChurch->id])
        ->set('primary_church_id', $otherChurch->id)
        ->set('role', 'local_manager')
        ->set('locale', 'pt_BR')
        ->call('save')
        ->assertHasNoErrors();

    $helper = User::firstWhere('email', 'helper@demo.test');
    expect($helper)->not->toBeNull();
    expect($helper->church_id)->toBe($church->id);
    expect($helper->hasRole('local_manager'))->toBeTrue();
    expect($helper->hasRole('global_manager'))->toBeFalse();
});

it('rejects a master user trying to assign global_manager role', function () {
    $church = Church::factory()->create();
    $master = makeMaster($church);

    $this->actingAs($master);

    Livewire::test('admin.users.editor')
        ->set('name', 'Escalator')
        ->set('email', 'escalator@demo.test')
        ->set('password', 'secret-password')
        ->set('role', 'global_manager')
        ->set('locale', 'pt_BR')
        ->call('save')
        ->assertHasErrors('role');

    expect(User::where('email', 'escalator@demo.test')->exists())->toBeFalse();
});

it('blocks a master user from reaching the churches CRUD', function () {
    $church = Church::factory()->create();
    $master = makeMaster($church);

    $this->actingAs($master)->get(route('admin.churches.index'))->assertForbidden();
});

it('limits master\'s users list to their own church', function () {
    $churchA = Church::factory()->create();
    $churchB = Church::factory()->create();

    $masterA = makeMaster($churchA);
    $strangerB = User::factory()->create(['church_id' => $churchB->id, 'name' => 'Should not appear']);
    $strangerB->assignRole('local_manager');

    $this->actingAs($masterA)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertDontSee('Should not appear');
});
