<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('creates a brand-new super user when the email is unknown', function () {
    $this->artisan('app:make-super', [
        '--email' => 'super@new.test',
        '--name' => 'Super New',
        '--password' => 'super-secret',
    ])->assertSuccessful();

    $user = User::firstWhere('email', 'super@new.test');
    expect($user)->not->toBeNull();
    expect($user->hasRole('global_manager'))->toBeTrue();
    expect(Hash::check('super-secret', $user->password))->toBeTrue();
});

it('promotes an existing user to global_manager', function () {
    $existing = User::factory()->create(['email' => 'pastor@church.test']);

    $this->artisan('app:make-super', ['--email' => 'pastor@church.test'])
        ->assertSuccessful();

    expect($existing->fresh()->hasRole('global_manager'))->toBeTrue();
});

it('resets the password when --password is provided on an existing user', function () {
    $existing = User::factory()->create(['email' => 'pastor@church.test']);

    $this->artisan('app:make-super', [
        '--email' => 'pastor@church.test',
        '--password' => 'rotated-secret',
    ])->assertSuccessful();

    expect(Hash::check('rotated-secret', $existing->fresh()->password))->toBeTrue();
});

it('fails gracefully when the global_manager role has not been seeded', function () {
    Role::query()->delete();

    $this->artisan('app:make-super', ['--email' => 'super@new.test'])
        ->expectsOutputToContain('Role global_manager does not exist')
        ->assertFailed();
});
