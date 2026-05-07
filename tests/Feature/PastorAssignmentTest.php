<?php

use App\Enums\ChurchType;
use App\Enums\PastorRole;
use App\Models\Church;
use App\Models\Pastor;
use App\Models\PastorAssignment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('reports only currently active assignments via Church::currentPastors', function () {
    $church = Church::factory()->create();
    $pastorActive = Pastor::factory()->create(['name' => 'Pr. Active']);
    $pastorPast = Pastor::factory()->create(['name' => 'Pr. Past']);
    $pastorFuture = Pastor::factory()->create(['name' => 'Pr. Future']);

    PastorAssignment::factory()->create([
        'pastor_id' => $pastorActive->id, 'church_id' => $church->id,
        'role' => PastorRole::Main->value,
        'start_date' => now()->subYear()->toDateString(), 'end_date' => null,
    ]);
    PastorAssignment::factory()->create([
        'pastor_id' => $pastorPast->id, 'church_id' => $church->id,
        'role' => PastorRole::Auxiliary->value,
        'start_date' => now()->subYears(3)->toDateString(),
        'end_date' => now()->subYear()->toDateString(),
    ]);
    PastorAssignment::factory()->create([
        'pastor_id' => $pastorFuture->id, 'church_id' => $church->id,
        'role' => PastorRole::Seminarist->value,
        'start_date' => now()->addMonth()->toDateString(),
    ]);

    $names = $church->currentPastors()->pluck('name')->all();
    expect($names)->toContain('Pr. Active');
    expect($names)->not->toContain('Pr. Past');
    expect($names)->not->toContain('Pr. Future');
});

it('lets a pastor serve multiple churches simultaneously', function () {
    $a = Church::factory()->create();
    $b = Church::factory()->create();
    $pastor = Pastor::factory()->create();

    PastorAssignment::factory()->create([
        'pastor_id' => $pastor->id, 'church_id' => $a->id,
        'role' => PastorRole::Main->value,
        'start_date' => now()->subMonths(6)->toDateString(),
    ]);
    PastorAssignment::factory()->create([
        'pastor_id' => $pastor->id, 'church_id' => $b->id,
        'role' => PastorRole::Seminarist->value,
        'start_date' => now()->subMonth()->toDateString(),
    ]);

    expect($pastor->churches()->count())->toBe(2);
});

it('preserves history when a pastor moves from one church to another', function () {
    $oldChurch = Church::factory()->create();
    $newChurch = Church::factory()->create();
    $pastor = Pastor::factory()->create();

    $assignment = PastorAssignment::factory()->create([
        'pastor_id' => $pastor->id, 'church_id' => $oldChurch->id,
        'role' => PastorRole::Main->value,
        'start_date' => now()->subYear()->toDateString(),
    ]);

    // End the old assignment yesterday so the pastor cleanly transitions today.
    $assignment->update(['end_date' => now()->subDay()->toDateString()]);

    PastorAssignment::factory()->create([
        'pastor_id' => $pastor->id, 'church_id' => $newChurch->id,
        'role' => PastorRole::Main->value,
        'start_date' => now()->toDateString(),
    ]);

    expect($oldChurch->pastors()->count())->toBe(1); // history preserved
    expect($oldChurch->currentPastors()->count())->toBe(0);
    expect($newChurch->currentPastors()->count())->toBe(1);
});

it('admin editor creates a brand-new pastor and a current assignment', function () {
    $super = User::factory()->create();
    $super->assignRole('global_manager');
    $church = Church::factory()->create();
    $this->actingAs($super);

    Livewire::test('admin.churches.pastors.editor', ['churchId' => $church->id])
        ->set('pastorMode', 'new')
        ->set('pastor_name', 'Pr. Novo')
        ->set('pastor_email', 'novo@pastores.test')
        ->set('role', 'seminarist')
        ->set('start_date', now()->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    $pastor = Pastor::firstWhere('email', 'novo@pastores.test');
    expect($pastor)->not->toBeNull();
    expect($church->currentPastors()->where('pastors.id', $pastor->id)->exists())->toBeTrue();
});

it('casts ChurchType properly', function () {
    $church = Church::factory()->create(['type' => ChurchType::MissionaryPoint->value]);
    expect($church->type)->toBe(ChurchType::MissionaryPoint);
});
