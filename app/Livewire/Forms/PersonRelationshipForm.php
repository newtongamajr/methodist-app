<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\PersonRelationshipType;
use App\Models\PersonRelationship;
use Livewire\Form;

class PersonRelationshipForm extends Form
{
    public ?PersonRelationship $relationship = null;

    public ?int $person_id = null;

    public ?int $related_person_id = null;

    public string $relationship_type = '';

    public string $started_at = '';

    public string $ended_at = '';

    public function rules(): array
    {
        return [
            'person_id' => ['required', 'integer', 'exists:persons,id'],
            'related_person_id' => ['required', 'integer', 'exists:persons,id', 'different:person_id'],
            'relationship_type' => ['required', 'in:'.implode(',', array_map(fn ($c) => $c->value, PersonRelationshipType::cases()))],
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
        ];
    }

    public function setRelationship(PersonRelationship $relationship): void
    {
        $this->relationship = $relationship;
        $this->person_id = $relationship->person_id;
        $this->related_person_id = $relationship->related_person_id;
        $this->relationship_type = $relationship->relationship_type?->value ?? '';
        $this->started_at = $relationship->started_at?->format('Y-m-d') ?? '';
        $this->ended_at = $relationship->ended_at?->format('Y-m-d') ?? '';
    }

    public function save(): PersonRelationship
    {
        $data = $this->validate();
        foreach (['started_at', 'ended_at'] as $k) {
            if (($data[$k] ?? null) === '') {
                $data[$k] = null;
            }
        }

        if ($this->relationship) {
            $this->relationship->update($data);
        } else {
            // inverse_type is auto-set by PersonRelationshipObserver on creating.
            $this->relationship = PersonRelationship::create($data);
        }

        return $this->relationship;
    }
}
