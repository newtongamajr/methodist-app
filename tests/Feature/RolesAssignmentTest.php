<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('creates the five application roles', function () {
    expect(Role::pluck('name')->all())
        ->toEqualCanonicalizing(['national_admin', 'regional_admin', 'district_admin', 'local_admin', 'user']);
});

it('grants national_admin every permission', function () {
    $role = Role::findByName('national_admin');
    expect($role->permissions()->count())->toBeGreaterThanOrEqual(8);
});

it('limits local_manager to local + moderation permissions', function () {
    $role = Role::findByName('local_admin');
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
    $user->assignRole('local_admin');

    expect($user->hasRole('local_admin'))->toBeTrue();
    expect($user->can('comments.moderate'))->toBeTrue();
    expect($user->can('posts.create.shared'))->toBeFalse();
});
