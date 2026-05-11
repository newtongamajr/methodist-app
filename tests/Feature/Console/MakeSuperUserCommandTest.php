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
    expect($user->hasRole('national_admin'))->toBeTrue();
    expect(Hash::check('super-secret', $user->password))->toBeTrue();
});

it('promotes an existing user to national_admin', function () {
    $existing = User::factory()->create(['email' => 'pastor@church.test']);

    $this->artisan('app:make-super', ['--email' => 'pastor@church.test'])
        ->assertSuccessful();

    expect($existing->fresh()->hasRole('national_admin'))->toBeTrue();
});

it('resets the password when --password is provided on an existing user', function () {
    $existing = User::factory()->create(['email' => 'pastor@church.test']);

    $this->artisan('app:make-super', [
        '--email' => 'pastor@church.test',
        '--password' => 'rotated-secret',
    ])->assertSuccessful();

    expect(Hash::check('rotated-secret', $existing->fresh()->password))->toBeTrue();
});

it('fails gracefully when the national_admin role has not been seeded', function () {
    Role::query()->delete();

    $this->artisan('app:make-super', ['--email' => 'super@new.test'])
        ->expectsOutputToContain('Role national_admin does not exist')
        ->assertFailed();
});
