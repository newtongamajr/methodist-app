<?php

namespace App\Models;

use App\Enums\PersonRelationshipType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonRelationship extends Model
{
    protected $fillable = [
        'person_id',
        'related_person_id',
        'relationship_type',
        'inverse_type',
        'started_at',
        'ended_at',
        'context_data',
    ];

    protected function casts(): array
    {
        return [
            'relationship_type' => PersonRelationshipType::class,
            'inverse_type' => PersonRelationshipType::class,
            'started_at' => 'date',
            'ended_at' => 'date',
            'context_data' => 'array',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function relatedPerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'related_person_id');
    }
}
