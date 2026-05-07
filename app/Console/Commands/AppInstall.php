<?php

namespace App\Console\Commands;

use Database\Seeders\EcclesiasticalRegionSeeder;
use Database\Seeders\FastingCampaignSeeder;
use Database\Seeders\PrayerCampaignSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;

class AppInstall extends Command
{
    protected $signature = 'app:install {--fresh : Drop all tables before running migrations}';

    protected $description = 'Production-safe bootstrap: migrate (optionally fresh), then seed roles, permissions, regions, and the current fasting + prayer campaigns.';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            if ($this->getLaravel()->environment('production') && ! $this->confirm('Production env detected. Really drop everything?')) {
                $this->info('Aborted.');

                return self::SUCCESS;
            }
            $this->call('migrate:fresh', ['--force' => true]);
        } else {
            $this->call('migrate', ['--force' => true]);
        }

        $this->info('Seeding roles and permissions…');
        $this->call('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);

        $this->info('Seeding ecclesiastical regions…');
        $this->call('db:seed', ['--class' => EcclesiasticalRegionSeeder::class, '--force' => true]);

        $this->info('Seeding fasting campaign…');
        $this->call('db:seed', ['--class' => FastingCampaignSeeder::class, '--force' => true]);

        $this->info('Seeding prayer campaign…');
        $this->call('db:seed', ['--class' => PrayerCampaignSeeder::class, '--force' => true]);

        $this->info('Install complete. Promote a super user with: php artisan app:make-super --email=you@example.com');

        return self::SUCCESS;
    }
}
