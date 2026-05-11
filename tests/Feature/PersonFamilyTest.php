<?php

use App\Enums\PersonRelationshipType;
use App\Models\Person;
use App\Models\PersonRelationship;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsSuperFamily(): User
{
    $u = User::factory()->create();
    $u->assignRole('national_admin');
    test()->actingAs($u);

    return $u;
}

it('returns parents and children regardless of which side defined the row', function () {
    $parent = Person::factory()->create(['name' => 'Mom']);
    $child = Person::factory()->create(['name' => 'Kid']);

    PersonRelationship::create([
        'person_id' => $parent->id,
        'related_person_id' => $child->id,
        'relationship_type' => PersonRelationshipType::ParentOf->value,
    ]);

    expect($parent->fresh()->children()->pluck('name')->all())->toBe(['Kid']);
    expect($child->fresh()->parents()->pluck('name')->all())->toBe(['Mom']);
});

it('returns siblings: children of any parent excluding me', function () {
    $mom = Person::factory()->create(['name' => 'Mom']);
    $a = Person::factory()->create(['name' => 'Sibling A']);
    $b = Person::factory()->create(['name' => 'Sibling B']);
    $me = Person::factory()->create(['name' => 'Me']);

    foreach ([$a, $b, $me] as $kid) {
        PersonRelationship::create([
            'person_id' => $mom->id,
            'related_person_id' => $kid->id,
            'relationship_type' => PersonRelationshipType::ParentOf->value,
        ]);
    }

    $siblingNames = $me->siblings()->pluck('name')->sort()->values()->all();
    expect($siblingNames)->toBe(['Sibling A', 'Sibling B']);
});

it('returns grandparents and grandchildren via two-hop traversal', function () {
    $grandma = Person::factory()->create(['name' => 'Grandma']);
    $mom = Person::factory()->create(['name' => 'Mom']);
    $kid = Person::factory()->create(['name' => 'Kid']);

    PersonRelationship::create([
        'person_id' => $grandma->id, 'related_person_id' => $mom->id,
        'relationship_type' => PersonRelationshipType::ParentOf->value,
    ]);
    PersonRelationship::create([
        'person_id' => $mom->id, 'related_person_id' => $kid->id,
        'relationship_type' => PersonRelationshipType::ParentOf->value,
    ]);

    expect($kid->fresh()->grandparents()->pluck('name')->all())->toBe(['Grandma']);
    expect($grandma->fresh()->grandchildren()->pluck('name')->all())->toBe(['Kid']);
});

it('returns aunts/uncles, nieces/nephews, and cousins', function () {
    $grandma = Person::factory()->create(['name' => 'Grandma']);
    $mom = Person::factory()->create(['name' => 'Mom']);
    $aunt = Person::factory()->create(['name' => 'Aunt']);
    $me = Person::factory()->create(['name' => 'Me']);
    $cousin = Person::factory()->create(['name' => 'Cousin']);

    foreach ([$mom, $aunt] as $child) {
        PersonRelationship::create([
            'person_id' => $grandma->id,
            'related_person_id' => $child->id,
            'relationship_type' => PersonRelationshipType::ParentOf->value,
        ]);
    }
    PersonRelationship::create([
        'person_id' => $mom->id, 'related_person_id' => $me->id,
        'relationship_type' => PersonRelationshipType::ParentOf->value,
    ]);
    PersonRelationship::create([
        'person_id' => $aunt->id, 'related_person_id' => $cousin->id,
        'relationship_type' => PersonRelationshipType::ParentOf->value,
    ]);

    expect($me->auntsAndUncles()->pluck('name')->all())->toBe(['Aunt']);
    expect($me->cousins()->pluck('name')->all())->toBe(['Cousin']);
    expect($aunt->niecesAndNephews()->pluck('name')->all())->toBe(['Me']);
});

it('returns the active spouse, ignoring ended ones', function () {
    $a = Person::factory()->create(['name' => 'A']);
    $exB = Person::factory()->create(['name' => 'Ex']);
    $currentC = Person::factory()->create(['name' => 'Current']);

    PersonRelationship::create([
        'person_id' => $a->id, 'related_person_id' => $exB->id,
        'relationship_type' => PersonRelationshipType::Spouse->value,
        'started_at' => '2010-01-01', 'ended_at' => '2018-06-01',
    ]);
    PersonRelationship::create([
        'person_id' => $a->id, 'related_person_id' => $currentC->id,
        'relationship_type' => PersonRelationshipType::Spouse->value,
        'started_at' => '2020-01-01',
    ]);

    expect($a->fresh()->spouse()?->name)->toBe('Current');
});

it('builds a depth-bounded family tree', function () {
    $grandma = Person::factory()->create(['name' => 'Grandma']);
    $mom = Person::factory()->create(['name' => 'Mom']);
    $kid = Person::factory()->create(['name' => 'Kid']);

    PersonRelationship::create([
        'person_id' => $grandma->id, 'related_person_id' => $mom->id,
        'relationship_type' => PersonRelationshipType::ParentOf->value,
    ]);
    PersonRelationship::create([
        'person_id' => $mom->id, 'related_person_id' => $kid->id,
        'relationship_type' => PersonRelationshipType::ParentOf->value,
    ]);

    $tree = $mom->familyTree(depth: 2);
    expect($tree['person']->name)->toBe('Mom');
    expect(collect($tree['parents'])->pluck('person.name')->all())->toBe(['Grandma']);
    expect(collect($tree['children'])->pluck('person.name')->all())->toBe(['Kid']);
});

it('lists relationships from both sides on the Family tab', function () {
    actingAsSuperFamily();
    $a = Person::factory()->create(['name' => 'A']);
    $b = Person::factory()->create(['name' => 'B']);

    PersonRelationship::create([
        'person_id' => $a->id, 'related_person_id' => $b->id,
        'relationship_type' => PersonRelationshipType::ParentOf->value,
    ]);

    Livewire::test('admin.people.family', ['personId' => $a->id])->assertSee('B');
    Livewire::test('admin.people.family', ['personId' => $b->id])->assertSee('A');
});

it('creates a relationship via the Family tab modal', function () {
    actingAsSuperFamily();
    $person = Person::factory()->create(['name' => 'Mom']);
    $kid = Person::factory()->create(['name' => 'Kid']);

    Livewire::test('admin.people.family', ['personId' => $person->id])
        ->call('openCreate')
        ->set('form.relationship_type', PersonRelationshipType::ParentOf->value)
        ->set('form.related_person_id', $kid->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(PersonRelationship::where('person_id', $person->id)
        ->where('related_person_id', $kid->id)
        ->where('relationship_type', PersonRelationshipType::ParentOf->value)
        ->exists())->toBeTrue();
});

it('rejects a self-relationship via the modal (observer guard)', function () {
    actingAsSuperFamily();
    $person = Person::factory()->create();

    Livewire::test('admin.people.family', ['personId' => $person->id])
        ->call('openCreate')
        ->set('form.relationship_type', PersonRelationshipType::ParentOf->value)
        ->set('form.related_person_id', $person->id)
        ->call('save')
        ->assertHasErrors(['form.related_person_id']);
});

it('finds candidate persons by name search inside the modal', function () {
    actingAsSuperFamily();
    $person = Person::factory()->create();
    Person::factory()->create(['name' => 'João Silva']);
    Person::factory()->create(['name' => 'Maria Souza']);

    Livewire::test('admin.people.family', ['personId' => $person->id])
        ->call('openCreate')
        ->set('personSearch', 'João')
        ->assertSee('João Silva')
        ->assertDontSee('Maria Souza');
});
