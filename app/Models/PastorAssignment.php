<?php

namespace App\Models;

use App\Enums\PastorRole;
use Database\Factories\PastorAssignmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class PastorAssignment extends Model
{
    /** @use HasFactory<PastorAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'pastor_id',
        'church_id',
        'role',
        'start_date',
        'end_date',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'role' => PastorRole::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'display_order' => 'integer',
        ];
    }

    public function pastor(): BelongsTo
    {
        return $this->belongsTo(Pastor::class);
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function isActiveOn(?Carbon $date = null): bool
    {
        $date = $date ?: now();

        if ($this->start_date && $this->start_date->gt($date)) {
            return false;
        }
        if ($this->end_date && $this->end_date->lt($date)) {
            return false;
        }

        return true;
    }

    public function scopeActiveOn(Builder $query, Carbon $date): Builder
    {
        return $query
            ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $date))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date));
    }
}
