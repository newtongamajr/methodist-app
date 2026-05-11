<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly age-based nature promotion: child → teenager → adult.
// withoutOverlapping protects against a slow run colliding with the next
// trigger; onOneServer limits the work to one node in a clustered setup.
Schedule::command('person:promote-minors')
    ->dailyAt('02:15')
    ->withoutOverlapping()
    ->onOneServer();
