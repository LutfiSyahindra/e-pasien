<?php

namespace App\Jobs;

use App\Models\PatientQueueCallEvent;
use App\Models\User;
use App\Notifications\PatientQueueCalledNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;
use Throwable;

class DispatchPatientQueueCalledNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly int $eventId) {}

    public function handle(): void
    {
        $event = PatientQueueCallEvent::query()->find($this->eventId);

        if (! $event || $event->dispatched_at) {
            return;
        }

        $patientRoleNames = collect([
            config('access-control.patient_role', 'Patient'),
            ...config('access-control.patient_role_aliases', ['Pasien']),
        ])
            ->filter(fn (mixed $role): bool => is_string($role) && trim($role) !== '')
            ->map(fn (string $role): string => trim($role))
            ->unique()
            ->values();

        $recipients = User::query()
            ->where('username', $event->medical_record_number)
            ->where(function (Builder $query): void {
                $query->where('status', true)->orWhereNull('status');
            })
            ->whereHas('roles', function (Builder $roles) use ($patientRoleNames): void {
                $roles
                    ->where('roles.guard_name', 'web')
                    ->whereIn('roles.name', $patientRoleNames);
            })
            ->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, PatientQueueCalledNotification::fromEvent($event));
        }

        $event->forceFill([
            'dispatched_at' => now(),
            'recipients_count' => $recipients->count(),
        ])->save();
    }

    public function failed(?Throwable $exception): void
    {
        PatientQueueCallEvent::query()
            ->whereKey($this->eventId)
            ->whereNull('dispatched_at')
            ->update(['queued_at' => null]);
    }
}
