<?php

namespace App\Services\epasien\menu;

use App\Jobs\DispatchPatientQueueCalledNotification;
use App\Models\PatientQueueCallEvent;
use App\Repositories\epasien\menu\DoctorArrivalRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PatientQueueNotificationService
{
    private const HOSPITAL_TIMEZONE = 'Asia/Jakarta';

    public function __construct(private readonly DoctorArrivalRepository $repository) {}

    public function dispatchDetected(?string $serviceDate = null): int
    {
        $serviceDate ??= now(self::HOSPITAL_TIMEZONE)->toDateString();
        $queued = 0;

        foreach ($this->repository->calledPatientQueues($serviceDate) as $queue) {
            if ($this->queuePatient($serviceDate, $queue)) {
                $queued++;
            }
        }

        return $queued;
    }

    private function queuePatient(string $serviceDate, object $queue): bool
    {
        $visitNumber = trim((string) ($queue->visit_number ?? ''));
        $medicalRecordNumber = trim((string) ($queue->medical_record_number ?? ''));

        if ($visitNumber === '' || $medicalRecordNumber === '') {
            return false;
        }

        $event = PatientQueueCallEvent::query()->firstOrCreate(
            [
                'service_date' => $serviceDate,
                'visit_number' => $visitNumber,
            ],
            [
                'medical_record_number' => $medicalRecordNumber,
                'queue_number' => $this->text($queue->queue_number ?? null),
                'doctor_code' => $this->text($queue->doctor_code ?? null),
                'clinic_code' => $this->text($queue->clinic_code ?? null),
                'doctor_name' => $this->nullableText($queue->doctor_name ?? null),
                'clinic_name' => $this->nullableText($queue->clinic_name ?? null),
                'called_at' => $this->calledAt($queue->called_at ?? null),
                'detected_at' => now(),
            ],
        );

        $claimed = DB::connection(config('database.default'))->transaction(function () use ($event): ?PatientQueueCallEvent {
            $candidate = PatientQueueCallEvent::query()->lockForUpdate()->find($event->getKey());

            if (! $candidate || $candidate->queued_at || $candidate->dispatched_at) {
                return null;
            }

            $candidate->forceFill(['queued_at' => now()])->save();

            return $candidate;
        });

        if (! $claimed) {
            return false;
        }

        DispatchPatientQueueCalledNotification::dispatch($claimed->getKey())->afterCommit();

        return true;
    }

    private function text(mixed $value): string
    {
        return trim((string) $value);
    }

    private function nullableText(mixed $value): ?string
    {
        $value = $this->text($value);

        return $value !== '' ? $value : null;
    }

    private function calledAt(mixed $value): ?Carbon
    {
        $value = $this->nullableText($value);

        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value, self::HOSPITAL_TIMEZONE)->utc();
        } catch (\Throwable) {
            return null;
        }
    }
}
