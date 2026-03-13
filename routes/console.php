<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-delete demo companies older than 1 week, runs daily at midnight
Schedule::command('demo:cleanup', ['--older-than' => 168, '--no-interaction' => true])
    ->daily()
    ->withoutOverlapping();
