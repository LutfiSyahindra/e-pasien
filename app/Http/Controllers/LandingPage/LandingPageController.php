<?php

namespace App\Http\Controllers\LandingPage;

use App\Http\Controllers\Controller;
use App\Services\epasien\menu\JadwalDokterService;
use App\Services\LandingPage\LandingDoctorService;
use App\Support\Epasien\DoctorScheduleDay;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class LandingPageController extends Controller
{
    public function __construct(
        private readonly LandingDoctorService $landingDoctorService,
        private readonly JadwalDokterService $jadwalDokterService
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'hari' => ['nullable', 'string', Rule::in(DoctorScheduleDay::ACCEPTED_FILTER_DAYS)],
            'q' => ['nullable', 'string', 'max:80'],
        ]);
        $landingDoctors = [];
        $landingScheduleError = false;
        $today = now('Asia/Jakarta')->locale('id');
        $todayDay = DoctorScheduleDay::EPASIEN_DAYS[$today->isoWeekday() - 1];
        $selectedDay = DoctorScheduleDay::toEpasien($validated['hari'] ?? $todayDay);
        $selectedDate = $selectedDay === 'SEMUA'
            ? null
            : $today->copy()->startOfWeek()->addDays(array_search($selectedDay, DoctorScheduleDay::EPASIEN_DAYS, true));
        $scheduleLabel = $selectedDay === 'SEMUA'
            ? 'semua hari'
            : ($selectedDay === $todayDay ? 'hari ini' : 'hari '.$selectedDate->translatedFormat('l'));
        $landingSchedulePaginator = new LengthAwarePaginator([], 0, 6, LengthAwarePaginator::resolveCurrentPage(), [
            'path' => route('landingPage.index'),
        ]);

        try {
            $landingDoctors = $this->landingDoctorService->featured();
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat dokter untuk landing page.', [
                'message' => $exception->getMessage(),
            ]);
        }

        try {
            $page = $this->jadwalDokterService->page(
                trim((string) ($validated['q'] ?? '')),
                $selectedDay,
                '',
                6
            );
            $landingSchedulePaginator = $page['schedules'];
        } catch (Throwable $exception) {
            $landingScheduleError = true;

            Log::warning('Gagal memuat jadwal dokter untuk landing page.', [
                'message' => $exception->getMessage(),
            ]);
        }

        $landingSchedulePaginator->appends($validated)->fragment('jadwal-praktik');
        $landingSchedules = $landingSchedulePaginator->getCollection()->map(function (array $schedule) use ($selectedDate): array {
            if ($selectedDate !== null && isset($schedule['leave_dates'])) {
                $schedule['is_on_leave'] = in_array($selectedDate->toDateString(), $schedule['leave_dates'], true);
            }

            return $schedule;
        });

        return view('landingPage.landingPage', [
            'landingDoctors' => $landingDoctors,
            'landingSchedules' => $landingSchedules,
            'landingScheduleError' => $landingScheduleError,
            'landingSchedulePaginator' => $landingSchedulePaginator,
            'landingScheduleLabel' => $scheduleLabel,
            'landingScheduleDate' => $selectedDate?->translatedFormat('l, d F Y') ?? 'Semua hari',
            'landingScheduleMonth' => $today->translatedFormat('F Y'),
            'landingScheduleDays' => collect(range(0, 6))->map(function (int $offset) use ($today, $selectedDay): array {
                $date = $today->copy()->startOfWeek()->addDays($offset);
                $key = DoctorScheduleDay::EPASIEN_DAYS[$offset];

                return [
                    'key' => $key,
                    'label' => $date->translatedFormat('D'),
                    'full_label' => $date->translatedFormat('l'),
                    'date' => $date->format('d'),
                    'month' => $date->translatedFormat('M'),
                    'is_today' => $date->isSameDay($today),
                    'is_selected' => $key === $selectedDay,
                ];
            }),
        ]);
    }
}
