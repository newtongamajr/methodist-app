<?php

use App\Models\Church;
use App\Models\District;
use App\Models\EcclesiasticalRegion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsSuperDistrict(): User
{
    $u = User::factory()->create();
    $u->assignRole('national_admin');
    test()->actingAs($u);

    return $u;
}

it('only church.manage users can reach the districts CRUD', function () {
    $regular = User::factory()->create();
    $regular->assignRole('user');

    $this->actingAs($regular)->get(route('admin.districts.index'))->assertForbidden();
    $this->actingAs(actingAsSuperDistrict())->get(route('admin.districts.index'))->assertOk();
});

it('lists every district with the region label', function () {
    actingAsSuperDistrict();
    $r = EcclesiasticalRegion::factory()->create(['code' => 'RX', 'name' => 'Sample Region']);
    District::factory()->create(['ecclesiastical_region_id' => $r->id, 'name' => 'Norte District']);
    District::factory()->create(['ecclesiastical_region_id' => $r->id, 'name' => 'Sul District']);

    $this->get(route('admin.districts.index'))
        ->assertOk()
        ->assertSee('Norte District')
        ->assertSee('Sul District')
        ->assertSee('RX');
});

it('filters the index by region via ?region= query param', function () {
    actingAsSuperDistrict();
    $a = EcclesiasticalRegion::factory()->create();
    $b = EcclesiasticalRegion::factory()->create();
    District::factory()->create(['ecclesiastical_region_id' => $a->id, 'name' => 'Inside A']);
    District::factory()->create(['ecclesiastical_region_id' => $b->id, 'name' => 'Inside B']);

    Livewire::test('admin.districts.index', ['region' => $a->id])
        ->assertSee('Inside A')
        ->assertDontSee('Inside B');
});

it('creates a district with auto-generated slug when blank', function () {
    actingAsSuperDistrict();
    $region = EcclesiasticalRegion::factory()->create();

    Livewire::test('admin.districts.editor')
        ->set('form.ecclesiastical_region_id', $region->id)
        ->set('form.name', 'Brand New District')
        ->call('save')
        ->assertHasNoErrors();

    $district = District::firstWhere('name', 'Brand New District');
    expect($district)->not->toBeNull();
    expect($district->slug)->toBe('brand-new-district');
    expect($district->ecclesiastical_region_id)->toBe($region->id);
});

it('respects an explicitly-typed slug over the auto-generator', function () {
    actingAsSuperDistrict();
    $region = EcclesiasticalRegion::factory()->create();

    Livewire::test('admin.districts.editor')
        ->set('form.ecclesiastical_region_id', $region->id)
        ->set('form.name', 'Distrito Demo')
        ->set('form.slug', 'custom-slug')
        ->call('save')
        ->assertHasNoErrors();

    expect(District::where('slug', 'custom-slug')->exists())->toBeTrue();
});

it('rejects a duplicate slug within the same region', function () {
    actingAsSuperDistrict();
    $region = EcclesiasticalRegion::factory()->create();
    District::factory()->create(['ecclesiastical_region_id' => $region->id, 'slug' => 'norte']);

    Livewire::test('admin.districts.editor')
        ->set('form.ecclesiastical_region_id', $region->id)
        ->set('form.name', 'Outro Norte')
        ->set('form.slug', 'norte')
        ->call('save')
        ->assertHasErrors(['form.slug']);
});

it('allows the same slug across different regions', function () {
    actingAsSuperDistrict();
    $a = EcclesiasticalRegion::factory()->create();
    $b = EcclesiasticalRegion::factory()->create();
    District::factory()->create(['ecclesiastical_region_id' => $a->id, 'slug' => 'norte']);

    Livewire::test('admin.districts.editor')
        ->set('form.ecclesiastical_region_id', $b->id)
        ->set('form.name', 'Norte B')
        ->set('form.slug', 'norte')
        ->call('save')
        ->assertHasNoErrors();
});

it('updates an existing district', function () {
    actingAsSuperDistrict();
    $district = District::factory()->create(['name' => 'Old Name']);

    Livewire::test('admin.districts.editor', ['districtId' => $district->id])
        ->set('form.name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($district->fresh()->name)->toBe('New Name');
});

it('refuses to delete a district that still has churches', function () {
    actingAsSuperDistrict();
    $district = District::factory()->create();
    Church::factory()->create([
        'ecclesiastical_region_id' => $district->ecclesiastical_region_id,
        'district_id' => $district->id,
    ]);

    Livewire::test('admin.districts.index')
        ->call('delete', $district->id)
        ->assertHasErrors('district');

    expect(District::find($district->id))->not->toBeNull();
});

it('deletes a district with no attached churches', function () {
    actingAsSuperDistrict();
    $district = District::factory()->create();

    Livewire::test('admin.districts.index')
        ->call('delete', $district->id);

    expect(District::find($district->id))->toBeNull();
});

it('keeps existing churches when their district is set to null', function () {
    actingAsSuperDistrict();
    $district = District::factory()->create();
    $church = Church::factory()->create([
        'ecclesiastical_region_id' => $district->ecclesiastical_region_id,
        'district_id' => $district->id,
    ]);

    // Simulate the FK nullOnDelete by detaching first, then deleting.
    $church->update(['district_id' => null]);
    Livewire::test('admin.districts.index')->call('delete', $district->id);

    expect(District::find($district->id))->toBeNull();
    expect(Church::find($church->id))->not->toBeNull();
});
