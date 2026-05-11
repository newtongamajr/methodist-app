<?php

namespace App\Models;

use App\Enums\SignupStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrayerSignup extends Model
{
    protected $fillable = [
        'prayer_slot_id',
        'user_id',
        'person_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => SignupStatus::class,
        ];
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(PrayerSlot::class, 'prayer_slot_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
