<?php

namespace App\Services\epasien\settings;

use App\Repositories\epasien\settings\DoctorScheduleRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class DoctorScheduleService
{
    private const DAYS = [
        'SENIN' => 'Senin',
        'SELASA' => 'Selasa',
        'RABU' => 'Rabu',
        'KAMIS' => 'Kamis',
        'JUMAT' => 'Jumat',
        'SABTU' => 'Sabtu',
        'AKHAD' => 'Minggu',
    ];

    public function __construct(
        private readonly DoctorScheduleRepository $doctorScheduleRepository
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function page(
        ?string $search,
        ?string $day,
        ?string $clinicCode,
        int $perPage = 15
    ): array {
        $filters = $this->filters($search, $day, $clinicCode);
        $queryDay = $filters['day'] === 'SEMUA' ? null : $filters['day'];
        $schedules = $this->doctorScheduleRepository->paginate(
            $filters['search'] !== '' ? $filters['search'] : null,
            $queryDay,
            $filters['clinic_code'] !== '' ? $filters['clinic_code'] : null,
            max(10, min($perPage, 50))
        );

        $schedules->setCollection(
            $schedules->getCollection()->map(
                fn (object $schedule): array => $this->formatSchedule($schedule)
            )
        );

        $summary = $this->doctorScheduleRepository->summary();

        return [
            ...$filters,
            'days' => self::DAYS,
            'schedules' => $schedules,
            'clinics' => $this->doctorScheduleRepository->clinics()
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

    /**
     * @return array<string, mixed>
     */
    public function emptyPage(?string $search, ?string $day, ?string $clinicCode): array
    {
        return [
            ...$this->filters($search, $day, $clinicCode),
            'days' => self::DAYS,
            'schedules' => new LengthAwarePaginator(
                [],
                0,
                15,
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

    /**
     * @param  array{doctor_code: string, day: string, start_time: string}  $original
     * @param  array{day: string, start_time: string, end_time: string, quota: int}  $changes
     */
    public function update(array $original, array $changes): void
    {
        $this->doctorScheduleRepository->update($original, $changes);
    }

    /**
     * @return array{search: string, day: string, clinic_code: string}
     */
    private function filters(?string $search, ?string $day, ?string $clinicCode): array
    {
        $day = strtoupper(trim((string) $day));

        return [
            'search' => trim((string) $search),
            'day' => isset(self::DAYS[$day]) ? $day : 'SEMUA',
            'clinic_code' => trim((string) $clinicCode),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatSchedule(object $schedule): array
    {
        $day = strtoupper($this->text($schedule->hari_kerja ?? null));
        $startTime = $this->timeInput($schedule->jam_mulai ?? null);
        $endTime = $this->timeInput($schedule->jam_selesai ?? null);
        $quota = max(0, (int) ($schedule->kuota ?? 0));

        return [
            'doctor_code' => $this->text($schedule->kd_dokter ?? null),
            'doctor_name' => $this->text($schedule->nm_dokter ?? null),
            'clinic_code' => $this->text($schedule->kd_poli ?? null),
            'clinic_name' => $this->text($schedule->nm_poli ?? null),
            'day' => $day,
            'day_label' => self::DAYS[$day] ?? ucfirst(strtolower($day)),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'time_label' => ($startTime !== '' ? str_replace(':', '.', $startTime) : '-')
                .' – '
                .($endTime !== '' ? str_replace(':', '.', $endTime) : '-')
                .' WIB',
            'quota' => $quota,
            'quota_label' => number_format($quota, 0, ',', '.').' pasien',
        ];
    }

    private function timeInput(mixed $value): string
    {
        $value = trim((string) $value);

        return $value === '' ? '' : substr($value, 0, 5);
    }

    private function text(mixed $value): string
    {
        return trim((string) $value);
    }
}
