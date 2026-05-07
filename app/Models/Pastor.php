<?php

namespace App\Models;

use Database\Factories\PastorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pastor extends Model
{
    /** @use HasFactory<PastorFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(PastorAssignment::class)->orderBy('start_date', 'desc');
    }

    public function churches(): BelongsToMany
    {
        return $this->belongsToMany(Church::class, 'pastor_assignments')
            ->withPivot(['role', 'start_date', 'end_date', 'display_order'])
            ->withTimestamps();
    }

    public function activeChurches()
    {
        $today = now();

        return $this->churches()
            ->wherePivot(fn ($q) => null)
            ->where(fn ($q) => $q->whereNull('pastor_assignments.start_date')->orWhere('pastor_assignments.start_date', '<=', $today))
            ->where(fn ($q) => $q->whereNull('pastor_assignments.end_date')->orWhere('pastor_assignments.end_date', '>=', $today));
    }
}
