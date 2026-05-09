<?php

use App\Enums\PersonRelationshipType;
use App\Livewire\Forms\PersonRelationshipForm;
use App\Models\Person;
use App\Models\PersonRelationship;
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
        abort_unless(auth()->user()?->can('users.manage') || auth()->user()?->can('users.manage.local'), 403);
        $this->person = Person::findOrFail($personId);
    }

    /**
     * All relationships in which this Person participates, with the OTHER
     * person eager-loaded so the list can render names without an N+1.
     * We collapse the two sides so each pair appears once.
     */
    #[Computed]
    public function relationships()
    {
        $forward = PersonRelationship::query()
            ->where('person_id', $this->person->id)
            ->with('relatedPerson:id,name')
            ->get()
            ->map(fn (PersonRelationship $r) => [
                'id' => $r->id,
                'other' => $r->relatedPerson,
                'type' => $r->relationship_type,
                'started_at' => $r->started_at,
                'ended_at' => $r->ended_at,
                'editable' => true,
            ]);

        $reverse = PersonRelationship::query()
            ->where('related_person_id', $this->person->id)
            ->with('person:id,name')
            ->get()
            ->map(fn (PersonRelationship $r) => [
                'id' => $r->id,
                'other' => $r->person,
                // From this side, the active relationship is the inverse type.
                'type' => $r->inverse_type,
                'started_at' => $r->started_at,
                'ended_at' => $r->ended_at,
                'editable' => false, // edit/delete from the side that owns the row
            ]);

        return $forward->concat($reverse)->sortBy(fn ($r) => $r['type']?->label())->values();
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
};
