<?php

use App\Enums\PersonNature;
use App\Models\Church;
use App\Models\District;
use App\Models\EcclesiasticalRegion;
use App\Models\Person;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// PersonObserver name-sync ----------------------------------------------------

it('syncs Person.name back to the linked Church row when the Person is updated', function () {
    $region = EcclesiasticalRegion::factory()->create();
    $district = District::factory()->create(['ecclesiastical_region_id' => $region->id]);
    $church = Church::factory()->create([
        'name' => 'Original Name',
        'ecclesiastical_region_id' => $region->id,
        'district_id' => $district->id,
    ]);

    $church->person->update(['name' => 'Renamed Church']);

    expect($church->fresh()->name)->toBe('Renamed Church');
});

it('syncs Person.name back to the linked Region row', function () {
    $region = EcclesiasticalRegion::factory()->create(['name' => 'Original Region']);
    $region->person->update(['name' => 'Renamed Region']);

    expect($region->fresh()->name)->toBe('Renamed Region');
});

it('syncs Person.name back to the linked District row', function () {
    $district = District::factory()->create(['name' => 'Original District']);
    $district->person->update(['name' => 'Renamed District']);

    expect($district->fresh()->name)->toBe('Renamed District');
});

it('does not touch any org row when an individual Person\'s name changes', function () {
    $region = EcclesiasticalRegion::factory()->create(['name' => 'Stable Region']);
    $person = Person::factory()->create(['name' => 'Original Person']);
    $person->update(['name' => 'Renamed Person']);

    expect($region->fresh()->name)->toBe('Stable Region');
});

// promote-minors command ------------------------------------------------------

it('promotes children → teenager based on age', function () {
    $kid = Person::factory()->create([
        'birthdate' => now()->subYears(12)->subDay()->toDateString(), // just turned 12
        'natures' => [PersonNature::Child->value],
    ]);

    $this->artisan('person:promote-minors')->assertSuccessful();

    $kid->refresh();
    expect($kid->natures)->toBe([PersonNature::Teenager->value]);
});

it('promotes teenager → adult (drops the Teenager nature, no auto-Member)', function () {
    $teen = Person::factory()->create([
        'birthdate' => now()->subYears(18)->subDay()->toDateString(), // just turned 18
        'natures' => [PersonNature::Teenager->value],
    ]);

    $this->artisan('person:promote-minors')->assertSuccessful();

    $teen->refresh();
    expect($teen->natures)->toBe([]); // Teenager dropped, Member NOT auto-added
});

it('leaves children whose 12th birthday has not arrived yet', function () {
    $kid = Person::factory()->create([
        'birthdate' => now()->subYears(11)->toDateString(), // turned 11 today
        'natures' => [PersonNature::Child->value],
    ]);

    $this->artisan('person:promote-minors')->assertSuccessful();

    $kid->refresh();
    expect($kid->natures)->toBe([PersonNature::Child->value]);
});

it('--dry-run reports counts without changing data', function () {
    $kid = Person::factory()->create([
        'birthdate' => now()->subYears(13)->toDateString(),
        'natures' => [PersonNature::Child->value],
    ]);

    $this->artisan('person:promote-minors', ['--dry-run' => true])
        ->expectsOutputToContain('Would promote 1 child(ren) → teenager')
        ->assertSuccessful();

    $kid->refresh();
    expect($kid->natures)->toBe([PersonNature::Child->value]); // still Child
});

it('preserves other natures alongside the promoted nature', function () {
    $kid = Person::factory()->create([
        'birthdate' => now()->subYears(13)->toDateString(),
        'natures' => [PersonNature::Child->value, PersonNature::Member->value],
    ]);

    $this->artisan('person:promote-minors')->assertSuccessful();

    $kid->refresh();
    expect($kid->natures)->toEqualCanonicalizing([PersonNature::Member->value, PersonNature::Teenager->value]);
});
