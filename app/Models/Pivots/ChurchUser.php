<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * The church_user pivot. Promoted to a Pivot model so we can hang an
 * observer off it (single-active-primary enforcement). The pivot table is
 * the conventional `church_user` (Laravel-default name).
 */
class ChurchUser extends Pivot
{
    protected $table = 'church_user';

    protected $casts = [
        'is_primary' => 'boolean',
    ];
}
