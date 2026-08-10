<?php

use App\Services\epasien\menu\DoctorArrivalNotificationService;
use App\Services\epasien\menu\PatientQueueNotificationService;
use App\Services\epasien\menu\PromotionNotificationService;
use App\Services\epasien\menu\PromotionService;
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

Schedule::call(fn () => app(PromotionService::class)->deleteExpired())
    ->name('delete-expired-promotions')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::call(fn () => app(DoctorArrivalNotificationService::class)->dispatchDetected())
    ->name('dispatch-doctor-arrival-notifications')
    ->everyMinute()
    ->withoutOverlapping(5);

Schedule::call(fn () => app(PatientQueueNotificationService::class)->dispatchDetected())
    ->name('dispatch-patient-queue-notifications')
    ->everyTenSeconds()
    ->withoutOverlapping(2);
