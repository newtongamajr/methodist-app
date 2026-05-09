<?php

use App\Models\Church;
use App\Models\Person;
use App\Models\User;

it('attaches a user to a church via the pivot and the Person managing church', function () {
    $church = Church::factory()->create();
    $person = Person::factory()->create(['managing_church_id' => $church->id]);
    $user = User::factory()->withPerson($person)->create();

    $user->churches()->attach($church->id, ['is_primary' => true]);

    expect($user->person->managing_church_id)->toBe($church->id);
    expect($user->churches->pluck('id')->all())->toContain($church->id);
    expect($church->fresh()->members->pluck('id')->all())->toContain($user->id);
});

it('supports a user belonging to multiple churches', function () {
    $a = Church::factory()->create();
    $b = Church::factory()->create();
    $person = Person::factory()->create(['managing_church_id' => $a->id]);
    $user = User::factory()->withPerson($person)->create();

    $user->churches()->attach([
        $a->id => ['is_primary' => true],
        $b->id => ['is_primary' => false],
    ]);

    expect($user->churches()->count())->toBe(2);
    expect($user->churches()->wherePivot('is_primary', true)->first()->id)->toBe($a->id);
});
