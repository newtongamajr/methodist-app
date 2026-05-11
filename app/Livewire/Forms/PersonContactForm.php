<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\PersonContactType;
use App\Models\PersonContact;
use Livewire\Form;

class PersonContactForm extends Form
{
    public ?PersonContact $contact = null;

    public ?int $person_id = null;

    public string $type = 'phone';

    public string $value = '';

    public string $label = '';

    public bool $is_primary = false;

    public function rules(): array
    {
        return [
            'person_id' => ['required', 'integer', 'exists:persons,id'],
            'type' => ['required', 'in:'.implode(',', array_map(fn ($c) => $c->value, PersonContactType::cases()))],
            'value' => ['required', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:64'],
            'is_primary' => ['boolean'],
        ];
    }

    public function setContact(PersonContact $contact): void
    {
        $this->contact = $contact;
        $this->person_id = $contact->person_id;
        $this->type = $contact->type?->value ?? PersonContactType::Phone->value;
        $this->value = $contact->value;
        $this->label = $contact->label ?? '';
        $this->is_primary = $contact->is_primary;
    }

    public function save(): PersonContact
    {
        $data = $this->validate();
        if (($data['label'] ?? null) === '') {
            $data['label'] = null;
        }

        if ($this->contact) {
            $this->contact->update($data);
        } else {
            $this->contact = PersonContact::create($data);
        }

        // Demote any other primary contact of the same type for the same person.
        if ($this->contact->is_primary) {
            PersonContact::query()
                ->where('person_id', $this->contact->person_id)
                ->where('type', $this->contact->type)
                ->whereKeyNot($this->contact->id)
                ->update(['is_primary' => false]);
        }

        return $this->contact;
    }
}
