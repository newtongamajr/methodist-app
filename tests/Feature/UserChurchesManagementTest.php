<?php

use App\Models\Church;
use App\Models\Pivots\ChurchUser;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $u = User::factory()->create();
    $u->assignRole('national_admin');
    $this->actingAs($u);
});

it('attaches a church via the listbox + Add button (first one becomes primary)', function () {
    $target = User::factory()->create();
    $church = Church::factory()->create();

    Livewire::test('admin.users.churches', ['userId' => $target->id])
        ->set('selectedChurchId', $church->id)
        ->call('attach');

    $target->refresh();
    expect($target->churches->pluck('id')->all())->toBe([$church->id]);
    expect($target->churches->first()->pivot->is_primary)->toBeTrue();
    expect($target->person->managing_church_id)->toBe($church->id);
});

it('hides already-attached churches from the selectable list', function () {
    $target = User::factory()->create();
    $a = Church::factory()->create();
    $b = Church::factory()->create();
    $target->churches()->attach($a->id, ['is_primary' => true]);

    $component = Livewire::test('admin.users.churches', ['userId' => $target->id]);
    $ids = $component->instance()->selectableChurches->pluck('id')->all();

    expect($ids)->toContain($b->id);
    expect($ids)->not->toContain($a->id);
});

it('promoting a church to primary demotes any other primary via the observer', function () {
    $target = User::factory()->create();
    $a = Church::factory()->create();
    $b = Church::factory()->create();
    $target->churches()->attach($a->id, ['is_primary' => true]);
    $target->churches()->attach($b->id, ['is_primary' => false]);

    Livewire::test('admin.users.churches', ['userId' => $target->id])
        ->call('setPrimary', $b->id);

    $target->refresh();
    $primaries = $target->churches->where('pivot.is_primary', true)->pluck('id')->all();

    expect($primaries)->toBe([$b->id]); // a got demoted
});

it('detaching the primary promotes the next church automatically', function () {
    $target = User::factory()->create();
    $a = Church::factory()->create(['name' => 'A']);
    $b = Church::factory()->create(['name' => 'B']);
    $target->churches()->attach($a->id, ['is_primary' => true]);
    $target->churches()->attach($b->id, ['is_primary' => false]);

    Livewire::test('admin.users.churches', ['userId' => $target->id])
        ->call('detach', $a->id);

    $target->refresh();
    expect($target->churches->pluck('id')->all())->toBe([$b->id]);
    expect($target->churches->first()->pivot->is_primary)->toBeTrue();
    expect($target->person->managing_church_id)->toBe($b->id);
});

it('detaching the only church clears managing_church_id', function () {
    $target = User::factory()->create();
    $church = Church::factory()->create();
    $target->churches()->attach($church->id, ['is_primary' => true]);
    $target->person->update(['managing_church_id' => $church->id]);

    Livewire::test('admin.users.churches', ['userId' => $target->id])
        ->call('detach', $church->id);

    $target->refresh();
    expect($target->churches)->toHaveCount(0);
    expect($target->person->managing_church_id)->toBeNull();
});

it('ChurchUser observer enforces single-primary directly on pivot saves', function () {
    $target = User::factory()->create();
    $a = Church::factory()->create();
    $b = Church::factory()->create();

    $target->churches()->attach($a->id, ['is_primary' => true]);
    // A direct pivot insert with is_primary=true should demote the previous primary.
    $target->churches()->attach($b->id, ['is_primary' => true]);

    $primaries = ChurchUser::query()
        ->where('user_id', $target->id)
        ->where('is_primary', true)
        ->pluck('church_id')
        ->all();

    expect($primaries)->toBe([$b->id]);
});
