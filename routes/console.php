<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keeps maintenance alert emails flowing even if nobody opens the
// maintenance-schedules screen (which also triggers the same check).
Schedule::command('maintenance:check-alerts')->daily();
