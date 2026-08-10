<?php

namespace App\Repositories\epasien\menu;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DoctorArrivalRepository
{
    /**
     * Return the patient registration represented by every queue that is
     * currently being called.
     */
    public function calledPatientQueues(string $serviceDate): Collection
    {
        $calls = $this->currentQueues($serviceDate)
            ->where('queue_state', 'calling')
            ->filter(fn (object $queue): bool => trim((string) ($queue->doctor_code ?? '')) !== ''
                && trim((string) ($queue->clinic_code ?? '')) !== ''
                && trim((string) ($queue->queue_number ?? '')) !== ''
            )
            ->keyBy(fn (object $queue): string => $this->queueCallKey($queue));

        if ($calls->isEmpty()) {
            return collect();
        }

        return $this->connection()
            ->table('reg_periksa as registration')
            ->where('registration.tgl_registrasi', $serviceDate)
            ->where('registration.stts', 'Belum')
            ->where(function (Builder $query) use ($calls): void {
                foreach ($calls as $call) {
                    $query->orWhere(function (Builder $candidate) use ($call): void {
                        $candidate
                            ->where('registration.kd_dokter', trim((string) $call->doctor_code))
                            ->where('registration.kd_poli', trim((string) $call->clinic_code))
                            ->where('registration.no_reg', trim((string) $call->queue_number));
                    });
                }
            })
            ->whereNotNull('registration.no_rkm_medis')
            ->where('registration.no_rkm_medis', '<>', '')
            ->select([
                'registration.no_rawat as visit_number',
                'registration.no_reg as queue_number',
                'registration.no_rkm_medis as medical_record_number',
                'registration.kd_dokter as doctor_code',
                'registration.kd_poli as clinic_code',
            ])
            ->get()
            ->map(function (object $registration) use ($calls): object {
                $call = $calls->get($this->queueCallKey($registration));

                $registration->visit_number = trim((string) $registration->visit_number);
                $registration->queue_number = trim((string) $registration->queue_number);
                $registration->medical_record_number = trim((string) $registration->medical_record_number);
                $registration->doctor_code = trim((string) $registration->doctor_code);
                $registration->clinic_code = trim((string) $registration->clinic_code);
                $registration->doctor_name = trim((string) ($call?->doctor_name ?? '')) ?: null;
                $registration->clinic_name = trim((string) ($call?->clinic_name ?? '')) ?: null;
                $registration->called_at = $call?->serviced_at ?? null;

                return $registration;
            })
            ->filter(fn (object $registration): bool => $registration->visit_number !== ''
                && $registration->medical_record_number !== ''
            )
            ->values();
    }

    public function detectedSchedules(string $serviceDate, int $minimumExaminations = 2): Collection
    {
        $qualifiedTreatments = $this->qualifiedTreatments(
            $serviceDate,
            $minimumExaminations
        );

        return $this->connection()
            ->query()
            ->fromSub($qualifiedTreatments, 'qualified_treatments')
            ->leftJoin(
                'dokter',
                'dokter.kd_dokter',
                '=',
                'qualified_treatments.kd_dokter'
            )
            ->leftJoin(
                'poliklinik',
                'poliklinik.kd_poli',
                '=',
                'qualified_treatments.kd_poli'
            )
            ->select([
                'qualified_treatments.kd_dokter as doctor_code',
                'qualified_treatments.kd_poli as clinic_code',
                'dokter.nm_dokter as doctor_name',
                'poliklinik.nm_poli as clinic_name',
            ])
            ->distinct()
            ->orderBy('qualified_treatments.kd_dokter')
            ->orderBy('qualified_treatments.kd_poli')
            ->get();
    }

