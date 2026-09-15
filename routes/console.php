<?php

use App\Jobs\CleanupOldRecordsJob;
use App\Jobs\SyncAccountingJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\ReconcileMikrotikJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ReconcileMikrotikJob)->everyThirtyMinutes();

Schedule::job(new SyncAccountingJob)->everyMinute();

Schedule::job(new CleanupOldRecordsJob)->dailyAt('02:00');
