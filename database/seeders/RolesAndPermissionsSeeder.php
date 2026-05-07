<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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

        $globalManager = Role::findOrCreate('global_manager', 'web');
        $globalManager->syncPermissions($permissions);

        $localManager = Role::findOrCreate('local_manager', 'web');
        $localManager->syncPermissions([
            'posts.create.local',
            'comments.moderate',
            'prayer.schedule.manage',
            'fasting.calendar.manage',
            'users.manage.local',
        ]);

        Role::findOrCreate('user', 'web');
    }
}
