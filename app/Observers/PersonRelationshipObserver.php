<?php

namespace App\Observers;

use App\Enums\PersonRelationshipType;
use App\Models\PersonRelationship;
use Illuminate\Validation\ValidationException;

class PersonRelationshipObserver
{
    public function creating(PersonRelationship $relationship): void
    {
        if ($relationship->person_id === $relationship->related_person_id) {
            throw ValidationException::withMessages([
                'related_person_id' => __('A person cannot have a relationship with themselves.'),
            ]);
        }

        $type = $relationship->relationship_type instanceof PersonRelationshipType
            ? $relationship->relationship_type
            : PersonRelationshipType::from((string) $relationship->relationship_type);

        $relationship->inverse_type = $type->inverse()->value;

        $duplicate = PersonRelationship::query()
            ->where('person_id', $relationship->person_id)
            ->where('related_person_id', $relationship->related_person_id)
            ->where('relationship_type', $type->value)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'related_person_id' => __('This relationship already exists.'),
            ]);
        }

        if ($type === PersonRelationshipType::Spouse) {
            $hasActiveSpouse = PersonRelationship::query()
                ->where('person_id', $relationship->person_id)
                ->where('relationship_type', PersonRelationshipType::Spouse->value)
                ->whereNull('ended_at')
                ->exists();

            if ($hasActiveSpouse) {
                throw ValidationException::withMessages([
                    'related_person_id' => __('This person already has an active spouse. End the previous spousal relationship first.'),
                ]);
            }
        }
    }
}
