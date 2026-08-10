<?php

namespace App\Jobs;

use App\Models\DoctorArrivalEvent;
use App\Models\User;
use App\Notifications\DoctorArrivalNotification;
use App\Repositories\epasien\menu\DoctorArrivalRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;
use Throwable;

class DispatchDoctorArrivalNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly int $eventId) {}

    public function handle(DoctorArrivalRepository $repository): void
    {
        $event = DoctorArrivalEvent::query()->find($this->eventId);

        if (! $event || $event->dispatched_at) {
            return;
        }

        $medicalRecordNumbers = $repository->medicalRecordNumbersForSchedule(
            $event->service_date->toDateString(),
            $event->doctor_code,
            $event->clinic_code,
        );

        $recipientsCount = 0;
        $notification = DoctorArrivalNotification::fromEvent($event);

        if ($medicalRecordNumbers->isNotEmpty()) {
            User::query()
                ->where(function (Builder $query): void {
                    $query->where('status', true)->orWhereNull('status');
                })
                ->whereIn('username', $medicalRecordNumbers)
                ->whereHas('roles', function (Builder $roles): void {
                    $roles
                        ->where('roles.guard_name', 'web')
                        ->where('roles.doctor_arrival_notifications_enabled', true);
                })
                ->chunkById(250, function ($patients) use ($notification, &$recipientsCount): void {
                    $recipientsCount += $patients->count();
                    Notification::send($patients, $notification);
                });
        }

        $event->forceFill([
            'dispatched_at' => now(),
            'recipients_count' => $recipientsCount,
        ])->save();
    }

    public function failed(?Throwable $exception): void
    {
        DoctorArrivalEvent::query()
            ->whereKey($this->eventId)
            ->whereNull('dispatched_at')
            ->update(['queued_at' => null]);
    }
}
