<?php

use App\Livewire\Forms\PersonContactForm;
use App\Models\Person;
use App\Models\PersonContact;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public PersonContactForm $form;

    public Person $person;

    public bool $showModal = false;

    public function mount(int $personId): void
    {
        abort_unless(auth()->user()?->can('users.manage') || auth()->user()?->can('users.manage.local'), 403);
        $this->person = Person::findOrFail($personId);
    }

    #[Computed]
    public function contacts()
    {
        return $this->person->contacts()
            ->orderByDesc('is_primary')
            ->orderBy('type')
            ->get();
    }

    public function openCreate(): void
    {
        $this->form->reset();
        $this->form->person_id = $this->person->id;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $contact = $this->person->contacts()->findOrFail($id);
        $this->form->setContact($contact);
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->form->person_id = $this->person->id;
        $this->form->save();
        $this->showModal = false;
        $this->form->reset();
        unset($this->contacts);
        $this->dispatch('contact-saved');
    }

    public function delete(int $id): void
    {
        $this->person->contacts()->where('id', $id)->delete();
        unset($this->contacts);
    }
};
