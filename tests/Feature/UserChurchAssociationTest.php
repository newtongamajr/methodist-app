<?php

use App\Models\Church;
use App\Models\User;

it('attaches a user to a church via the pivot and primary FK', function () {
    $church = Church::factory()->create();
    $user = User::factory()->create(['church_id' => $church->id]);

    $user->churches()->attach($church->id, ['is_primary' => true]);

    expect($user->primaryChurch->id)->toBe($church->id);
    expect($user->churches->pluck('id')->all())->toContain($church->id);
    expect($church->fresh()->members->pluck('id')->all())->toContain($user->id);
});

it('supports a user belonging to multiple churches', function () {
    $a = Church::factory()->create();
    $b = Church::factory()->create();
    $user = User::factory()->create(['church_id' => $a->id]);

    $user->churches()->attach([
        $a->id => ['is_primary' => true],
        $b->id => ['is_primary' => false],
    ]);

    expect($user->churches()->count())->toBe(2);
    expect($user->churches()->wherePivot('is_primary', true)->first()->id)->toBe($a->id);
});
