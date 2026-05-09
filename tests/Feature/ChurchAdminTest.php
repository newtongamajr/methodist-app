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
    $u->assignRole('national_admin');

    return $u;
}

function makeMaster(Church $church): User
{
    $u = User::factory()->create();
    $u->person->update(['managing_church_id' => $church->id]);
    $u->assignRole('local_admin');
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
        ->set('form.ecclesiastical_region_id', $region->id)
        ->set('form.name', 'Igreja Demo')
        ->set('form.city', 'São Paulo')
        ->set('form.state', 'SP')
        ->set('form.master_name', 'Pastor Demo')
        ->set('form.master_email', 'pastor@demo.test')
        ->set('form.master_password', 'secret-password')
        ->call('save')
        ->assertHasNoErrors();

    $church = Church::firstWhere('name', 'Igreja Demo');
    expect($church)->not->toBeNull();

    $master = User::firstWhere('email', 'pastor@demo.test');
    expect($master)->not->toBeNull();
    expect($master->hasRole('local_admin'))->toBeTrue();
    expect($master->person->managing_church_id)->toBe($church->id);
    expect($master->churches->contains($church))->toBeTrue();
});

it('forces a master user creating an admin to local_admin (role escalation blocked downstream)', function () {
    // Church associations live on the dedicated /admin/users/{id}/churches
    // page now — the editor only handles identity + role + locale + appearance.
    $church = Church::factory()->create();
    $master = makeMaster($church);

    $this->actingAs($master);

    Livewire::test('admin.users.editor')
        ->set('form.name', 'Helper')
        ->set('form.email', 'helper@demo.test')
        ->set('form.password', 'secret-password')
        ->set('form.password_confirmation', 'secret-password')
        ->set('form.role', 'local_admin')
        ->set('form.locale', 'pt_BR')
        ->set('form.appearance', 'system')
        ->call('save')
        ->assertHasNoErrors();

    $helper = User::firstWhere('email', 'helper@demo.test');
    expect($helper)->not->toBeNull();
    expect($helper->hasRole('local_admin'))->toBeTrue();
    expect($helper->hasRole('national_admin'))->toBeFalse();
});

it('rejects a master user trying to assign national_admin role', function () {
    $church = Church::factory()->create();
    $master = makeMaster($church);

    $this->actingAs($master);

    Livewire::test('admin.users.editor')
        ->set('form.name', 'Escalator')
        ->set('form.email', 'escalator@demo.test')
        ->set('form.password', 'secret-password')
        ->set('form.password_confirmation', 'secret-password')
        ->set('form.role', 'national_admin')
        ->set('form.locale', 'pt_BR')
        ->set('form.appearance', 'system')
        ->call('save')
        ->assertHasErrors('form.role');

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
    $strangerB = User::factory()->create(['name' => 'Should not appear']);
    $strangerB->person->update(['managing_church_id' => $churchB->id]);
    $strangerB->churches()->syncWithoutDetaching([$churchB->id => ['is_primary' => true]]);
    $strangerB->assignRole('local_admin');

    $this->actingAs($masterA)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertDontSee('Should not appear');
});
