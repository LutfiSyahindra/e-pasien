<?php

namespace App\Jobs;

use App\Services\epasien\settings\auth\PatientUserSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SyncPatientUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1200;

    public function __construct(public int $roleId) {}

    /**
     * Execute the job.
     */
    public function handle(PatientUserSyncService $syncService): void
    {
        $syncService->markRunning();

        try {
            $summary = $syncService->sync($this->roleId, function (array $summary) use ($syncService): void {
                $syncService->markRunning($summary);
            });

            if ($summary['stopped'] ?? false) {
                $syncService->markStopped($summary);

                return;
            }

            $syncService->markCompleted($summary);
        } catch (Throwable $exception) {
            $syncService->markFailed($exception->getMessage());

            throw $exception;
        }
    }
}
