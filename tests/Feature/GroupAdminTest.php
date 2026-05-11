<?php

use App\Enums\GroupKind;
use App\Models\Church;
use App\Models\District;
use App\Models\EcclesiasticalRegion;
use App\Models\FunctionRole;
use App\Models\Group;
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

function actingAsSuperGroup(): User
{
    $u = User::factory()->create();
    $u->assignRole('national_admin');
    test()->actingAs($u);

    return $u;
}

/** Region → District → Church chain so church-level groups satisfy GroupObserver. */
function fullChainChurch(): Church
{
    $region = EcclesiasticalRegion::factory()->create();
    $district = District::factory()->create(['ecclesiastical_region_id' => $region->id]);

    return Church::factory()->create([
        'ecclesiastical_region_id' => $region->id,
        'district_id' => $district->id,
    ]);
}

it('only church.manage users can reach the groups CRUD', function () {
    $regular = User::factory()->create();
    $regular->assignRole('user');
    $this->actingAs($regular)->get(route('admin.groups.index'))->assertForbidden();

    $this->actingAs(actingAsSuperGroup())->get(route('admin.groups.index'))->assertOk();
});

it('creates a national-level group with no scope FKs', function () {
    actingAsSuperGroup();

    Livewire::test('admin.groups.editor')
        ->set('form.kind', GroupKind::Council->value)
        ->set('form.name', 'Concílio Geral')
        ->set('form.level', 'national')
        ->call('save')
        ->assertHasNoErrors();

    $group = Group::firstWhere('name', 'Concílio Geral');
    expect($group)->not->toBeNull();
    expect($group->ecclesiastical_region_id)->toBeNull();
    expect($group->district_id)->toBeNull();
    expect($group->church_id)->toBeNull();
    expect($group->level())->toBe('national');
});

it('creates a region-level group requiring the region FK', function () {
    actingAsSuperGroup();
    $region = EcclesiasticalRegion::factory()->create();

    Livewire::test('admin.groups.editor')
        ->set('form.kind', GroupKind::Ministry->value)
        ->set('form.name', 'Ministério Regional')
        ->set('form.level', 'region')
        ->set('form.ecclesiastical_region_id', $region->id)
        ->call('save')
        ->assertHasNoErrors();

    $group = Group::firstWhere('name', 'Ministério Regional');
    expect($group->ecclesiastical_region_id)->toBe($region->id);
    expect($group->district_id)->toBeNull();
    expect($group->church_id)->toBeNull();
});

it('rejects a region-level group without a region FK', function () {
    actingAsSuperGroup();

    Livewire::test('admin.groups.editor')
        ->set('form.kind', GroupKind::Council->value)
        ->set('form.name', 'Sem Região')
        ->set('form.level', 'region')
        ->call('save')
        ->assertHasErrors(['form.ecclesiastical_region_id']);
});

it('creates a church-level group with all three scope FKs auto-denormalized', function () {
    actingAsSuperGroup();
    $region = EcclesiasticalRegion::factory()->create();
    $district = District::factory()->create(['ecclesiastical_region_id' => $region->id]);
    $church = Church::factory()->create([
        'ecclesiastical_region_id' => $region->id,
        'district_id' => $district->id,
    ]);

    Livewire::test('admin.groups.editor')
        ->set('form.kind', GroupKind::Commission->value)
        ->set('form.name', 'Comissão Local')
        ->set('form.level', 'church')
        ->set('form.ecclesiastical_region_id', $region->id)
        ->set('form.district_id', $district->id)
        ->set('form.church_id', $church->id)
        ->call('save')
        ->assertHasNoErrors();

    $group = Group::firstWhere('name', 'Comissão Local');
    expect($group->church_id)->toBe($church->id);
    expect($group->district_id)->toBe($district->id);
    expect($group->ecclesiastical_region_id)->toBe($region->id);
});

it('lists groups filtered by kind via ?kind= query param', function () {
    actingAsSuperGroup();
    Group::factory()->kind(GroupKind::Council)->create(['name' => 'My Council']);
    Group::factory()->kind(GroupKind::Ministry)->create(['name' => 'My Ministry']);

    Livewire::test('admin.groups.index', ['kind' => 'council'])
        ->assertSee('My Council')
        ->assertDontSee('My Ministry');
});

