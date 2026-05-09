<?php

use App\Models\Church;
use App\Models\District;
use App\Models\EcclesiasticalRegion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $u = User::factory()->create();
    $u->assignRole('national_admin');
    $this->actingAs($u);
});

it('keeps district nullable when the chosen region has no districts', function () {
    $region = EcclesiasticalRegion::factory()->create();

    Livewire::test('admin.churches.editor')
        ->set('form.ecclesiastical_region_id', $region->id)
        ->set('form.type', 'church')
        ->set('form.name', 'Igreja Sem Distrito')
        ->set('form.master_name', 'Master')
        ->set('form.master_email', 'master@nodist.test')
        ->set('form.master_password', 'secret-password')
        ->call('save')
        ->assertHasNoErrors();

    expect(Church::firstWhere('name', 'Igreja Sem Distrito')->district_id)->toBeNull();
});

it('rejects a save without district when the chosen region has districts', function () {
    $region = EcclesiasticalRegion::factory()->create();
    District::factory()->create(['ecclesiastical_region_id' => $region->id]);

    Livewire::test('admin.churches.editor')
        ->set('form.ecclesiastical_region_id', $region->id)
        ->set('form.type', 'church')
        ->set('form.name', 'Igreja Sem District')
        ->set('form.master_name', 'Master')
        ->set('form.master_email', 'master@need.test')
        ->set('form.master_password', 'secret-password')
        ->call('save')
        ->assertHasErrors(['form.district_id']);
});

it('accepts a save when the chosen region has districts and one is picked', function () {
    $region = EcclesiasticalRegion::factory()->create();
    $district = District::factory()->create(['ecclesiastical_region_id' => $region->id]);

    Livewire::test('admin.churches.editor')
        ->set('form.ecclesiastical_region_id', $region->id)
        ->set('form.district_id', $district->id)
        ->set('form.type', 'church')
        ->set('form.name', 'Igreja Com District')
        ->set('form.master_name', 'Master')
        ->set('form.master_email', 'master@have.test')
        ->set('form.master_password', 'secret-password')
        ->call('save')
        ->assertHasNoErrors();

    $church = Church::firstWhere('name', 'Igreja Com District');
    expect($church->district_id)->toBe($district->id);
});
