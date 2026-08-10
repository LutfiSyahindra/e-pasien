<?php

namespace App\Services\epasien\menu;

use App\Repositories\epasien\menu\DoctorArrivalRepository;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DoctorQueueService
{
    private const HOSPITAL_TIMEZONE = 'Asia/Jakarta';

    private const CACHE_SECONDS = 5;

    public function __construct(private readonly DoctorArrivalRepository $repository) {}

    /**
     * @param  array<string, mixed>|null  $patientRegistration
     */
    public function current(
        ?array $patientRegistration = null,
        ?CarbonInterface $at = null,
    ): Collection {
        $now = $at
            ? Carbon::instance($at)->setTimezone(self::HOSPITAL_TIMEZONE)
            : now(self::HOSPITAL_TIMEZONE);
        $serviceDate = $now->toDateString();
        $calls = Cache::remember(
            'epasien:khanza:doctor-queues:v3:'.$serviceDate,
            self::CACHE_SECONDS,
            fn (): Collection => $this->repository->currentQueues($serviceDate),
        );

        $patientQueue = $this->patientQueueForDate($patientRegistration, $serviceDate);

        return collect($calls)
            ->map(fn (object $call): array => $this->formatCall($call, $patientQueue))
            ->unique(fn (array $call): string => $call['doctor_code'].'|'.$call['clinic_code'])
            ->sortBy([
                ['clinic_name', 'asc'],
                ['doctor_name', 'asc'],
            ], SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * @param  array<string, string>|null  $patientQueue
     * @return array<string, mixed>
     */
    private function formatCall(object $call, ?array $patientQueue): array
    {
        $doctorCode = trim((string) ($call->doctor_code ?? ''));
        $clinicCode = trim((string) ($call->clinic_code ?? ''));
        $currentNumber = trim((string) ($call->queue_number ?? ''));
        $queueState = in_array(($call->queue_state ?? null), ['calling', 'waiting'], true)
            ? $call->queue_state
            : 'calling';
        $servicedAt = $this->servicedAt($call->serviced_at ?? null);
        $isPatientQueue = $patientQueue !== null
            && $queueState === 'calling'
            && $doctorCode === $patientQueue['doctor_code']
            && $clinicCode === $patientQueue['clinic_code'];
        $remaining = $isPatientQueue
            ? $this->remainingQueueNumbers($currentNumber, $patientQueue['queue_number'])
            : null;

        return [
            'id' => md5($doctorCode.'|'.$clinicCode),
            'doctor_code' => $doctorCode,
            'doctor_name' => $this->text($call->doctor_name ?? null, 'Dokter belum tercatat'),
            'clinic_code' => $clinicCode,
            'clinic_name' => $this->text($call->clinic_name ?? null, 'Poliklinik'),
            'current_number' => $currentNumber !== '' ? $currentNumber : '-',
            'last_serviced_number' => $this->nullableText($call->last_serviced_number ?? null),
            'queue_state' => $queueState,
            'number_label' => $queueState === 'calling' ? 'Sedang dipanggil' : 'Terakhir dilayani',
            'queue_message' => $queueState === 'calling'
                ? 'Panggilan antrean sedang berlangsung.'
                : 'Belum ada antrean berikutnya yang menunggu.',
            'serviced_at' => $servicedAt?->toIso8601String(),
            'serviced_at_label' => $servicedAt ? $servicedAt->format('H:i').' WIB' : null,
            'is_patient_queue' => $isPatientQueue,
            'patient_number' => $isPatientQueue ? $patientQueue['queue_number'] : null,
            'remaining_before_patient' => $remaining,
            'patient_message' => $isPatientQueue
                ? $this->patientMessage($remaining)
                : null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $registration
     * @return array<string, string>|null
     */
    private function patientQueueForDate(?array $registration, string $serviceDate): ?array
    {
        if (! $registration || trim((string) ($registration['tanggal'] ?? '')) !== $serviceDate) {
            return null;
        }

        $doctorCode = trim((string) ($registration['kd_dokter'] ?? ''));
        $clinicCode = trim((string) ($registration['kd_poli'] ?? ''));
        $queueNumber = trim((string) ($registration['no_reg'] ?? ''));

        if ($doctorCode === '' || $clinicCode === '' || $queueNumber === '') {
            return null;
        }

        return [
            'doctor_code' => $doctorCode,
            'clinic_code' => $clinicCode,
            'queue_number' => $queueNumber,
        ];
    }

    private function remainingQueueNumbers(string $currentNumber, string $patientNumber): ?int
    {
        if (! ctype_digit($currentNumber) || ! ctype_digit($patientNumber)) {
            return null;
        }

        return (int) $patientNumber - (int) $currentNumber;
    }

    private function patientMessage(?int $remaining): string
    {
        return match (true) {
            $remaining === null => 'Pantau panggilan petugas untuk antrean Anda.',
            $remaining === 0 => 'Antrean Anda sedang dipanggil. Silakan menuju poli sekarang.',
            $remaining > 0 => 'Sekitar '.$remaining.' nomor lagi menuju antrean Anda.',
            default => 'Nomor antrean Anda telah terlewati. Silakan hubungi petugas poli.',
        };
    }

    private function servicedAt(mixed $value): ?Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value, self::HOSPITAL_TIMEZONE);
        } catch (\Throwable) {
            return null;
        }
    }

    private function text(mixed $value, string $fallback): string
    {
        return trim((string) $value) ?: $fallback;
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
