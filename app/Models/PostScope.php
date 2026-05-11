<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One audience the parent Post is published to. A Post can have many of
 * these — the visibility check is OR across the rows. Allowed shapes:
 *
 *   - national_post=true, all FKs null  → everyone sees it
 *   - region_id only                    → users whose church lives in that region
 *   - region_id + district_id           → users whose church lives in that district
 *   - region_id + district_id + church_id → users on that specific church
 *
 * The shape is enforced at write time by the Post editor and at read time
 * by Post::scopeVisibleTo(), not at the schema level (NULLs make CHECK
 * constraints awkward and MySQL didn't enforce them historically).
 */
class PostScope extends Model
{
    protected $fillable = [
        'post_id',
        'national_post',
        'region_id',
        'district_id',
        'church_id',
    ];

    protected function casts(): array
    {
        return [
            'national_post' => 'boolean',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(EcclesiasticalRegion::class, 'region_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /**
     * Bucket label for UI display. Mirrors the four allowed shapes; rows
     * that don't fit any shape (corrupt data) fall through to "Local".
     */
    public function shape(): string
    {
        if ($this->national_post) {
            return 'national';
        }
        if ($this->church_id) {
            return 'local';
        }
        if ($this->district_id) {
            return 'district';
        }
        if ($this->region_id) {
            return 'regional';
        }

        return 'local';
    }
}
