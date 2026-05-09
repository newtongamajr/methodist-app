<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EcclesiasticalRegionSeeder::class,
            RolesAndPermissionsSeeder::class,
            FunctionsSeeder::class,
            FastingCampaignSeeder::class,
            PrayerCampaignSeeder::class,
        ]);

        if (app()->environment('local', 'testing')) {
            $this->call([
                DemoDistrictSeeder::class,
                DemoChurchSeeder::class,
                DemoUserSeeder::class,
            ]);
        }
    }
}
