<?php

namespace App\Services\epasien\menu;

use App\Jobs\DispatchDoctorArrivalNotification;
use App\Models\DoctorArrivalEvent;
use App\Repositories\epasien\menu\DoctorArrivalRepository;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class DoctorArrivalNotificationService
{
    private const HOSPITAL_TIMEZONE = 'Asia/Jakarta';

    public function __construct(private readonly DoctorArrivalRepository $repository) {}

    public function dispatchDetected(?string $serviceDate = null): int
    {
        if (! $this->hasEnabledRoles()) {
            return 0;
        }

        $serviceDate ??= now(self::HOSPITAL_TIMEZONE)->toDateString();
        $queued = 0;

        foreach ($this->repository->detectedSchedules($serviceDate) as $schedule) {
            if ($this->queueSchedule($serviceDate, $schedule)) {
                $queued++;
            }
        }

        return $queued;
    }

    private function hasEnabledRoles(): bool
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->where('doctor_arrival_notifications_enabled', true)
            ->exists();
    }

    private function queueSchedule(string $serviceDate, object $schedule): bool
    {
        $doctorCode = trim((string) ($schedule->doctor_code ?? ''));
        $clinicCode = trim((string) ($schedule->clinic_code ?? ''));

        if ($doctorCode === '' || $clinicCode === '') {
            return false;
        }

        $event = DoctorArrivalEvent::query()->firstOrCreate(
            [
                'service_date' => $serviceDate,
                'doctor_code' => $doctorCode,
                'clinic_code' => $clinicCode,
            ],
            [
                'doctor_name' => $this->text($schedule->doctor_name ?? null),
                'clinic_name' => $this->text($schedule->clinic_name ?? null),
                'detected_at' => now(),
            ],
        );

        $claimed = DB::connection(config('database.default'))->transaction(function () use ($event): ?DoctorArrivalEvent {
            $candidate = DoctorArrivalEvent::query()->lockForUpdate()->find($event->getKey());

            if (! $candidate || $candidate->queued_at || $candidate->dispatched_at) {
                return null;
            }

            $candidate->forceFill(['queued_at' => now()])->save();

            return $candidate;
        });

        if (! $claimed) {
            return false;
        }

        DispatchDoctorArrivalNotification::dispatch($claimed->getKey())->afterCommit();

        return true;
    }

    private function text(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
