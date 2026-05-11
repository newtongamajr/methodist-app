<?php

namespace App\Console\Commands;

use App\Enums\AppLocale;
use App\Enums\PersonNature;
use App\Enums\PersonType;
use App\Models\Person;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class MakeSuperUser extends Command
{
    protected $signature = 'app:make-super
        {--email= : Email of the user to promote / create}
        {--name= : Name to set on a freshly-created user}
        {--password= : Password for a freshly-created user (or to reset)}';

    protected $description = 'Promote an existing user to national_admin — or create a new one — for full administrative access.';

    public function handle(): int
    {
        $email = (string) ($this->option('email') ?: $this->ask('Email'));
        $email = strtolower(trim($email));

        if ($email === '') {
            $this->error('Email is required.');

            return self::FAILURE;
        }

        if (! Role::query()->where('name', 'national_admin')->exists()) {
            $this->error('Role national_admin does not exist. Run: php artisan db:seed --class=RolesAndPermissionsSeeder');

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

            $user = DB::transaction(function () use ($name, $email, $password) {
                $person = Person::create([
                    'person_type' => PersonType::Individual->value,
                    'name' => $name ?: 'Super User',
                    'natures' => [PersonNature::Member->value],
                ]);

                return User::create([
                    'person_id' => $person->id,
                    'name' => $person->name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'locale' => AppLocale::PtBR->value,
                    'appearance' => 'system',
                    'email_verified_at' => now(),
                ]);
            });
            $created = true;
        } elseif ($pw = $this->option('password')) {
            $user->forceFill(['password' => Hash::make($pw)])->save();
            $this->info('Password reset for existing user.');
        }

        $user->syncRoles(['national_admin']);

        $this->info(($created ? 'Created' : 'Promoted').' '.$user->email.' → national_admin.');

        return self::SUCCESS;
    }
}
