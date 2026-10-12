<?php

namespace App\Repositories\epasien\settings;

use App\Support\Epasien\DoctorScheduleDay;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DoctorScheduleLeaveRepository
{
    public function annotate(Collection $schedules, bool $includePastDates = false): Collection
    {
        $today = now('Asia/Jakarta')->toDateString();
        $todayDay = DoctorScheduleDay::EPASIEN_DAYS[now('Asia/Jakarta')->isoWeekday() - 1];
        $leaves = $this->leavesFor($schedules)
            ->filter(fn (object $leave): bool => $includePastDates || $leave->leave_date === null || $leave->leave_date >= $today)
            ->groupBy(fn (object $leave): string => $this->key($leave));

        return $schedules->map(function (array $schedule) use ($leaves, $today, $todayDay): array {
            $doctorLeaves = $leaves->get($this->key($schedule), collect());
            $dates = $doctorLeaves->pluck('leave_date')->filter()->unique()->sort()->values()->all();
            $day = DoctorScheduleDay::toEpasien($schedule['day'] ?? '');

            return [
                ...$schedule,
                'is_on_leave' => $day === $todayDay && in_array($today, $dates, true),
                'leave_dates' => $dates,
                'has_undated_leave' => $doctorLeaves->contains(fn (object $leave): bool => $leave->leave_date === null),
            ];
        });
    }

    public function available(Collection $schedules, string $date): Collection
    {
        $keys = $this->leavesFor($schedules, $date)
            ->mapWithKeys(fn (object $leave): array => [$this->key($leave) => true])->all();

        return $schedules->reject(fn (object $schedule): bool => isset($keys[$this->key($schedule)]));
    }

    public function ensureAvailable(object $schedule, string $date): void
    {
        if (DB::table('doctor_schedule_leaves')->where([
            ...$this->identity($schedule),
            'leave_date' => $date,
        ])->exists()) {
            throw ValidationException::withMessages([
                'jadwal' => 'Dokter sedang libur pada tanggal ini. Silakan pilih tanggal atau dokter lain.',
            ]);
        }
    }

    public function setOnLeave(object $schedule, bool $isOnLeave, string $date, ?string $originalDate = null): void
    {
        $identity = [...$this->identity($schedule), 'leave_date' => $date];

        if (! $isOnLeave) {
            DB::table('doctor_schedule_leaves')->where($identity)->delete();

            return;
        }

        if ($originalDate !== null) {
            DB::transaction(function () use ($identity, $originalDate, $date): void {
                $leave = DB::table('doctor_schedule_leaves')
                    ->where([...$identity, 'leave_date' => $originalDate])->lockForUpdate()->first();
                if ($leave === null) {
                    throw ValidationException::withMessages([
                        'original_leave_date' => 'Tanggal libur tidak ditemukan atau baru saja diubah oleh petugas lain.',
                    ]);
                }
                if ($originalDate !== $date && DB::table('doctor_schedule_leaves')->where($identity)->exists()) {
                    throw ValidationException::withMessages([
                        'leave_date' => 'Tanggal libur tersebut sudah terdaftar untuk jadwal ini.',
                    ]);
                }
                DB::table('doctor_schedule_leaves')->where('id', $leave->id)
                    ->update(['leave_date' => $date, 'updated_at' => now()]);
            });

            return;
        }

        DB::transaction(function () use ($identity): void {
            DB::table('doctor_schedule_leaves')->upsert([
                [
                    ...$identity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ], ['doctor_code', 'clinic_code', 'day', 'start_time', 'leave_date'], ['updated_at']);

            // A chosen date replaces this schedule's old recurring leave setting.
            DB::table('doctor_schedule_leaves')->where(array_diff_key($identity, ['leave_date' => true]))
                ->whereNull('leave_date')->delete();
        });
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

    private function leavesFor(Collection $schedules, ?string $date = null): Collection
    {
        if ($schedules->isEmpty()) {
            return collect();
        }

        $doctorCodes = $schedules->map(fn (array|object $schedule): string => $this->identity($schedule)['doctor_code'])
            ->unique()->values()->all();

        return DB::table('doctor_schedule_leaves')->whereIn('doctor_code', $doctorCodes)
            ->when($date !== null, fn (Builder $query) => $query->where('leave_date', $date))->get();
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
