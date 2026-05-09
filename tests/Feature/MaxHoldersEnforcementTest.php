<?php

use App\Models\Church;
use App\Models\FunctionRole;
use App\Models\Person;
use App\Models\PersonRoleAssignment;
use Database\Seeders\FunctionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(FunctionsSeeder::class);
});

it('blocks a second active Main Pastor on the same church (max_holders=1)', function () {
    $church = Church::factory()->create();
    $main = FunctionRole::where('slug', 'main_pastor')->first();
    $a = Person::factory()->create();
    $b = Person::factory()->create();

    PersonRoleAssignment::create([
        'person_id' => $a->id,
        'function_id' => $main->id,
        'church_id' => $church->id,
        'started_at' => now()->subYear()->toDateString(),
    ]);

    expect(fn () => PersonRoleAssignment::create([
        'person_id' => $b->id,
        'function_id' => $main->id,
        'church_id' => $church->id,
        'started_at' => now()->toDateString(),
    ]))->toThrow(ValidationException::class);
});

it('allows a second Main Pastor on a different church', function () {
    $churchA = Church::factory()->create();
    $churchB = Church::factory()->create();
    $main = FunctionRole::where('slug', 'main_pastor')->first();
    $person = Person::factory()->create();

    PersonRoleAssignment::create([
        'person_id' => $person->id, 'function_id' => $main->id,
        'church_id' => $churchA->id, 'started_at' => now()->subYear()->toDateString(),
    ]);

    $second = PersonRoleAssignment::create([
        'person_id' => $person->id, 'function_id' => $main->id,
        'church_id' => $churchB->id, 'started_at' => now()->toDateString(),
    ]);

    expect($second->id)->not->toBeNull();
});

it('allows a new Main Pastor after the previous one ends', function () {
    $church = Church::factory()->create();
    $main = FunctionRole::where('slug', 'main_pastor')->first();
    $a = Person::factory()->create();
    $b = Person::factory()->create();

    PersonRoleAssignment::create([
        'person_id' => $a->id, 'function_id' => $main->id, 'church_id' => $church->id,
        'started_at' => now()->subYears(2)->toDateString(),
        'ended_at' => now()->subDay()->toDateString(),
    ]);

    $second = PersonRoleAssignment::create([
        'person_id' => $b->id, 'function_id' => $main->id, 'church_id' => $church->id,
        'started_at' => now()->toDateString(),
    ]);

    expect($second->id)->not->toBeNull();
});

it('does NOT cap Auxiliary Pastor (max_holders is null)', function () {
    $church = Church::factory()->create();
    $aux = FunctionRole::where('slug', 'auxiliary_pastor')->first();
    $a = Person::factory()->create();
    $b = Person::factory()->create();

    PersonRoleAssignment::create([
        'person_id' => $a->id, 'function_id' => $aux->id, 'church_id' => $church->id,
        'started_at' => now()->subYear()->toDateString(),
    ]);

    $second = PersonRoleAssignment::create([
        'person_id' => $b->id, 'function_id' => $aux->id, 'church_id' => $church->id,
        'started_at' => now()->toDateString(),
    ]);

    expect($second->id)->not->toBeNull();
});
