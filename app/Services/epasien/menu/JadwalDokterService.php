<?php

namespace App\Services\epasien\menu;

use App\Repositories\epasien\menu\JadwalDokterRepository;
use App\Repositories\epasien\settings\DoctorPhotoRepository;
use App\Support\Epasien\DoctorScheduleDay;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class JadwalDokterService
{
    private const PATIENT_TIMEZONE = 'Asia/Jakarta';

    private const LIST_CACHE_FRESH_SECONDS = 60;

    private const LIST_CACHE_STALE_SECONDS = 900;

    private const REFERENCE_CACHE_FRESH_SECONDS = 300;

    private const REFERENCE_CACHE_STALE_SECONDS = 3600;

    private const DAYS = [
        'SENIN' => ['label' => 'Senin', 'short' => 'Sen'],
        'SELASA' => ['label' => 'Selasa', 'short' => 'Sel'],
        'RABU' => ['label' => 'Rabu', 'short' => 'Rab'],
        'KAMIS' => ['label' => 'Kamis', 'short' => 'Kam'],
        'JUMAT' => ['label' => 'Jumat', 'short' => 'Jum'],
        'SABTU' => ['label' => 'Sabtu', 'short' => 'Sab'],
        'MINGGU' => ['label' => 'Minggu', 'short' => 'Min'],
    ];

    public function __construct(
        private readonly JadwalDokterRepository $jadwalDokterRepository,
        private readonly DoctorPhotoRepository $doctorPhotoRepository
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function page(
        ?string $search,
        ?string $day,
        ?string $clinicCode,
        int $perPage = 12
    ): array {
        $filters = $this->filters($search, $day, $clinicCode);
        $perPage = max(6, min($perPage, 24));
        $queryDay = $filters['day'] === 'SEMUA'
            ? null
            : DoctorScheduleDay::toDatabase($filters['day']);
        $search = $filters['search'] !== '' ? $filters['search'] : null;
        $clinicCode = $filters['clinic_code'] !== ''
            ? $filters['clinic_code']
            : null;
        $schedules = Cache::flexible(
            $this->scheduleCacheKey($search, $queryDay, $clinicCode, $perPage),
            [
                self::LIST_CACHE_FRESH_SECONDS,
                self::LIST_CACHE_STALE_SECONDS,
            ],
            function () use ($search, $queryDay, $clinicCode, $perPage): LengthAwarePaginator {
                $schedules = $this->jadwalDokterRepository->paginateSchedules(
                    $search,
                    $queryDay,
                    $clinicCode,
                    $perPage
                );
                $schedules->setCollection(
                    $schedules->getCollection()->map(
                        fn (object $schedule): array => $this->formatSchedule($schedule)
                    )
                );

                return $schedules;
            }
        );
        $schedules->setPath(LengthAwarePaginator::resolveCurrentPath());
        $this->attachDoctorPhotos($schedules);

        $summary = Cache::flexible(
            'epasien:khanza:doctor-schedules:summary:v1',
            [
                self::REFERENCE_CACHE_FRESH_SECONDS,
                self::REFERENCE_CACHE_STALE_SECONDS,
            ],
            fn (): ?object => $this->jadwalDokterRepository->summary()
        );
        $clinics = Cache::flexible(
            'epasien:khanza:doctor-schedules:clinics:v1',
            [
                self::REFERENCE_CACHE_FRESH_SECONDS,
                self::REFERENCE_CACHE_STALE_SECONDS,
            ],
            fn (): Collection => $this->jadwalDokterRepository->clinics()
        );

        return [
            ...$filters,
            'days' => $this->days(),
            'schedules' => $schedules,
            'clinics' => $clinics
                ->map(fn (object $clinic): array => [
                    'code' => $this->text($clinic->kd_poli ?? null),
                    'name' => $this->text($clinic->nm_poli ?? null),
                ])
                ->values()
                ->all(),
            'summary' => [
                'schedules' => (int) ($summary?->schedules ?? 0),
                'doctors' => (int) ($summary?->doctors ?? 0),
                'clinics' => (int) ($summary?->clinics ?? 0),
            ],
        ];
    }

    private function scheduleCacheKey(
        ?string $search,
        ?string $day,
        ?string $clinicCode,
        int $perPage
    ): string {
        $filters = json_encode([
            'search' => $search,
            'day' => $day,
            'clinic' => $clinicCode,
            'per_page' => $perPage,
            'page' => LengthAwarePaginator::resolveCurrentPage(),
        ], JSON_THROW_ON_ERROR);

        return 'epasien:khanza:doctor-schedules:list:v3:'.hash('sha256', $filters);
    }

    /**
     * @return array<string, mixed>
     */
    public function emptyPage(
        ?string $search,
        ?string $day,
        ?string $clinicCode
    ): array {
        return [
            ...$this->filters($search, $day, $clinicCode),
            'days' => $this->days(),
            'schedules' => new LengthAwarePaginator(
                [],
                0,
                12,
                LengthAwarePaginator::resolveCurrentPage(),
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            ),
            'clinics' => [],
            'summary' => [
                'schedules' => 0,
                'doctors' => 0,
                'clinics' => 0,
            ],
        ];
    }

    public function currentDay(): string
    {
        return match (now(self::PATIENT_TIMEZONE)->dayOfWeek) {
            CarbonInterface::MONDAY => 'SENIN',
            CarbonInterface::TUESDAY => 'SELASA',
            CarbonInterface::WEDNESDAY => 'RABU',
            CarbonInterface::THURSDAY => 'KAMIS',
            CarbonInterface::FRIDAY => 'JUMAT',
            CarbonInterface::SATURDAY => 'SABTU',
            default => 'MINGGU',
        };
    }

    /**
     * @return array<string, array{label: string, short: string}>
     */
    private function days(): array
    {
        return [
            'SEMUA' => ['label' => 'Semua hari', 'short' => 'Semua'],
            ...self::DAYS,
        ];
    }

    /**
     * @return array{
     *     search: string,
     *     day: string,
     *     day_label: string,
     *     clinic_code: string
     * }
     */
    private function filters(
        ?string $search,
        ?string $day,
        ?string $clinicCode
    ): array {
        $day = DoctorScheduleDay::toEpasien($day);
        $day = isset(self::DAYS[$day]) || $day === 'SEMUA'
            ? $day
            : $this->currentDay();

        return [
            'search' => trim((string) $search),
            'day' => $day,
            'day_label' => $day === 'SEMUA'
                ? 'Semua hari'
                : self::DAYS[$day]['label'],
            'clinic_code' => trim((string) $clinicCode),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatSchedule(object $schedule): array
    {
        $doctorName = $this->text($schedule->nm_dokter ?? null);
        $day = DoctorScheduleDay::toEpasien(
            $this->text($schedule->hari_kerja ?? null)
        );
        $quota = max(0, (int) ($schedule->kuota ?? 0));

        return [
            'doctor_code' => $this->text($schedule->kd_dokter ?? null),
            'doctor_name' => $doctorName,
            'doctor_initials' => $this->initials($doctorName),
            'doctor_photo_url' => null,
            'gender_icon' => ($schedule->jk ?? null) === 'P'
                ? 'bi-person-heart'
                : 'bi-person',
            'clinic_code' => $this->text($schedule->kd_poli ?? null),
            'clinic_name' => $this->text($schedule->nm_poli ?? null),
            'day' => $day,
            'day_label' => self::DAYS[$day]['label'] ?? ucfirst(strtolower($day)),
            'time_start' => $this->formatTime($schedule->jam_mulai ?? null),
            'time_end' => $this->formatTime($schedule->jam_selesai ?? null),
            'time_label' => $this->timeLabel(
                $schedule->jam_mulai ?? null,
                $schedule->jam_selesai ?? null
            ),
            'quota' => $quota,
            'quota_label' => $quota > 0
                ? number_format($quota, 0, ',', '.').' pasien'
                : 'Konfirmasi petugas',
            'is_today' => $day === $this->currentDay(),
        ];
    }

    private function timeLabel(mixed $start, mixed $end): string
    {
        $start = $this->formatTime($start);
        $end = $this->formatTime($end);

        if ($start === '-') {
            return 'Jam belum tersedia';
        }

        return $end === '-' ? $start.' WIB' : $start.' – '.$end.' WIB';
    }

    private function formatTime(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '00:00:00') {
            return $value === '00:00:00' ? '00.00' : '-';
        }

        return str_replace(':', '.', substr($value, 0, 5));
    }

    private function initials(string $name): string
    {
        $name = preg_replace('/\bdr\.?\b/iu', '', $name) ?? $name;
        preg_match_all('/[\p{L}]+/u', $name, $matches);
        $words = array_slice($matches[0] ?? [], 0, 2);

        if ($words === []) {
            return 'DR';
        }

        return mb_strtoupper(implode('', array_map(
            fn (string $word): string => mb_substr($word, 0, 1),
            $words
        )));
    }

    private function attachDoctorPhotos(LengthAwarePaginator $schedules): void
    {
        if ($schedules->isEmpty()) {
            return;
        }

        $photoUrls = $this->doctorPhotoRepository->urlsForCodes(
            $schedules->getCollection()->pluck('doctor_code')->all()
        );

        $schedules->setCollection($schedules->getCollection()->map(
            function (array $schedule) use ($photoUrls): array {
                $schedule['doctor_photo_url'] = $photoUrls[$schedule['doctor_code']] ?? null;

                return $schedule;
            }
        ));
    }

    private function text(mixed $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : '-';
    }
}
