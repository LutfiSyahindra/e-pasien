<?php

namespace App\Http\Controllers\LandingPage;

use App\Http\Controllers\Controller;
use App\Services\epasien\menu\JadwalDokterService;
use App\Services\LandingPage\LandingDoctorService;
use Illuminate\Support\Facades\Log;
use Throwable;

class LandingPageController extends Controller
{
    public function __construct(
        private readonly LandingDoctorService $landingDoctorService,
        private readonly JadwalDokterService $jadwalDokterService
    ) {}

    public function index()
    {
        $landingDoctors = [];
        $landingSchedules = collect();
        $landingScheduleError = false;
        $today = now('Asia/Jakarta')->locale('id');

        try {
            $landingDoctors = $this->landingDoctorService->featured();
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat dokter untuk landing page.', [
                'message' => $exception->getMessage(),
            ]);
        }

        try {
            $landingSchedules = $this->jadwalDokterService->today(6);
        } catch (Throwable $exception) {
            $landingScheduleError = true;

            Log::warning('Gagal memuat jadwal dokter untuk landing page.', [
                'message' => $exception->getMessage(),
            ]);
        }

        return view('landingPage.landingPage', [
            'landingDoctors' => $landingDoctors,
            'landingSchedules' => $landingSchedules,
            'landingScheduleError' => $landingScheduleError,
            'landingScheduleDate' => $today->translatedFormat('l, d F Y'),
            'landingScheduleMonth' => $today->translatedFormat('F Y'),
            'landingScheduleDays' => collect(range(0, 6))->map(function (int $offset) use ($today): array {
                $date = $today->copy()->startOfWeek()->addDays($offset);

                return [
                    'key' => mb_strtoupper($date->translatedFormat('l')),
                    'label' => $date->translatedFormat('D'),
                    'full_label' => $date->translatedFormat('l'),
                    'date' => $date->format('d'),
                    'month' => $date->translatedFormat('M'),
                    'is_today' => $date->isSameDay($today),
                ];
            }),
        ]);
    }
}
