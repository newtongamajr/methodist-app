<?php

use App\Models\Church;
use App\Models\EcclesiasticalRegion;
use App\Models\FunctionRole;
use App\Models\Person;
use App\Models\PersonRoleAssignment;
use App\Models\User;
use Database\Seeders\FunctionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(FunctionsSeeder::class);
});

function actingAsSuperRoles(): User
{
    $u = User::factory()->create();
    $u->assignRole('national_admin');
    test()->actingAs($u);

    return $u;
}

it('lists all role assignments for a person', function () {
    actingAsSuperRoles();
    $person = Person::factory()->create();
    $church = Church::factory()->create(['name' => 'Igreja Central']);
    $aux = FunctionRole::where('slug', 'auxiliary_pastor')->first();

    PersonRoleAssignment::create([
        'person_id' => $person->id, 'function_id' => $aux->id, 'church_id' => $church->id,
        'started_at' => now()->subMonth()->toDateString(),
    ]);

    Livewire::test('admin.people.roles', ['personId' => $person->id])
        ->assertSee('Auxiliary Pastor')
        ->assertSee('Igreja Central');
});

it('creates a new pastor assignment via the modal', function () {
    actingAsSuperRoles();
    $person = Person::factory()->create();
    $church = Church::factory()->create();
    $sem = FunctionRole::where('slug', 'seminarist')->first();

    Livewire::test('admin.people.roles', ['personId' => $person->id])
        ->call('openCreate')
        ->set('form.function_id', $sem->id)
        ->set('form.church_id', $church->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($person->roleAssignments()->where('function_id', $sem->id)->where('church_id', $church->id)->exists())
        ->toBeTrue();
});

it('creates a national admin assignment with no scope FKs', function () {
    actingAsSuperRoles();
    $person = Person::factory()->create();
    $national = FunctionRole::where('slug', 'national_admin')->first();

    Livewire::test('admin.people.roles', ['personId' => $person->id])
        ->call('openCreate')
        ->set('form.function_id', $national->id)
        ->call('save')
        ->assertHasNoErrors();

    $row = $person->roleAssignments()->where('function_id', $national->id)->first();
    expect($row)->not->toBeNull();
    expect($row->church_id)->toBeNull();
    expect($row->district_id)->toBeNull();
    expect($row->ecclesiastical_region_id)->toBeNull();
});

it('creates a regional admin assignment with the chosen region', function () {
    actingAsSuperRoles();
    $person = Person::factory()->create();
    $region = EcclesiasticalRegion::factory()->create();
    $regional = FunctionRole::where('slug', 'regional_admin')->first();

    Livewire::test('admin.people.roles', ['personId' => $person->id])
        ->call('openCreate')
        ->set('form.function_id', $regional->id)
        ->set('form.ecclesiastical_region_id', $region->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($person->roleAssignments()->where('function_id', $regional->id)
        ->where('ecclesiastical_region_id', $region->id)->exists())->toBeTrue();
});

it('ends an active assignment from the Roles tab', function () {
    actingAsSuperRoles();
    $person = Person::factory()->create();
    $church = Church::factory()->create();
    $aux = FunctionRole::where('slug', 'auxiliary_pastor')->first();
    $a = PersonRoleAssignment::create([
        'person_id' => $person->id, 'function_id' => $aux->id,
        'church_id' => $church->id, 'started_at' => now()->subMonth()->toDateString(),
    ]);

    Livewire::test('admin.people.roles', ['personId' => $person->id])
        ->call('endAssignment', $a->id);

    expect($a->fresh()->ended_at)->not->toBeNull();
});
