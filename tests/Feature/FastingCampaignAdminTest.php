<?php

use App\Models\FastingCampaign;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lets a global manager create a campaign', function () {
    $super = User::factory()->create();
    $super->assignRole('global_manager');
    $this->actingAs($super);

    Livewire::test('admin.fasting-campaigns.editor')
        ->set('form.name', 'Test Campaign')
        ->set('form.description', 'Three weeks of fasting')
        ->set('form.start_date', '2026-05-04')
        ->set('form.end_date', '2026-05-24')
        ->set('form.types', ['h24', 'h12', 'partial'])
        ->set('form.restrictions', ['meat', 'sweets'])
        ->set('form.is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    $created = FastingCampaign::firstWhere('name', 'Test Campaign');
    expect($created)->not->toBeNull();
    expect($created->slug)->toBe('test-campaign');
    expect($created->types)->toEqualCanonicalizing(['h24', 'h12', 'partial']);
    expect($created->restrictions)->toEqualCanonicalizing(['meat', 'sweets']);
});

it('rejects a campaign with end before start', function () {
    $super = User::factory()->create();
    $super->assignRole('global_manager');
    $this->actingAs($super);

    Livewire::test('admin.fasting-campaigns.editor')
        ->set('form.name', 'Backwards')
        ->set('form.start_date', '2026-05-10')
        ->set('form.end_date', '2026-05-01')
        ->set('form.types', ['h12'])
        ->set('form.restrictions', [])
        ->call('save')
        ->assertHasErrors('form.end_date');
});

it('blocks regular users from the campaign CRUD', function () {
    $regular = User::factory()->create();
    $regular->assignRole('user');
    $this->actingAs($regular)
        ->get(route('admin.fasting-campaigns.index'))
        ->assertForbidden();
});
