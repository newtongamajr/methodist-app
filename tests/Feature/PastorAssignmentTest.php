<?php

use App\Enums\ChurchType;
use App\Enums\PersonNature;
use App\Models\Church;
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

function pastorPerson(string $name): Person
{
    return Person::factory()->create([
        'name' => $name,
        'natures' => [PersonNature::Pastor->value],
    ]);
}

it('reports only currently active pastor assignments for a church', function () {
    $church = Church::factory()->create();
    $main = FunctionRole::where('slug', 'main_pastor')->first();
    $aux = FunctionRole::where('slug', 'auxiliary_pastor')->first();
    $sem = FunctionRole::where('slug', 'seminarist')->first();

    $active = pastorPerson('Pr. Active');
    $past = pastorPerson('Pr. Past');
    $future = pastorPerson('Pr. Future');

    PersonRoleAssignment::create([
        'person_id' => $active->id, 'function_id' => $main->id, 'church_id' => $church->id,
        'started_at' => now()->subYear()->toDateString(), 'ended_at' => null,
    ]);
    PersonRoleAssignment::create([
        'person_id' => $past->id, 'function_id' => $aux->id, 'church_id' => $church->id,
        'started_at' => now()->subYears(3)->toDateString(),
        'ended_at' => now()->subYear()->toDateString(),
    ]);
    PersonRoleAssignment::create([
        'person_id' => $future->id, 'function_id' => $sem->id, 'church_id' => $church->id,
        'started_at' => now()->addMonth()->toDateString(),
    ]);

    $today = now()->toDateString();
    $names = $church->pastorAssignments()
        ->with('person')
        ->where(fn ($q) => $q->whereNull('started_at')->orWhere('started_at', '<=', $today))
        ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>=', $today))
        ->get()->map(fn ($a) => $a->person->name)->all();

    expect($names)->toContain('Pr. Active');
    expect($names)->not->toContain('Pr. Past');
    expect($names)->not->toContain('Pr. Future');
});

it('lets a pastor serve multiple churches simultaneously', function () {
    $a = Church::factory()->create();
    $b = Church::factory()->create();
    $main = FunctionRole::where('slug', 'main_pastor')->first();
    $sem = FunctionRole::where('slug', 'seminarist')->first();
    $pastor = pastorPerson('Pr. Multi');

    PersonRoleAssignment::create([
        'person_id' => $pastor->id, 'function_id' => $main->id, 'church_id' => $a->id,
        'started_at' => now()->subMonths(6)->toDateString(),
    ]);
    PersonRoleAssignment::create([
        'person_id' => $pastor->id, 'function_id' => $sem->id, 'church_id' => $b->id,
        'started_at' => now()->subMonth()->toDateString(),
    ]);

    expect($pastor->roleAssignments()->count())->toBe(2);
});

it('preserves history when a pastor moves from one church to another', function () {
    $oldChurch = Church::factory()->create();
    $newChurch = Church::factory()->create();
    $main = FunctionRole::where('slug', 'main_pastor')->first();
    $pastor = pastorPerson('Pr. Mover');

    $assignment = PersonRoleAssignment::create([
        'person_id' => $pastor->id, 'function_id' => $main->id, 'church_id' => $oldChurch->id,
        'started_at' => now()->subYear()->toDateString(),
    ]);

    $assignment->update(['ended_at' => now()->subDay()->toDateString()]);

    PersonRoleAssignment::create([
        'person_id' => $pastor->id, 'function_id' => $main->id, 'church_id' => $newChurch->id,
        'started_at' => now()->toDateString(),
    ]);

    expect($oldChurch->pastorAssignments()->count())->toBe(1);
    $today = now()->toDateString();
    expect(
        $oldChurch->pastorAssignments()
            ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>=', $today))
            ->count(),
    )->toBe(0);
    expect(
        $newChurch->pastorAssignments()
            ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>=', $today))
            ->count(),
    )->toBe(1);
});

it('admin editor creates a brand-new pastor and a current assignment', function () {
    $super = User::factory()->create();
    $super->assignRole('national_admin');
    $church = Church::factory()->create();
    $sem = FunctionRole::where('slug', 'seminarist')->first();
    $this->actingAs($super);

    Livewire::test('admin.churches.pastors.editor', ['churchId' => $church->id])
        ->set('form.pastorMode', 'new')
        ->set('form.person_name', 'Pr. Novo')
        ->set('form.person_email', 'novo@pastores.test')
        ->set('form.function_id', $sem->id)
        ->set('form.start_date', now()->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    $pastor = Person::query()
        ->whereJsonContains('natures', PersonNature::Pastor->value)
        ->where('name', 'Pr. Novo')
        ->first();
    expect($pastor)->not->toBeNull();
    expect(
        $church->pastorAssignments()->where('person_id', $pastor->id)->exists(),
    )->toBeTrue();
});

it('casts ChurchType properly', function () {
    $church = Church::factory()->create(['type' => ChurchType::MissionaryPoint->value]);
    expect($church->type)->toBe(ChurchType::MissionaryPoint);
});
