<?php

namespace App\Models;

use Database\Factories\PersonRoleAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonRoleAssignment extends Model
{
    /** @use HasFactory<PersonRoleAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'person_id',
        'function_id',
        'assignment_role_id',
        'group_id',
        'church_id',
        'ecclesiastical_region_id',
        'district_id',
        'started_at',
        'ended_at',
        'is_primary',
        'context_data',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
            'is_primary' => 'boolean',
            'context_data' => 'array',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function function(): BelongsTo
    {
        return $this->belongsTo(FunctionRole::class, 'function_id');
    }

    public function assignmentRole(): BelongsTo
    {
        return $this->belongsTo(AssignmentRole::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(EcclesiasticalRegion::class, 'ecclesiastical_region_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function isActive(): bool
    {
        return $this->ended_at === null || $this->ended_at->isFuture();
    }
}
