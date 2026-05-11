<?php

namespace App\Models;

use App\Enums\RegionKind;
use Database\Factories\EcclesiasticalRegionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcclesiasticalRegion extends Model
{
    /** @use HasFactory<EcclesiasticalRegionFactory> */
    use HasFactory;

    protected $fillable = [
        'person_id',
        'code',
        'name',
        'kind',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'kind' => RegionKind::class,
            'display_order' => 'integer',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function churches(): HasMany
    {
        return $this->hasMany(Church::class);
    }

    public function isNationalHeadquarters(): bool
    {
        return $this->kind === RegionKind::NationalHeadquarters;
    }
}
