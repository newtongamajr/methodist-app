<?php

use App\Enums\PersonNature;
use App\Enums\PersonType;
use App\Models\Church;
use App\Models\Person;
use App\Models\PersonAddress;
use App\Models\PersonContact;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsSuperPerson(): User
{
    $u = User::factory()->create();
    $u->assignRole('national_admin');
    test()->actingAs($u);

    return $u;
}

it('lists every Person on the index, regardless of nature', function () {
    actingAsSuperPerson();
    Person::factory()->nature(PersonNature::Member)->create(['name' => 'Member Mike']);
    Person::factory()->nature(PersonNature::Pastor)->create(['name' => 'Pastor Pat']);

    $this->get(route('admin.people.index'))
        ->assertOk()
        ->assertSee('Member Mike')
        ->assertSee('Pastor Pat');
});

it('filters the index by nature', function () {
    actingAsSuperPerson();
    Person::factory()->nature(PersonNature::Member)->create(['name' => 'Member Mike']);
    Person::factory()->nature(PersonNature::Pastor)->create(['name' => 'Pastor Pat']);

    Livewire::test('admin.people.index')
        ->set('natureFilter', PersonNature::Pastor->value)
        ->assertSee('Pastor Pat')
        ->assertDontSee('Member Mike');
});

it('searches by name, preferred name, and tax id', function () {
    actingAsSuperPerson();
    Person::factory()->create(['name' => 'João Silva', 'preferred_name' => 'Joca']);
    Person::factory()->create(['name' => 'Maria Souza', 'tax_id' => '11144477735', 'tax_id_type' => 'cpf']);
    Person::factory()->create(['name' => 'Outsider Olaf']);

    Livewire::test('admin.people.index')
        ->set('search', 'Joca')
        ->assertSee('João Silva')
        ->assertDontSee('Outsider Olaf');

    Livewire::test('admin.people.index')
        ->set('search', '11144477735')
        ->assertSee('Maria Souza')
        ->assertDontSee('Outsider Olaf');
});

it('refuses to delete a Person that has a User account hanging off it', function () {
    actingAsSuperPerson();
    $u = User::factory()->create();

    Livewire::test('admin.people.index')
        ->call('deletePerson', $u->person_id)
        ->assertHasErrors('person');

    expect(Person::find($u->person_id))->not->toBeNull();
});

it('creates a Person via the Identity tab and persists Identity fields', function () {
    actingAsSuperPerson();
    $church = Church::factory()->create();

    Livewire::test('admin.people.identity')
        ->set('form.person_type', PersonType::Individual->value)
        ->set('form.name', 'New Person')
        ->set('form.birthdate', '1990-05-10')
        ->set('form.natures', [PersonNature::Member->value])
        ->set('form.managing_church_id', $church->id)
        ->call('save')
        ->assertHasNoErrors();

    $person = Person::firstWhere('name', 'New Person');
    expect($person)->not->toBeNull();
    expect($person->birthdate?->format('Y-m-d'))->toBe('1990-05-10');
    expect($person->natures)->toBe([PersonNature::Member->value]);
    expect($person->managing_church_id)->toBe($church->id);
});

it('rejects an Identity save with an invalid CPF (observer guard)', function () {
    actingAsSuperPerson();

    Livewire::test('admin.people.identity')
        ->set('form.person_type', PersonType::Individual->value)
        ->set('form.name', 'Invalid CPF Person')
        ->set('form.tax_id_type', 'cpf')
        ->set('form.tax_id', '11111111111') // checksum will fail
        ->call('save')
        ->assertHasErrors();

    expect(Person::where('name', 'Invalid CPF Person')->exists())->toBeFalse();
});

it('adds a contact, demotes other primaries of the same type', function () {
    actingAsSuperPerson();
    $person = Person::factory()->create();
    PersonContact::create([
        'person_id' => $person->id,
        'type' => 'phone',
        'value' => '(11) 11111-1111',
        'is_primary' => true,
    ]);

    Livewire::test('admin.people.contacts', ['personId' => $person->id])
        ->call('openCreate')
        ->set('form.type', 'phone')
        ->set('form.value', '(22) 22222-2222')
        ->set('form.is_primary', true)
        ->call('save')
        ->assertHasNoErrors();

    $contacts = $person->contacts()->orderBy('value')->get();
    expect($contacts)->toHaveCount(2);
    expect($contacts->where('value', '(11) 11111-1111')->first()->is_primary)->toBeFalse();
    expect($contacts->where('value', '(22) 22222-2222')->first()->is_primary)->toBeTrue();
});

it('adds an address and enforces single primary', function () {
    actingAsSuperPerson();
    $person = Person::factory()->create();
    PersonAddress::create([
        'person_id' => $person->id,
        'label' => 'Home',
        'street' => 'Rua Antiga',
        'country' => 'BR',
        'is_primary' => true,
    ]);

    Livewire::test('admin.people.addresses', ['personId' => $person->id])
        ->call('openCreate')
        ->set('form.label', 'Work')
        ->set('form.street', 'Av. Nova')
        ->set('form.country', 'BR')
        ->set('form.is_primary', true)
        ->call('save')
        ->assertHasNoErrors();

    $addresses = $person->addresses()->orderBy('label')->get();
    expect($addresses)->toHaveCount(2);
    expect($addresses->where('label', 'Home')->first()->is_primary)->toBeFalse();
    expect($addresses->where('label', 'Work')->first()->is_primary)->toBeTrue();
});

it('creates a document row even without a file attached', function () {
    actingAsSuperPerson();
    $person = Person::factory()->create();

    Livewire::test('admin.people.documents', ['personId' => $person->id])
        ->call('openCreate')
        ->set('form.document_type', 'RG')
        ->set('form.number', '12.345.678-9')
        ->set('form.issuer', 'SSP/SP')
        ->set('form.issued_at', '2010-03-15')
        ->call('save')
        ->assertHasNoErrors();

    expect($person->documents()->where('document_type', 'RG')->exists())->toBeTrue();
});

it('blocks regular users from reaching /admin/people', function () {
    $u = User::factory()->create();
    $u->assignRole('user');
    $this->actingAs($u)->get(route('admin.people.index'))->assertForbidden();
});
