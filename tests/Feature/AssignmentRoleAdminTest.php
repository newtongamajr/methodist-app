<?php

use App\Models\AssignmentRole;
use App\Models\Church;
use App\Models\District;
use App\Models\EcclesiasticalRegion;
use App\Models\FunctionRole;
use App\Models\Group;
use App\Models\Person;
use App\Models\PersonRoleAssignment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsSuperAR(): User
{
    $u = User::factory()->create();
    $u->assignRole('national_admin');
    test()->actingAs($u);

    return $u;
}

it('only church.manage users can reach the assignment-roles CRUD', function () {
    $regular = User::factory()->create();
    $regular->assignRole('user');
    $this->actingAs($regular)->get(route('admin.assignment-roles.index'))->assertForbidden();

    $this->actingAs(actingAsSuperAR())->get(route('admin.assignment-roles.index'))->assertOk();
});

it('creates an assignment role and auto-generates a slug', function () {
    actingAsSuperAR();

    Livewire::test('admin.assignment-roles.editor')
        ->set('form.name', 'Treasurer')
        ->set('form.description', 'Handles finances.')
        ->call('save');

    expect(AssignmentRole::where('name', 'Treasurer')->first())
        ->not->toBeNull()
        ->slug->toBe('treasurer')
        ->is_active->toBeTrue();
});

it('updates an existing assignment role', function () {
    actingAsSuperAR();
    $role = AssignmentRole::create(['name' => 'Old name', 'slug' => 'old-name']);

    Livewire::test('admin.assignment-roles.editor', ['assignmentRoleId' => $role->id])
        ->set('form.name', 'New name')
        ->call('save');

    expect($role->fresh())->name->toBe('New name')->slug->toBe('old-name');
});

it('deletes an assignment role from the index', function () {
    actingAsSuperAR();
    $role = AssignmentRole::create(['name' => 'Temp', 'slug' => 'temp']);

    Livewire::test('admin.assignment-roles.index')->call('delete', $role->id);

    expect(AssignmentRole::find($role->id))->toBeNull();
});

it('lists people who hold a given assignment role with their group', function () {
    actingAsSuperAR();

    $region = EcclesiasticalRegion::factory()->create();
    $district = District::factory()->create(['ecclesiastical_region_id' => $region->id]);
    $church = Church::factory()->create([
        'ecclesiastical_region_id' => $region->id,
        'district_id' => $district->id,
    ]);
    $group = Group::create([
        'kind' => 'council',
        'name' => 'Local council',
        'slug' => 'local-council',
        'ecclesiastical_region_id' => $region->id,
        'district_id' => $district->id,
        'church_id' => $church->id,
        'is_active' => true,
    ]);
    $role = AssignmentRole::create(['name' => 'Secretary', 'slug' => 'secretary']);
    $person = Person::factory()->create();
    $function = FunctionRole::create([
        'name' => 'Member',
        'slug' => 'member',
        'applies_to' => ['council'],
        'is_active' => true,
        'display_order' => 0,
    ]);
    PersonRoleAssignment::create([
        'person_id' => $person->id,
        'group_id' => $group->id,
        'function_id' => $function->id,
        'assignment_role_id' => $role->id,
        'started_at' => now()->subMonth()->toDateString(),
    ]);

    Livewire::test('admin.assignment-roles.people', ['assignmentRoleId' => $role->id])
        ->assertSee($person->name)
        ->assertSee('Local council');
});
