<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::command('orders:process-pending')->everyMinute();
Schedule::command('orders:notify-vendors')->everyMinute();
Schedule::command('queue:work --queue=notify-vendor-sub-order --tries=1 --daemon')->withoutOverlapping();
Schedule::command('queue:work --queue=process-order --tries=1 --daemon')->withoutOverlapping();



