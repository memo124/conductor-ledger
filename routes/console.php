<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:create-yearly-partitions')
    ->monthlyOn(1, '02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/partitions.log'));

Schedule::job(new \App\Jobs\DatabaseBackupJob)
    ->monthlyOn(1, '03:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/backups.log'));
