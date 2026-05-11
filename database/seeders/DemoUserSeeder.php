<?php

namespace Database\Seeders;

use App\Enums\PersonNature;
use App\Enums\PersonType;
use App\Models\Church;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $firstChurch = Church::query()->orderBy('id')->first();

        $admin = $this->upsert(
            'admin@jejum-oracao.test',
            'Global Admin',
            $firstChurch,
        );
        $admin->syncRoles(['national_admin']);

        $manager = $this->upsert(
            'manager@jejum-oracao.test',
            'Local Manager',
            $firstChurch,
        );
        $manager->syncRoles(['local_admin']);
        if ($firstChurch) {
            $manager->churches()->syncWithoutDetaching([$firstChurch->id => ['is_primary' => true]]);
        }

        $member = $this->upsert(
            'member@jejum-oracao.test',
            'Regular Member',
            $firstChurch,
        );
        $member->syncRoles(['user']);
        if ($firstChurch) {
            $member->churches()->syncWithoutDetaching([$firstChurch->id => ['is_primary' => true]]);
        }
    }

    private function upsert(string $email, string $name, ?Church $church): User
    {
        return DB::transaction(function () use ($email, $name, $church) {
            $existing = User::firstWhere('email', $email);
            if ($existing) {
                $existing->person?->update([
                    'name' => $name,
                    'managing_church_id' => $church?->id,
                ]);
                $existing->forceFill([
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'locale' => 'pt_BR',
                    'email_verified_at' => now(),
                ])->save();

                return $existing;
            }

            $person = Person::create([
                'person_type' => PersonType::Individual->value,
                'name' => $name,
                'natures' => [PersonNature::Member->value],
                'managing_church_id' => $church?->id,
            ]);

            return User::create([
                'person_id' => $person->id,
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'locale' => 'pt_BR',
                'email_verified_at' => now(),
            ]);
        });
    }
}
