<?php

namespace App\Console\Commands;

use App\Enums\AppLocale;
use App\Enums\MemberType;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class MakeSuperUser extends Command
{
    protected $signature = 'app:make-super
        {--email= : Email of the user to promote / create}
        {--name= : Name to set on a freshly-created user}
        {--password= : Password for a freshly-created user (or to reset)}';

    protected $description = 'Promote an existing user to global_manager — or create a new one — for full administrative access.';

    public function handle(): int
    {
        $email = (string) ($this->option('email') ?: $this->ask('Email'));
        $email = strtolower(trim($email));

        if ($email === '') {
            $this->error('Email is required.');

            return self::FAILURE;
        }

        if (! Role::query()->where('name', 'global_manager')->exists()) {
            $this->error('Role global_manager does not exist. Run: php artisan db:seed --class=RolesAndPermissionsSeeder');

            return self::FAILURE;
        }

        $user = User::firstWhere('email', $email);
        $created = false;

        if (! $user) {
            $name = (string) ($this->option('name') ?: $this->ask('Full name'));
            $password = (string) ($this->option('password') ?: $this->secret('Initial password (min 8 chars)'));

            if (strlen($password) < 8) {
                $this->error('Password must be at least 8 characters.');

                return self::FAILURE;
            }

            $user = User::create([
                'name' => $name ?: 'Super User',
                'email' => $email,
                'password' => Hash::make($password),
                'member_type' => MemberType::Member->value,
                'locale' => AppLocale::PtBR->value,
                'appearance' => 'system',
                'email_verified_at' => now(),
            ]);
            $created = true;
        } elseif ($pw = $this->option('password')) {
            $user->forceFill(['password' => Hash::make($pw)])->save();
            $this->info('Password reset for existing user.');
        }

        $user->syncRoles(['global_manager']);

        $this->info(($created ? 'Created' : 'Promoted').' '.$user->email.' → global_manager.');

        return self::SUCCESS;
    }
}
