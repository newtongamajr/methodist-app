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

        $national = Role::findOrCreate('national_admin', 'web');
        $national->syncPermissions($permissions);

        $regional = Role::findOrCreate('regional_admin', 'web');
        $regional->syncPermissions($permissions);

        $district = Role::findOrCreate('district_admin', 'web');
        $district->syncPermissions([
            'posts.create.shared',
            'posts.create.local',
            'posts.update.any',
            'posts.delete.any',
            'comments.moderate',
            'prayer.schedule.manage',
            'fasting.calendar.manage',
            'users.manage.local',
        ]);

        $local = Role::findOrCreate('local_admin', 'web');
        $local->syncPermissions([
            'posts.create.local',
            'comments.moderate',
            'prayer.schedule.manage',
            'fasting.calendar.manage',
            'users.manage.local',
        ]);

        Role::findOrCreate('user', 'web');
    }
}
