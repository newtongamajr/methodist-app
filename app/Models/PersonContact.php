<?php

namespace App\Models;

use App\Enums\PersonContactType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonContact extends Model
{
    protected $fillable = [
        'person_id',
        'type',
        'value',
        'label',
        'is_primary',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => PersonContactType::class,
            'is_primary' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
