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

it('surfaces a partner\'s child as a stepchild without storing a duplicate row', function () {
    // A is married to B. A has child C. B should see C as a stepchild on
    // their family list — NO new relationship row exists between B and C.
    $a = Person::factory()->create(['name' => 'A']);
    $b = Person::factory()->create(['name' => 'B']);
    $c = Person::factory()->create(['name' => 'C']);

    PersonRelationship::create([
        'person_id' => $a->id, 'related_person_id' => $b->id,
        'relationship_type' => PersonRelationshipType::Spouse->value,
    ]);
    PersonRelationship::create([
        'person_id' => $a->id, 'related_person_id' => $c->id,
        'relationship_type' => PersonRelationshipType::ParentOf->value,
    ]);

    expect($b->fresh()->stepchildren()->pluck('name')->all())->toBe(['C']);
    expect($c->fresh()->stepparents()->pluck('name')->all())->toBe(['B']);

    // The relationship is purely derived — no row directly connects B↔C.
    expect(PersonRelationship::query()
        ->where(fn ($q) => $q->where('person_id', $b->id)->where('related_person_id', $c->id))
        ->orWhere(fn ($q) => $q->where('person_id', $c->id)->where('related_person_id', $b->id))
        ->exists())->toBeFalse();
});

it('derives parents-in-law and children-in-law from the active spouse', function () {
    // A married B. B's mother is M. → M is A's parent-in-law.
    // A's child is K, K married L → L is A's child-in-law.
    $a = Person::factory()->create(['name' => 'A']);
    $b = Person::factory()->create(['name' => 'B']);
    $m = Person::factory()->create(['name' => 'M']);
    $k = Person::factory()->create(['name' => 'K']);
    $l = Person::factory()->create(['name' => 'L']);

    PersonRelationship::create([
        'person_id' => $a->id, 'related_person_id' => $b->id,
        'relationship_type' => PersonRelationshipType::Spouse->value,
    ]);
    PersonRelationship::create([
        'person_id' => $m->id, 'related_person_id' => $b->id,
        'relationship_type' => PersonRelationshipType::ParentOf->value,
    ]);
    PersonRelationship::create([
        'person_id' => $a->id, 'related_person_id' => $k->id,
        'relationship_type' => PersonRelationshipType::ParentOf->value,
    ]);
    PersonRelationship::create([
        'person_id' => $k->id, 'related_person_id' => $l->id,
        'relationship_type' => PersonRelationshipType::Spouse->value,
    ]);

    expect($a->fresh()->parentsInLaw()->pluck('name')->all())->toBe(['M']);
    expect($a->fresh()->childrenInLaw()->pluck('name')->all())->toBe(['L']);
});

it('derives siblings-in-law from spouse and from siblings\' spouses', function () {
    // I'm married to S. S's brother is BIL_a (a "brother-in-law via spouse").
    // I also have a sister Sis, married to BIL_b ("brother-in-law via my sibling").
    $me = Person::factory()->create(['name' => 'Me']);
    $s = Person::factory()->create(['name' => 'S']);
    $sParent = Person::factory()->create(['name' => 'SParent']);
    $bilA = Person::factory()->create(['name' => 'BILa']);
    $myParent = Person::factory()->create(['name' => 'MyParent']);
    $sis = Person::factory()->create(['name' => 'Sis']);
    $bilB = Person::factory()->create(['name' => 'BILb']);

    PersonRelationship::create([
        'person_id' => $me->id, 'related_person_id' => $s->id,
        'relationship_type' => PersonRelationshipType::Spouse->value,
    ]);
    foreach ([$s, $bilA] as $kid) {
        PersonRelationship::create([
            'person_id' => $sParent->id, 'related_person_id' => $kid->id,
            'relationship_type' => PersonRelationshipType::ParentOf->value,
        ]);
    }
    foreach ([$me, $sis] as $kid) {
        PersonRelationship::create([
            'person_id' => $myParent->id, 'related_person_id' => $kid->id,
            'relationship_type' => PersonRelationshipType::ParentOf->value,
        ]);
    }
    PersonRelationship::create([
        'person_id' => $sis->id, 'related_person_id' => $bilB->id,
        'relationship_type' => PersonRelationshipType::Spouse->value,
    ]);

    $names = $me->fresh()->siblingsInLaw()->pluck('name')->sort()->values()->all();
    expect($names)->toBe(['BILa', 'BILb']);
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