it('adds a member to a group via the editor modal', function () {
    actingAsSuperGroup();
    $church = fullChainChurch();
    $group = Group::factory()->kind(GroupKind::Council)->create([
        'name' => 'Council X',
        'church_id' => $church->id,
        'district_id' => $church->district_id,
        'ecclesiastical_region_id' => $church->ecclesiastical_region_id,
    ]);
    $person = Person::factory()->create();
    $treasurer = FunctionRole::where('slug', 'treasurer')->first();

    Livewire::test('admin.groups.editor', ['groupId' => $group->id])
        ->call('openMemberCreate')
        ->set('memberForm.person_id', $person->id)
        ->set('memberForm.function_id', $treasurer->id)
        ->call('saveMember')
        ->assertHasNoErrors();

    expect($group->assignments()->where('person_id', $person->id)->where('function_id', $treasurer->id)->exists())
        ->toBeTrue();
});

it('Group::members returns the active person collection', function () {
    $church = fullChainChurch();
    $group = Group::factory()->kind(GroupKind::Council)->create([
        'church_id' => $church->id,
        'district_id' => $church->district_id,
        'ecclesiastical_region_id' => $church->ecclesiastical_region_id,
    ]);
    $member = FunctionRole::where('slug', 'member')->first();

    $a = Person::factory()->create(['name' => 'Active Anna']);
    $b = Person::factory()->create(['name' => 'Ended Eddie']);

    PersonRoleAssignment::create([
        'person_id' => $a->id, 'function_id' => $member->id,
        'group_id' => $group->id, 'started_at' => now()->subYear()->toDateString(),
    ]);
    PersonRoleAssignment::create([
        'person_id' => $b->id, 'function_id' => $member->id,
        'group_id' => $group->id, 'started_at' => now()->subYears(2)->toDateString(),
        'ended_at' => now()->subDay()->toDateString(),
    ]);

    $names = $group->members()->pluck('name')->all();
    expect($names)->toContain('Active Anna');
    expect($names)->not->toContain('Ended Eddie');
});

it('Group::functionHolder returns the current holder of a function slug', function () {
    $church = fullChainChurch();
    $group = Group::factory()->kind(GroupKind::Ministry)->create([
        'church_id' => $church->id,
        'district_id' => $church->district_id,
        'ecclesiastical_region_id' => $church->ecclesiastical_region_id,
    ]);
    $lead = FunctionRole::where('slug', 'lead')->first();
    $leader = Person::factory()->create(['name' => 'Lead Lulu']);

    PersonRoleAssignment::create([
        'person_id' => $leader->id, 'function_id' => $lead->id,
        'group_id' => $group->id, 'started_at' => now()->subMonth()->toDateString(),
    ]);

    expect($group->functionHolder('lead')?->name)->toBe('Lead Lulu');
    expect($group->functionHolder('treasurer'))->toBeNull();
});

it('Person::groupsAsLeader returns groups where the person leads', function () {
    $church = fullChainChurch();
    $a = Group::factory()->kind(GroupKind::Council)->create([
        'name' => 'Council A',
        'church_id' => $church->id,
        'district_id' => $church->district_id,
        'ecclesiastical_region_id' => $church->ecclesiastical_region_id,
    ]);
    $b = Group::factory()->kind(GroupKind::Ministry)->create([
        'name' => 'Ministry B',
        'church_id' => $church->id,
        'district_id' => $church->district_id,
        'ecclesiastical_region_id' => $church->ecclesiastical_region_id,
    ]);
    $lead = FunctionRole::where('slug', 'lead')->first();
    $member = FunctionRole::where('slug', 'member')->first();
    $person = Person::factory()->create();

    PersonRoleAssignment::create([
        'person_id' => $person->id, 'function_id' => $lead->id, 'group_id' => $a->id,
        'started_at' => now()->subMonth()->toDateString(),
    ]);
    PersonRoleAssignment::create([
        'person_id' => $person->id, 'function_id' => $member->id, 'group_id' => $b->id,
        'started_at' => now()->subMonth()->toDateString(),
    ]);

    expect($person->groupsAsLeader()->pluck('name')->all())->toBe(['Council A']);
});
