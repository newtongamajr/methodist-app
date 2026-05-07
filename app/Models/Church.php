<?php

namespace App\Models;

use App\Enums\ChurchType;
use App\Enums\LocationMode;
use Database\Factories\ChurchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Church extends Model
{
    /** @use HasFactory<ChurchFactory> */
    use HasFactory;

    protected $fillable = [
        'ecclesiastical_region_id',
        'type',
        'name',
        'slug',
        'address',
        'city',
        'state',
        'zip',
        'latitude',
        'longitude',
        'timezone',
        'max_prayers_per_slot',
        'default_mode',
        'phone',
        'email',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'max_prayers_per_slot' => 'integer',
            'type' => ChurchType::class,
            'default_mode' => LocationMode::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(EcclesiasticalRegion::class, 'ecclesiastical_region_id');
    }

    public function pastorAssignments(): HasMany
    {
        return $this->hasMany(PastorAssignment::class)->orderBy('display_order');
    }

    public function pastors(): BelongsToMany
    {
        return $this->belongsToMany(Pastor::class, 'pastor_assignments')
            ->withPivot(['role', 'start_date', 'end_date', 'display_order'])
            ->withTimestamps();
    }

    public function currentPastors(): BelongsToMany
    {
        $today = now()->toDateString();

        return $this->pastors()
            ->where(fn ($q) => $q->whereNull('pastor_assignments.start_date')->orWhere('pastor_assignments.start_date', '<=', $today))
            ->where(fn ($q) => $q->whereNull('pastor_assignments.end_date')->orWhere('pastor_assignments.end_date', '>=', $today))
            ->orderByPivot('display_order');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function primaryUsers(): HasMany
    {
        return $this->hasMany(User::class, 'church_id');
    }
}
