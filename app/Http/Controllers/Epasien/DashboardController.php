<?php

namespace App\Http\Controllers\Epasien;

use App\Http\Controllers\Controller;
use App\Services\epasien\menu\DaftarOnlineService;
use App\Services\epasien\menu\DoctorQueueService;
use App\Services\epasien\menu\JadwalDokterService;
use App\Services\epasien\menu\PromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    private const PATIENT_TIMEZONE = 'Asia/Jakarta';

    public function __construct(
        private readonly DaftarOnlineService $daftarOnlineService,
        private readonly DoctorQueueService $doctorQueueService,
        private readonly JadwalDokterService $jadwalDokterService,
        private readonly PromotionService $promotionService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $dashboardNow = now(self::PATIENT_TIMEZONE);
        $promotions = new Collection;
        $upcomingRegistration = null;
        $doctorQueues = new Collection;
        $doctorSchedules = new Collection;
        $sectionErrors = [];

        try {
            $promotions = $this->promotionService->latestActive($dashboardNow, 4);
        } catch (Throwable $exception) {
            $sectionErrors['promotions'] = true;
            $this->logSectionFailure('promotions', $user?->getKey(), $exception);
        }

        try {
            $upcomingRegistration = $this->daftarOnlineService
                ->upcomingRegistration($user);
        } catch (Throwable $exception) {
            $sectionErrors['registration'] = true;
            $this->logSectionFailure('registration', $user?->getKey(), $exception);
        }

        try {
            $doctorQueues = $this->doctorQueueService
                ->current($upcomingRegistration);
        } catch (Throwable $exception) {
            $sectionErrors['queues'] = true;
            $this->logSectionFailure('queues', $user?->getKey(), $exception);
        }

        try {
            $doctorSchedules = $this->jadwalDokterService->today(6);
        } catch (Throwable $exception) {
            $sectionErrors['schedules'] = true;
            $this->logSectionFailure('schedules', $user?->getKey(), $exception);
        }

        return view('e-pasien.dashboard', [
            'promotions' => $promotions,
            'upcomingRegistration' => $upcomingRegistration,
            'doctorQueues' => $doctorQueues,
            'doctorSchedules' => $doctorSchedules,
            'sectionErrors' => $sectionErrors,
            'todayLabel' => ucfirst(
                $dashboardNow->locale('id')->translatedFormat('l, d F Y')
            ),
        ]);
    }

    public function doctorQueues(Request $request): JsonResponse
    {
        $user = $request->user();
        $registration = null;

        try {
            $registration = $this->daftarOnlineService->upcomingRegistration($user);
        } catch (Throwable $exception) {
            $this->logSectionFailure('registration-queues-api', $user?->getKey(), $exception);
        }

        try {
            $queues = $this->doctorQueueService->current($registration);
            $refreshedAt = now(self::PATIENT_TIMEZONE);

            return response()->json([
                'data' => $queues->values()->all(),
                'meta' => [
                    'refreshed_at' => $refreshedAt->toIso8601String(),
                    'refreshed_at_label' => $refreshedAt->format('H:i').' WIB',
                ],
            ]);
        } catch (Throwable $exception) {
            $this->logSectionFailure('queues-api', $user?->getKey(), $exception);

            return response()->json([
                'message' => 'Antrean poli belum dapat diperbarui.',
            ], 503);
        }
    }

    private function logSectionFailure(
        string $section,
        int|string|null $userId,
        Throwable $exception,
    ): void {
        Log::warning('Bagian dashboard pasien gagal dimuat.', [
            'section' => $section,
            'user_id' => $userId,
            'message' => $exception->getMessage(),
        ]);
    }
}
