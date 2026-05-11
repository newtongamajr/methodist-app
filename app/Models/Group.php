<?php

namespace App\Models;

use App\Enums\GroupKind;
use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    protected $fillable = [
        'kind',
        'name',
        'slug',
        'description',
        'ecclesiastical_region_id',
        'district_id',
        'church_id',
        'started_at',
        'ended_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'kind' => GroupKind::class,
            'started_at' => 'date',
            'ended_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(EcclesiasticalRegion::class, 'ecclesiastical_region_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(PersonRoleAssignment::class);
    }

    public function level(): string
    {
        return match (true) {
            $this->church_id !== null => 'church',
            $this->district_id !== null => 'district',
            $this->ecclesiastical_region_id !== null => 'region',
            default => 'national',
        };
    }
}
