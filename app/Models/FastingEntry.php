<?php

namespace App\Models;

use App\Enums\FastingType;
use Database\Factories\FastingEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FastingEntry extends Model
{
    /** @use HasFactory<FastingEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'person_id',
        'fasting_campaign_id',
        'date',
        'type',
        'restrictions',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'type' => FastingType::class,
            'restrictions' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(FastingCampaign::class, 'fasting_campaign_id');
    }
}
