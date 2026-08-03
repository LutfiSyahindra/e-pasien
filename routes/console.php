<?php

use App\Services\epasien\menu\PromotionNotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => app(PromotionNotificationService::class)->dispatchDue())
    ->name('dispatch-due-promotion-notifications')
    ->everyMinute()
    ->withoutOverlapping();
