<?php

namespace App\Models\Pivots;

use App\Models\Church;
use App\Models\District;
use App\Models\EcclesiasticalRegion;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * The church_user pivot. Doubles as the source of truth for non-national
 * admin scopes since the schema migration that added `region_id` and
 * `district_id` and relaxed `church_id` to nullable.
 *
 * Allowed row shapes:
 *   - region_id only                       → regional admin scope
 *   - region_id + district_id              → district admin scope
 *   - region_id + district_id + church_id  → local admin scope OR plain
 *                                             membership (distinguished
 *                                             by the user's role)
 *
 * National admins keep no rows; their access is implied by the role alone.
 */
class ChurchUser extends Pivot
{
    protected $table = 'church_user';

    protected $fillable = [
        'church_id',
        'region_id',
        'district_id',
        'user_id',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(EcclesiasticalRegion::class, 'region_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /** Bucketing helper that names the row's shape for UI / debugging. */
    public function scopeShape(): string
    {
        if ($this->church_id) {
            return 'local';
        }
        if ($this->district_id) {
            return 'district';
        }
        if ($this->region_id) {
            return 'regional';
        }

        return 'unknown';
    }
}
