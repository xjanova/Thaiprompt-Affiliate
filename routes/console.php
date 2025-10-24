<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Scheduled Commands
Schedule::command('commissions:process')->daily();
Schedule::command('ranks:update')->daily();
Schedule::command('withdrawals:process')->hourly();
