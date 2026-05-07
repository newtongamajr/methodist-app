<?php

namespace App\Models;

use App\Enums\RegionKind;
use Database\Factories\EcclesiasticalRegionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcclesiasticalRegion extends Model
{
    /** @use HasFactory<EcclesiasticalRegionFactory> */
    use HasFactory;

    protected $fillable = [
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

    public function churches(): HasMany
    {
        return $this->hasMany(Church::class);
    }
}
