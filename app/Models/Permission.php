<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Project-local Permission model. Mirrors App\Models\Role: we extend the
 * spatie base so we can add columns or behavior here without forking the
 * vendor package. Wired in via config/permission.php so HasPermissions
 * and friends resolve to this class instead of the spatie one.
 */
class Permission extends SpatiePermission
{
    protected $fillable = [
        'name',
        'guard_name',
    ];
}
