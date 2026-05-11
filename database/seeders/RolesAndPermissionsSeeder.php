<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('permission:cache-reset');

        $permissions = [
            'posts.create.shared',
            'posts.create.local',
            'posts.update.any',
            'posts.delete.any',
            'comments.moderate',
            'prayer.schedule.manage',
            'fasting.calendar.manage',
            'church.manage',
            'users.manage',
            'users.manage.local',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // Roles + descriptions. Descriptions are surfaced as badges on the
        // /admin/users index so a non-technical admin can read the role
        // without having to remember what 'regional_admin' implies.
        $roles = [
            ['national_admin', __('National administrator (full access across every region, district, and church)'), $permissions],
            ['regional_admin', __('Regional administrator (everything within one region)'), $permissions],
            ['district_admin', __('District administrator (everything within one district)'), [
                'posts.create.shared',
                'posts.create.local',
                'posts.update.any',
                'posts.delete.any',
                'comments.moderate',
                'prayer.schedule.manage',
                'fasting.calendar.manage',
                'users.manage.local',
            ]],
            ['local_admin', __('Local administrator (everything within one church)'), [
                'posts.create.local',
                'comments.moderate',
                'prayer.schedule.manage',
                'fasting.calendar.manage',
                'users.manage.local',
            ]],
            ['user', __('Regular member (no admin powers)'), []],
        ];

        foreach ($roles as [$name, $description, $perms]) {
            $role = Role::findOrCreate($name, 'web');
            $role->forceFill(['description' => $description])->save();
            if (! empty($perms)) {
                $role->syncPermissions($perms);
            }
        }
    }
}
