<?php

use App\Models\Person;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    /**
     * Family relationships of the current user's Person, grouped for the
     * profile UI. The "Act as" button only renders next to Persons the
     * current user is allowed to act-as (per User::canActAs).
     */
    #[Computed]
    public function person(): ?Person
    {
        return auth()->user()?->person;
    }

    #[Computed]
    public function children()
    {
        return $this->person?->children() ?? collect();
    }

    #[Computed]
    public function spouse(): ?Person
    {
        return $this->person?->spouse();
    }

    #[Computed]
    public function parents()
    {
        return $this->person?->parents() ?? collect();
    }

    #[Computed]
    public function godchildren()
    {
        return $this->person?->godchildren() ?? collect();
    }

    #[Computed]
    public function wards()
    {
        return $this->person?->wards() ?? collect();
    }

    public function actAs(int $personId): void
    {
        $target = Person::findOrFail($personId);
        abort_unless(auth()->user()?->canActAs($target), 403);

        session(['acting_as_person_id' => $target->id]);
        $this->dispatch('acting-as-changed');
    }
};
