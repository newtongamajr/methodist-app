<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Project-local Role model. Extends spatie's so we can add a `description`
 * column (used in the admin/users index list) without losing any of the
 * permission-machinery behavior. Wired in via config/permission.php.
 */
class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'description',
        'guard_name',
    ];
}
