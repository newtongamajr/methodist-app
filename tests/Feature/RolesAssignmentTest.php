<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('creates the three application roles', function () {
    expect(Role::pluck('name')->all())
        ->toEqualCanonicalizing(['global_manager', 'local_manager', 'user']);
});

it('grants global_manager every permission', function () {
    $role = Role::findByName('global_manager');
    expect($role->permissions()->count())->toBeGreaterThanOrEqual(8);
});

it('limits local_manager to local + moderation permissions', function () {
    $role = Role::findByName('local_manager');
    expect($role->permissions()->pluck('name')->all())->toEqualCanonicalizing([
        'posts.create.local',
        'comments.moderate',
        'prayer.schedule.manage',
        'fasting.calendar.manage',
        'users.manage.local',
    ]);
});

it('assigns roles to a user', function () {
    $user = User::factory()->create();
    $user->assignRole('local_manager');

    expect($user->hasRole('local_manager'))->toBeTrue();
    expect($user->can('comments.moderate'))->toBeTrue();
    expect($user->can('posts.create.shared'))->toBeFalse();
});
