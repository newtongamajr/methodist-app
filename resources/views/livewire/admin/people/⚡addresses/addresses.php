<?php

use App\Livewire\Forms\PersonAddressForm;
use App\Models\Person;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public PersonAddressForm $form;

    public Person $person;

    public bool $showModal = false;

    public function mount(int $personId): void
    {
        abort_unless(auth()->user()?->can('users.manage') || auth()->user()?->can('users.manage.local'), 403);
        $this->person = Person::findOrFail($personId);
    }

    #[Computed]
    public function addresses()
    {
        return $this->person->addresses()
            ->orderByDesc('is_primary')
            ->orderBy('label')
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
        $address = $this->person->addresses()->findOrFail($id);
        $this->form->setAddress($address);
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->form->person_id = $this->person->id;
        $this->form->save();
        $this->showModal = false;
        $this->form->reset();
        unset($this->addresses);
    }

    public function delete(int $id): void
    {
        $this->person->addresses()->where('id', $id)->delete();
        unset($this->addresses);
    }
};