    /**
     * Return the latest serviced queue number for every doctor and clinic that
     * satisfies the same arrival signal used by detectedSchedules().
     */
    public function currentQueues(string $serviceDate, int $minimumExaminations = 2): Collection
    {
        $qualifiedTreatments = $this->qualifiedTreatments(
            $serviceDate,
            $minimumExaminations
        );

        $lastServicedQueues = $this->connection()
            ->query()
            ->fromSub($qualifiedTreatments, 'qualified_treatments')
            ->join(
                'reg_periksa as registration',
                'registration.no_rawat',
                '=',
                'qualified_treatments.no_rawat'
            )
            ->join(
                'pemeriksaan_ralan as examination',
                'examination.no_rawat',
                '=',
                'qualified_treatments.no_rawat'
            )
            ->leftJoin(
                'dokter',
                'dokter.kd_dokter',
                '=',
                'qualified_treatments.kd_dokter'
            )
            ->leftJoin(
                'poliklinik',
                'poliklinik.kd_poli',
                '=',
                'qualified_treatments.kd_poli'
            )
            ->select([
                'registration.no_reg as queue_number',
                'qualified_treatments.kd_dokter as doctor_code',
                'qualified_treatments.kd_poli as clinic_code',
                'dokter.nm_dokter as doctor_name',
                'poliklinik.nm_poli as clinic_name',
                'examination.tgl_perawatan as serviced_date',
                'examination.jam_rawat as serviced_time',
            ])
            ->orderByDesc('examination.tgl_perawatan')
            ->orderByDesc('examination.jam_rawat')
            ->get()
            ->unique(
                fn (object $queue): string => trim((string) $queue->doctor_code)
                    .'|'.trim((string) $queue->clinic_code)
            )
            ->map(function (object $queue): object {
                $servicedDate = trim((string) ($queue->serviced_date ?? ''));
                $servicedTime = trim((string) ($queue->serviced_time ?? ''));

                $queue->serviced_at = trim($servicedDate.' '.$servicedTime) ?: null;
                unset(
                    $queue->serviced_date,
                    $queue->serviced_time,
                );

                return $queue;
            })
            ->values();

        $waitingBySchedule = $this->waitingQueues(
            $serviceDate,
            $minimumExaminations
        )->groupBy(fn (object $queue): string => $this->scheduleKey($queue));

        return $lastServicedQueues
            ->map(function (object $queue) use ($waitingBySchedule): object {
                $lastServicedNumber = trim((string) ($queue->queue_number ?? ''));
                $nextQueue = $waitingBySchedule
                    ->get($this->scheduleKey($queue), collect())
                    ->first(
                        fn (object $candidate): bool => $this->queueNumberIsAfter(
                            (string) ($candidate->queue_number ?? ''),
                            $lastServicedNumber
                        )
                    );

                $queue->last_serviced_number = $lastServicedNumber ?: null;
                $queue->queue_state = $nextQueue ? 'calling' : 'waiting';

                if ($nextQueue) {
                    $queue->queue_number = trim((string) $nextQueue->queue_number);
                }

                return $queue;
            })
            ->values();
    }

    public function medicalRecordNumbersForSchedule(
        string $serviceDate,
        string $doctorCode,
        string $clinicCode
    ): Collection {
        return $this->connection()
            ->table('reg_periksa')
            ->where('tgl_registrasi', $serviceDate)
            ->where('kd_dokter', $doctorCode)
            ->where('kd_poli', $clinicCode)
            ->where('stts', '<>', 'Batal')
            ->whereNotNull('no_rkm_medis')
            ->where('no_rkm_medis', '<>', '')
            ->distinct()
            ->pluck('no_rkm_medis')
            ->map(fn (mixed $number): string => trim((string) $number))
            ->filter()
            ->values();
    }

    private function qualifiedTreatments(
        string $serviceDate,
        int $minimumExaminations
    ): Builder {
        return $this->connection()
            ->table('reg_periksa as registration')
            ->join(
                'pemeriksaan_ralan as examination',
                'examination.no_rawat',
                '=',
                'registration.no_rawat'
            )
            ->where('registration.tgl_registrasi', $serviceDate)
            ->where('registration.stts', '<>', 'Batal')
            ->groupBy(
                'registration.no_rawat',
                'registration.kd_dokter',
                'registration.kd_poli'
            )
            ->havingRaw('COUNT(*) >= ?', [max(2, $minimumExaminations)])
            ->select([
                'registration.no_rawat',
                'registration.kd_dokter',
                'registration.kd_poli',
            ]);
    }

    private function waitingQueues(
        string $serviceDate,
        int $minimumExaminations
    ): Collection {
        return $this->connection()
            ->table('reg_periksa as registration')
            ->leftJoin(
                'pemeriksaan_ralan as examination',
                'examination.no_rawat',
                '=',
                'registration.no_rawat'
            )
            ->where('registration.tgl_registrasi', $serviceDate)
            ->where('registration.stts', 'Belum')
            ->select([
                'registration.no_reg as queue_number',
                'registration.kd_dokter as doctor_code',
                'registration.kd_poli as clinic_code',
            ])
            ->groupBy(
                'registration.no_rawat',
                'registration.no_reg',
                'registration.kd_dokter',
                'registration.kd_poli'
            )
            ->havingRaw(
                'COUNT(examination.no_rawat) < ?',
                [max(2, $minimumExaminations)]
            )
            ->get()
            ->sortBy([
                [fn (object $queue): int => $this->queueNumberValue($queue->queue_number ?? null), 'asc'],
                ['queue_number', 'asc'],
            ])
            ->values();
    }

    private function scheduleKey(object $queue): string
    {
        return trim((string) ($queue->doctor_code ?? ''))
            .'|'.trim((string) ($queue->clinic_code ?? ''));
    }

    private function queueCallKey(object $queue): string
    {
        return $this->scheduleKey($queue)
            .'|'.trim((string) ($queue->queue_number ?? ''));
    }

    private function queueNumberIsAfter(string $candidate, string $current): bool
    {
        $candidate = trim($candidate);
        $current = trim($current);

        if (ctype_digit($candidate) && ctype_digit($current)) {
            return (int) $candidate > (int) $current;
        }

        return $candidate !== '' && strnatcasecmp($candidate, $current) > 0;
    }

    private function queueNumberValue(mixed $queueNumber): int
    {
        $queueNumber = trim((string) $queueNumber);

        return ctype_digit($queueNumber) ? (int) $queueNumber : PHP_INT_MAX;
    }

    private function connection(): Connection
    {
        return DB::connection('mysql_khanza');
    }
}
