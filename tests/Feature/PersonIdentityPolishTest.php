<?php

use App\Enums\MaritalStatus;
use App\Enums\PersonNature;
use App\Enums\PersonType;
use App\Models\Person;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $u = User::factory()->create();
    $u->assignRole('national_admin');
    $this->actingAs($u);
});

it('individual options exclude org natures and include Youth', function () {
    $opts = PersonNature::individualOptions();

    expect($opts)->toHaveKey(PersonNature::Youth->value);
    expect($opts)->toHaveKey(PersonNature::Member->value);
    expect($opts)->not->toHaveKey(PersonNature::Church->value);
    expect($opts)->not->toHaveKey(PersonNature::EcclesiasticalRegion->value);
});

it('organizational options include only org natures', function () {
    $opts = PersonNature::organizationalOptions();

    expect(array_keys($opts))->toEqualCanonicalizing([
        PersonNature::NationalHeadquarters->value,
        PersonNature::EcclesiasticalRegion->value,
        PersonNature::District->value,
        PersonNature::Church->value,
    ]);
});

it('rejects an individual nature on an organization person', function () {
    Livewire::test('admin.people.identity')
        ->set('form.person_type', PersonType::Organization->value)
        ->set('form.name', 'Some Org')
        ->set('form.natures', [PersonNature::Member->value]) // not allowed for org
        ->call('save')
        ->assertHasErrors(['form.natures.0']);
});

it('rejects an org nature on an individual person', function () {
    Livewire::test('admin.people.identity')
        ->set('form.person_type', PersonType::Individual->value)
        ->set('form.name', 'Some Human')
        ->set('form.natures', [PersonNature::Church->value]) // not allowed for individual
        ->call('save')
        ->assertHasErrors(['form.natures.0']);
});

it('clears stale natures when person_type flips from individual to organization', function () {
    Livewire::test('admin.people.identity')
        ->set('form.person_type', PersonType::Individual->value)
        ->set('form.natures', [PersonNature::Member->value, PersonNature::Pastor->value])
        ->set('form.person_type', PersonType::Organization->value)
        ->assertSet('form.natures', []);
});

it('persists marital_status as the MaritalStatus enum', function () {
    Livewire::test('admin.people.identity')
        ->set('form.person_type', PersonType::Individual->value)
        ->set('form.name', 'Married Mary')
        ->set('form.marital_status', MaritalStatus::Married->value)
        ->call('save')
        ->assertHasNoErrors();

    $person = Person::firstWhere('name', 'Married Mary');
    expect($person->marital_status)->toBe(MaritalStatus::Married);
});

it('rejects an unknown marital_status string', function () {
    Livewire::test('admin.people.identity')
        ->set('form.person_type', PersonType::Individual->value)
        ->set('form.name', 'Bad Status')
        ->set('form.marital_status', 'engaged') // not a real enum case
        ->call('save')
        ->assertHasErrors(['form.marital_status']);
});

it('rejects marital_status on an organization person', function () {
    Livewire::test('admin.people.identity')
        ->set('form.person_type', PersonType::Organization->value)
        ->set('form.name', 'Org With Status')
        ->set('form.marital_status', MaritalStatus::Single->value)
        ->call('save')
        ->assertHasErrors(['form.marital_status']);
});

it('accepts the Youth nature on an individual person', function () {
    Livewire::test('admin.people.identity')
        ->set('form.person_type', PersonType::Individual->value)
        ->set('form.name', 'Young Yara')
        ->set('form.natures', [PersonNature::Youth->value])
        ->call('save')
        ->assertHasNoErrors();

    $person = Person::firstWhere('name', 'Young Yara');
    expect($person->natures)->toBe([PersonNature::Youth->value]);
});

it('accepts a date interpreted as foundation date for organizations', function () {
    Livewire::test('admin.people.identity')
        ->set('form.person_type', PersonType::Organization->value)
        ->set('form.name', 'Old Org')
        ->set('form.birthdate', '1930-05-10')
        ->set('form.natures', [PersonNature::EcclesiasticalRegion->value])
        ->call('save')
        ->assertHasNoErrors();

    $org = Person::firstWhere('name', 'Old Org');
    expect($org->birthdate?->format('Y-m-d'))->toBe('1930-05-10');
});
