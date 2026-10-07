<?php

namespace App\Repositories\epasien\settings;

use App\Support\Epasien\DoctorScheduleDay;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DoctorScheduleLeaveRepository
{
    public function annotate(Collection $schedules): Collection
    {
        $keys = $this->leaveKeys($schedules);

        return $schedules->map(fn (array $schedule): array => [
            ...$schedule,
            'is_on_leave' => isset($keys[$this->key($schedule)]),
        ]);
    }

    public function available(Collection $schedules): Collection
    {
        $keys = $this->leaveKeys($schedules);

        return $schedules->reject(fn (object $schedule): bool => isset($keys[$this->key($schedule)]));
    }

    public function ensureAvailable(object $schedule): void
    {
        if (DB::table('doctor_schedule_leaves')->where($this->identity($schedule))->exists()) {
            throw ValidationException::withMessages([
                'jadwal' => 'Dokter sedang libur pada jadwal ini. Silakan pilih jadwal lain.',
            ]);
        }
    }

    public function setOnLeave(object $schedule, bool $isOnLeave): void
    {
        $identity = $this->identity($schedule);

        if (! $isOnLeave) {
            DB::table('doctor_schedule_leaves')->where($identity)->delete();

            return;
        }

        DB::table('doctor_schedule_leaves')->upsert([
            [...$identity, 'created_at' => now(), 'updated_at' => now()],
        ], ['doctor_code', 'day', 'start_time'], ['clinic_code', 'updated_at']);
    }

    public function move(array $original, array $changes): void
    {
        DB::table('doctor_schedule_leaves')
            ->where('doctor_code', $original['doctor_code'])
            ->where('day', DoctorScheduleDay::toEpasien($original['day']))
            ->where('start_time', substr($original['start_time'], 0, 5))
            ->update([
                'day' => DoctorScheduleDay::toEpasien($changes['day']),
                'start_time' => substr($changes['start_time'], 0, 5),
                'updated_at' => now(),
            ]);
    }

    public function remove(object $schedule): void
    {
        DB::table('doctor_schedule_leaves')->where($this->identity($schedule))->delete();
    }

    private function leaveKeys(Collection $schedules): array
    {
        if ($schedules->isEmpty()) {
            return [];
        }

        $doctorCodes = $schedules->map(fn (array|object $schedule): string => $this->identity($schedule)['doctor_code'])
            ->unique()->values()->all();

        return DB::table('doctor_schedule_leaves')->whereIn('doctor_code', $doctorCodes)
            ->get()->mapWithKeys(fn (object $leave): array => [$this->key($leave) => true])->all();
    }

    private function key(array|object $schedule): string
    {
        return implode('|', $this->identity($schedule));
    }

    private function identity(array|object $schedule): array
    {
        $schedule = (array) $schedule;

        return [
            'doctor_code' => trim((string) ($schedule['doctor_code'] ?? $schedule['kd_dokter'] ?? '')),
            'clinic_code' => trim((string) ($schedule['clinic_code'] ?? $schedule['kd_poli'] ?? '')),
            'day' => DoctorScheduleDay::toEpasien($schedule['day'] ?? $schedule['hari_kerja'] ?? ''),
            'start_time' => substr(trim((string) ($schedule['start_time'] ?? $schedule['jam_mulai'] ?? '')), 0, 5),
        ];
    }
}
