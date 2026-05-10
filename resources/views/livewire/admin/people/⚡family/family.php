<?php

use App\Enums\Gender;
use App\Enums\PersonRelationshipType;
use App\Livewire\Forms\PersonRelationshipForm;
use App\Models\Person;
use App\Models\PersonRelationship;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public PersonRelationshipForm $form;

    public Person $person;

    public bool $showModal = false;

    /** Search box for the related-person picker inside the modal. */
    public string $personSearch = '';

    public function mount(int $personId): void
    {
        $authUser = auth()->user();
        $isOwner = $authUser && $personId && $authUser->person_id === $personId;
        abort_unless($isOwner || $authUser?->can('users.manage') || $authUser?->can('users.manage.local'), 403);
        $this->person = Person::findOrFail($personId);
    }

    /**
     * Every relationship to surface on the Family tab — both the explicit
     * stored rows AND the derived ones (siblings, grandparents, in-laws,
     * step-children, …) computed from the graph. Derived rows have no DB id
     * and are read-only; an explicit row for the same person always wins so
     * we never show the same person twice.
     */
    #[Computed]
    public function relationships()
    {
        $rows = collect();
        $explicitOtherIds = collect();
        $authUser = auth()->user();
        // Only surface the Act-as button when the auth user is looking at
        // their OWN family — admins viewing someone else's record don't get
        // act-as buttons for that other person's children.
        $isOwnFamily = $authUser && $authUser->person_id === $this->person->id;

        $forward = PersonRelationship::query()
            ->where('person_id', $this->person->id)
            ->with('relatedPerson:id,name,gender,birthdate,natures')
            ->get();
        foreach ($forward as $r) {
            if (! $r->relatedPerson) {
                continue;
            }
            $explicitOtherIds[] = $r->relatedPerson->id;
            $rows[] = [
                'id' => 'r-'.$r->id,
                'other' => $r->relatedPerson,
                'type_label' => $r->relationship_type?->label() ?? '—',
                'started_at' => $r->started_at,
                'ended_at' => $r->ended_at,
                'editable' => true,
                'derived' => false,
                'db_id' => $r->id,
                'can_act_as' => $isOwnFamily && $authUser->canActAs($r->relatedPerson),
            ];
        }

        $reverse = PersonRelationship::query()
            ->where('related_person_id', $this->person->id)
            ->with('person:id,name,gender,birthdate,natures')
            ->get();
        foreach ($reverse as $r) {
            if (! $r->person) {
                continue;
            }
            $explicitOtherIds[] = $r->person->id;
            $rows[] = [
                'id' => 'r-'.$r->id.'-rev',
                'other' => $r->person,
                'type_label' => $r->inverse_type?->label() ?? '—',
                'started_at' => $r->started_at,
                'ended_at' => $r->ended_at,
                'editable' => false,
                'derived' => false,
                'db_id' => $r->id,
                'can_act_as' => $isOwnFamily && $authUser->canActAs($r->person),
            ];
        }

        $explicitOtherIds = $explicitOtherIds->unique();

        // Derived relationships — gender-aware labels. Each kind is a tuple of
        // (Collection of Person, label-resolver). The resolver picks the
        // gendered translation (Brother vs Sister, Stepson vs Stepdaughter…)
        // from Person->gender, falling back to a neutral term when unknown.
        $derived = [
            ['sibling', $this->person->siblings()],
            ['grandparent', $this->person->grandparents()],
            ['grandchild', $this->person->grandchildren()],
            ['uncle_aunt', $this->person->auntsAndUncles()],
            ['niece_nephew', $this->person->niecesAndNephews()],
            ['cousin', $this->person->cousins()],
            ['sibling_in_law', $this->person->siblingsInLaw()],
            ['parent_in_law', $this->person->parentsInLaw()],
            ['child_in_law', $this->person->childrenInLaw()],
            ['stepchild', $this->person->stepchildren()],
            ['stepparent', $this->person->stepparents()],
        ];

        foreach ($derived as [$kind, $people]) {
            /** @var Collection<int, Person> $people */
            foreach ($people as $other) {
                if ($explicitOtherIds->contains($other->id)) {
                    continue;
                }
                $explicitOtherIds[] = $other->id; // dedupe across derived kinds too
                $rows[] = [
                    'id' => 'd-'.$kind.'-'.$other->id,
                    'other' => $other,
                    'type_label' => self::derivedLabel($kind, $other->gender),
                    'started_at' => null,
                    'ended_at' => null,
                    'editable' => false,
                    'derived' => true,
                    'db_id' => null,
                    'can_act_as' => $isOwnFamily && $authUser->canActAs($other),
                ];
            }
        }

        // Bucketed display order:
        //   0 = explicit rows owned by this Person (editable here)
        //   1 = explicit rows owned by the other side (defined elsewhere)
        //   2 = derived rows (computed from the family graph)
        // Within each bucket, sort by relationship label so "Brother" lands
        // next to "Brother-in-law" etc.
        return $rows
            ->sortBy(fn ($r) => [
                $r['derived'] ? 2 : ($r['editable'] ? 0 : 1),
                $r['type_label'],
                $r['other']?->name ?? '',
            ])
            ->values();
    }

    /**
     * Person picker for the modal: limited to a few matches to keep the
     * payload small. Uses the in-modal search box.
     */
    #[Computed]
    public function candidatePersons()
    {
        $term = trim($this->personSearch);
        if ($term === '') {
            return collect();
        }
        $like = '%'.addcslashes($term, '%_\\').'%';

        return Person::query()
            ->whereKeyNot($this->person->id)
            ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('preferred_name', 'like', $like))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name']);
    }

    public function openCreate(): void
    {
        $this->form->reset();
        $this->personSearch = '';
        $this->form->person_id = $this->person->id;
        $this->form->relationship_type = PersonRelationshipType::ParentOf->value;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $relationship = $this->person->relationships()->findOrFail($id);
        $this->form->setRelationship($relationship);
        $this->personSearch = '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->form->person_id = $this->person->id;
        $this->form->save();
        $this->showModal = false;
        $this->form->reset();
        $this->personSearch = '';
        unset($this->relationships);
    }

    public function delete(int $id): void
    {
        $relationship = $this->person->relationships()->findOrFail($id);
        $relationship->delete();
        unset($this->relationships);
    }

    /**
     * Switch the session into an "act-as" context so the current user records
     * subsequent actions on behalf of a minor/ward in their family. Only fires
     * when the auth user can canActAs() the target — admins viewing someone
     * else's family don't see the trigger button to begin with.
     */
    public function actAs(int $otherPersonId): void
    {
        $target = Person::findOrFail($otherPersonId);
        abort_unless(auth()->user()?->canActAs($target), 403);

        session(['acting_as_person_id' => $target->id]);
        $this->dispatch('acting-as-changed');
    }

    private static function derivedLabel(string $kind, ?Gender $gender): string
    {
        $isMale = $gender === Gender::Male;
        $isFemale = $gender === Gender::Female;

        return match ($kind) {
            'sibling' => $isMale ? __('Brother') : ($isFemale ? __('Sister') : __('Sibling')),
            'grandparent' => $isMale ? __('Grandfather') : ($isFemale ? __('Grandmother') : __('Grandparent')),
            'grandchild' => $isMale ? __('Grandson') : ($isFemale ? __('Granddaughter') : __('Grandchild')),
            'uncle_aunt' => $isMale ? __('Uncle') : ($isFemale ? __('Aunt') : __('Uncle or aunt')),
            'niece_nephew' => $isMale ? __('Nephew') : ($isFemale ? __('Niece') : __('Nephew or niece')),
            'cousin' => __('Cousin'),
            'sibling_in_law' => $isMale ? __('Brother-in-law') : ($isFemale ? __('Sister-in-law') : __('Sibling-in-law')),
            'parent_in_law' => $isMale ? __('Father-in-law') : ($isFemale ? __('Mother-in-law') : __('Parent-in-law')),
            'child_in_law' => $isMale ? __('Son-in-law') : ($isFemale ? __('Daughter-in-law') : __('Child-in-law')),
            'stepchild' => $isMale ? __('Stepson') : ($isFemale ? __('Stepdaughter') : __('Stepchild')),
            'stepparent' => $isMale ? __('Stepfather') : ($isFemale ? __('Stepmother') : __('Stepparent')),
            default => '—',
        };
    }
};