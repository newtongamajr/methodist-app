<?php

namespace Database\Seeders;

use App\Enums\MemberType;
use App\Models\Church;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $firstChurch = Church::query()->orderBy('id')->first();

        $admin = User::updateOrCreate(
            ['email' => 'admin@jejum-oracao.test'],
            [
                'name' => 'Global Admin',
                'password' => Hash::make('password'),
                'member_type' => MemberType::Member->value,
                'church_id' => $firstChurch?->id,
                'locale' => 'pt_BR',
                'email_verified_at' => now(),
            ],
        );
        $admin->syncRoles(['global_manager']);

        $manager = User::updateOrCreate(
            ['email' => 'manager@jejum-oracao.test'],
            [
                'name' => 'Local Manager',
                'password' => Hash::make('password'),
                'member_type' => MemberType::Member->value,
                'church_id' => $firstChurch?->id,
                'locale' => 'pt_BR',
                'email_verified_at' => now(),
            ],
        );
        $manager->syncRoles(['local_manager']);
        if ($firstChurch) {
            $manager->churches()->syncWithoutDetaching([$firstChurch->id => ['is_primary' => true]]);
        }

        $member = User::updateOrCreate(
            ['email' => 'member@jejum-oracao.test'],
            [
                'name' => 'Regular Member',
                'password' => Hash::make('password'),
                'member_type' => MemberType::Member->value,
                'church_id' => $firstChurch?->id,
                'locale' => 'pt_BR',
                'email_verified_at' => now(),
            ],
        );
        $member->syncRoles(['user']);
        if ($firstChurch) {
            $member->churches()->syncWithoutDetaching([$firstChurch->id => ['is_primary' => true]]);
        }
    }
}
