<?php

namespace App\Services\epasien\menu;

use App\Exceptions\RegistrationLockException;
use App\Models\OnlineRegistrationAudit;
use App\Models\User;
use App\Repositories\epasien\menu\DaftarOnlineRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class DaftarOnlineService
{
    private const MONTHS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function __construct(
        private readonly DaftarOnlineRepository $daftarOnlineRepository
    ) {}

    public function patientForUser(User $user): ?object
    {
        return $this->patientForMedicalRecord((string) $user->username);
    }

    public function patientForMedicalRecord(string $medicalRecordNumber): ?object
    {
        $medicalRecordNumber = trim($medicalRecordNumber);

        if ($medicalRecordNumber === '') {
            return null;
        }

        return $this->daftarOnlineRepository->findPatient($medicalRecordNumber);
    }

    public function penjaminOptions(bool $includeBpjs = false): array
    {
        return $this->daftarOnlineRepository
            ->getPenjaminOptions($includeBpjs)
            ->map(fn (object $penjamin): array => [
                'kd_pj' => trim((string) $penjamin->kd_pj),
                'png_jawab' => trim((string) $penjamin->png_jawab),
            ])
            ->values()
            ->all();
    }

    public function availableSchedules(string $date): array
    {
        $registrationDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        $registrationDateString = $registrationDate->toDateString();
        $schedules = $this->daftarOnlineRepository->getSchedules(
            $this->workdayAliases($registrationDate)
        );

        return [
            'tanggal' => $registrationDateString,
            'tanggal_label' => $this->dateLabel($registrationDate),
            'hari' => $this->dayLabel($registrationDate),
            'schedules' => $schedules
                ->map(function (object $schedule) use ($registrationDateString): array {
                    $doctorCode = trim((string) $schedule->kd_dokter);
                    $clinicCode = trim((string) $schedule->kd_poli);

                    return [
                        'id' => md5(implode('|', [
                            $schedule->kd_poli,
                            $schedule->kd_dokter,
                            $schedule->jam_mulai,
                            $schedule->jam_selesai,
                        ])),
                        'kd_dokter' => $doctorCode,
                        'nm_dokter' => trim((string) $schedule->nm_dokter),
                        'kd_poli' => $clinicCode,
                        'nm_poli' => trim((string) $schedule->nm_poli),
                        'hari_kerja' => trim((string) $schedule->hari_kerja),
                        'jam_mulai' => $this->timeValue($schedule->jam_mulai),
                        'jam_selesai' => $this->timeValue($schedule->jam_selesai),
                        'kuota' => (int) ($schedule->kuota ?? 0),
                        'terdaftar' => $this->daftarOnlineRepository->countActiveRegistrations(
                            $registrationDateString,
                            $doctorCode,
                            $clinicCode
                        ),
                        'estimasi_no_reg' => $this->daftarOnlineRepository->previewNextRegistrationNumber(
                            $registrationDateString,
                            $doctorCode,
                            $clinicCode
                        ),
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    public function pendingRegistration(User $user): ?array
    {
        return $this->pendingRegistrationForMedicalRecord((string) $user->username);
    }

    public function pendingRegistrationForMedicalRecord(string $medicalRecordNumber): ?array
    {
        $medicalRecordNumber = trim($medicalRecordNumber);

        if ($medicalRecordNumber === '') {
            return null;
        }

        $registration = $this->daftarOnlineRepository
            ->findPendingRegistration($medicalRecordNumber);

        return $registration ? $this->formatRegistration($registration) : null;
    }

    public function registrationHistory(
        User $user,
        string $searchQuery = '',
        int $perPage = 8,
        string $guarantorCode = '',
        bool $viewAllPatients = false
    ): LengthAwarePaginator {
        $medicalRecordNumber = $viewAllPatients
            ? null
            : trim((string) $user->username);
        $perPage = max(4, min($perPage, 20));

        if (! $viewAllPatients && $medicalRecordNumber === '') {
            return $this->emptyPaginator($perPage);
        }

        $registrations = $this->daftarOnlineRepository->paginateRegistrationHistory(
            $medicalRecordNumber,
            trim($searchQuery),
            $perPage,
            trim($guarantorCode)
        );

        $audits = OnlineRegistrationAudit::query()
            ->whereIn(
                'no_rawat',
                $registrations->getCollection()->pluck('no_rawat')->filter()->all()
            )
            ->get()
            ->keyBy('no_rawat');

        $registrations->setCollection(
            $registrations->getCollection()->map(
                fn (object $registration): array => $this->formatRegistration(
                    $registration,
                    $audits->get(trim((string) $registration->no_rawat))
                )
            )
        );

        return $registrations;
    }

    public function register(User $user, array $data, bool $configuredRegistrationRole = false): array
    {
        $registrationDate = Carbon::createFromFormat('Y-m-d', $data['tgl_registrasi'])->toDateString();
        $doctorCode = trim((string) $data['kd_dokter']);
        $clinicCode = trim((string) $data['kd_poli']);
        $guarantorCode = trim((string) $data['kd_pj']);
        $requestedMedicalRecordNumber = trim((string) ($data['no_rkm_medis'] ?? ''));
        $userMedicalRecordNumber = trim((string) $user->username);

        if ($configuredRegistrationRole && $requestedMedicalRecordNumber === '') {
            throw ValidationException::withMessages([
                'no_rkm_medis' => 'Nomor rekam medis pasien wajib dipilih oleh petugas pendaftaran.',
            ]);
        }

        if (
            ! $configuredRegistrationRole
            && $requestedMedicalRecordNumber !== ''
            && $requestedMedicalRecordNumber !== $userMedicalRecordNumber
        ) {
            throw ValidationException::withMessages([
                'no_rkm_medis' => 'Anda tidak memiliki akses untuk mendaftarkan pasien lain.',
            ]);
        }

        $medicalRecordNumber = $configuredRegistrationRole
            ? $requestedMedicalRecordNumber
            : $userMedicalRecordNumber;

        $patient = $this->patientForMedicalRecord($medicalRecordNumber);

        if (! $patient) {
            throw ValidationException::withMessages([
                'pasien' => 'Data pasien tidak ditemukan. Pastikan username akun sesuai dengan no_rkm_medis.',
            ]);
        }

        $pendingRegistration = $this->pendingRegistrationForMedicalRecord($medicalRecordNumber);

        if ($pendingRegistration) {
            throw ValidationException::withMessages([
                'pendaftaran' => 'Masih ada pendaftaran berstatus Belum dengan nomor registrasi '.$pendingRegistration['no_reg'].'.',
            ]);
        }

        $birthDate = $this->validBirthDate($patient->tgl_lahir ?? null);

        if (! $birthDate) {
            throw ValidationException::withMessages([
                'pasien' => 'Tanggal lahir pasien belum valid, sehingga umur daftar tidak bisa dihitung.',
            ]);
        }

        $schedule = $this->scheduleFor($registrationDate, $doctorCode, $clinicCode);

        if (! $schedule) {
            throw ValidationException::withMessages([
                'jadwal' => 'Jadwal dokter dan poli tidak tersedia pada tanggal yang dipilih.',
            ]);
        }

        $penjamin = $this->daftarOnlineRepository->findEligiblePenjamin(
            $guarantorCode,
            $configuredRegistrationRole
        );

        if (! $penjamin) {
            throw ValidationException::withMessages([
                'kd_pj' => $configuredRegistrationRole
                    ? 'Penjamin tidak tersedia.'
                    : 'Penjamin tidak tersedia atau termasuk BPJS Kesehatan.',
            ]);
        }

        try {
            $row = $this->daftarOnlineRepository->createRegistration([
                'tgl_registrasi' => $registrationDate,
                'jam_reg' => now()->format('H:i:s'),
                'kd_dokter' => $doctorCode,
                'no_rkm_medis' => trim((string) $patient->no_rkm_medis),
                'kd_poli' => $clinicCode,
                'p_jawab' => $this->limitValue($this->firstFilled([
                    $patient->namakeluarga ?? null,
                    $patient->nm_pasien ?? null,
                ]), 100),
                'almt_pj' => $this->limitValue($patient->alamat ?? '-', 200),
                'hubunganpj' => $this->limitValue($patient->keluarga ?? '-', 20),
                'biaya_reg' => 0,
                'stts' => 'Belum',
                'stts_daftar' => 'Lama',
                'status_lanjut' => 'Ralan',
                'kd_pj' => $guarantorCode,
                'umurdaftar' => (int) $birthDate->diffInYears(Carbon::parse($registrationDate)),
                'sttsumur' => 'Th',
                'status_bayar' => 'Belum Bayar',
                'status_poli' => 'Lama',
            ]);
        } catch (RegistrationLockException) {
            throw ValidationException::withMessages([
                'pendaftaran' => 'Nomor registrasi sedang diproses. Silakan coba beberapa saat lagi.',
            ]);
        }

        if (! $row) {
            throw ValidationException::withMessages([
                'jadwal' => 'Anda sudah terdaftar pada dokter dan poli ini untuk tanggal tersebut.',
            ]);
        }

        if ($configuredRegistrationRole) {
            try {
                OnlineRegistrationAudit::query()->create([
                    'no_rawat' => $row['no_rawat'],
                    'no_reg' => $row['no_reg'],
                    'registration_date' => $row['tgl_registrasi'],
                    'registration_time' => $row['jam_reg'],
                    'patient_medical_record_number' => trim((string) $patient->no_rkm_medis),
                    'patient_name' => $this->limitValue($patient->nm_pasien, 100),
                    'doctor_code' => $row['kd_dokter'],
                    'doctor_name' => $this->limitValue($schedule->nm_dokter, 100),
                    'clinic_code' => $row['kd_poli'],
                    'clinic_name' => $this->limitValue($schedule->nm_poli, 100),
                    'guarantor_code' => $row['kd_pj'],
                    'guarantor_name' => $this->limitValue($penjamin->png_jawab, 100),
                    'registered_by_user_id' => $user->getKey(),
                    'registered_by_name' => $this->limitValue($user->name, 100),
                    'registered_by_username' => Str::limit(
                        trim((string) $user->username),
                        100,
                        ''
                    ) ?: null,
                    'registered_by_roles' => $user->getRoleNames()->values()->all(),
                ]);
            } catch (Throwable $exception) {
                Log::critical('Pendaftaran Khanza tersimpan tetapi audit E-Pasien gagal dibuat.', [
                    'no_rawat' => $row['no_rawat'],
                    'registered_by_user_id' => $user->getKey(),
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return [
            'registration' => [
                'no_reg' => $row['no_reg'],
                'no_rawat' => $row['no_rawat'],
                'tanggal' => $row['tgl_registrasi'],
                'tanggal_label' => $this->dateLabel(Carbon::parse($row['tgl_registrasi'])),
                'jam' => $this->timeValue($row['jam_reg']),
                'dokter' => trim((string) $schedule->nm_dokter),
                'kd_dokter' => $row['kd_dokter'],
                'poli' => trim((string) $schedule->nm_poli),
                'kd_poli' => $row['kd_poli'],
                'penjamin' => trim((string) $penjamin->png_jawab),
                'kd_pj' => $row['kd_pj'],
                'status' => $row['stts'],
                'status_bayar' => $row['status_bayar'],
                'umurdaftar' => $row['umurdaftar'],
                'sttsumur' => $row['sttsumur'],
            ],
        ];
    }

    private function formatRegistration(
        object $registration,
        ?OnlineRegistrationAudit $audit = null
    ): array {
        $registrationDate = Carbon::parse($registration->tgl_registrasi)->startOfDay();
        $status = trim((string) ($registration->stts ?: '-'));
        $age = trim((string) ($registration->umurdaftar ?? ''));
        $ageUnit = trim((string) ($registration->sttsumur ?? ''));
        $registrationFee = (float) ($registration->biaya_reg ?? 0);

        return [
            'id' => md5(trim((string) $registration->no_rawat).'|'.trim((string) $registration->no_reg)),
            'no_reg' => trim((string) $registration->no_reg),
            'no_rawat' => trim((string) $registration->no_rawat),
            'no_rkm_medis' => trim((string) ($registration->no_rkm_medis ?? '')),
            'nama_pasien' => trim((string) ($registration->nm_pasien ?? '-')),
            'telepon_pasien' => trim((string) ($registration->no_tlp ?? '-')),
            'tanggal' => $registrationDate->toDateString(),
            'tanggal_label' => $this->dateLabel($registrationDate),
            'tanggal_lengkap' => $this->dayLabel($registrationDate).', '.$this->dateLabel($registrationDate),
            'hari' => $this->dayLabel($registrationDate),
            'hari_short' => substr($this->dayLabel($registrationDate), 0, 3),
            'tanggal_angka' => $registrationDate->format('d'),
            'bulan_short' => substr(self::MONTHS[(int) $registrationDate->format('n')], 0, 3),
            'jam' => $this->timeValue($registration->jam_reg),
            'dokter' => trim((string) ($registration->nm_dokter ?: '-')),
            'kd_dokter' => trim((string) $registration->kd_dokter),
            'poli' => trim((string) ($registration->nm_poli ?: '-')),
            'kd_poli' => trim((string) $registration->kd_poli),
            'penjamin' => trim((string) ($registration->png_jawab ?: '-')),
            'kd_pj' => trim((string) $registration->kd_pj),
            'status' => $status,
            'status_tone' => $this->statusTone($status),
            'status_bayar' => trim((string) ($registration->status_bayar ?: '-')),
            'status_lanjut' => trim((string) ($registration->status_lanjut ?: '-')),
            'stts_daftar' => trim((string) ($registration->stts_daftar ?: '-')),
            'status_poli' => trim((string) ($registration->status_poli ?: '-')),
            'biaya_reg' => $registrationFee,
            'biaya_reg_label' => 'Rp '.number_format($registrationFee, 0, ',', '.'),
            'umurdaftar' => $age !== '' ? trim($age.' '.$ageUnit) : '-',
            'penanggung_jawab' => trim((string) ($registration->p_jawab ?: '-')),
            'alamat_penanggung_jawab' => trim((string) ($registration->almt_pj ?: '-')),
            'hubungan_penanggung_jawab' => trim((string) ($registration->hubunganpj ?: '-')),
            'didaftarkan_oleh' => trim((string) ($audit?->registered_by_name ?: '-')),
            'role_pendaftar' => collect($audit?->registered_by_roles ?? [])->implode(', ') ?: '-',
        ];
    }

    private function statusTone(string $status): string
    {
        return match (strtoupper($status)) {
            'BELUM' => 'warning',
            'SUDAH', 'BERKAS DITERIMA' => 'success',
            'BATAL' => 'danger',
            'DIRAWAT', 'DIRUJUK' => 'info',
            default => 'neutral',
        };
    }

    private function emptyPaginator(int $perPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, $perPage, LengthAwarePaginator::resolveCurrentPage(), [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);
    }

    private function scheduleFor(string $date, string $doctorCode, string $clinicCode): ?object
    {
        $registrationDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();

        return $this->daftarOnlineRepository->findSchedule(
            $doctorCode,
            $clinicCode,
            $this->workdayAliases($registrationDate)
        );
    }

    private function validBirthDate(mixed $value): ?Carbon
    {
        $date = trim((string) $value);

        if ($date === '' || str_starts_with($date, '0000-00-00')) {
            return null;
        }

        try {
            return Carbon::parse($date)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    private function timeValue(mixed $value): string
    {
        $time = trim((string) $value);

        if ($time === '') {
            return '-';
        }

        return substr($time, 0, 5);
    }

    private function limitValue(mixed $value, int $limit): string
    {
        $text = trim((string) $value);

        if ($text === '') {
            $text = '-';
        }

        return Str::limit($text, $limit, '');
    }

    private function firstFilled(array $values): string
    {
        foreach ($values as $value) {
            $text = trim((string) $value);

            if ($text !== '') {
                return $text;
            }
        }

        return '-';
    }

    private function workdayAliases(Carbon $date): array
    {
        return match ($date->dayOfWeek) {
            Carbon::SUNDAY => ['MINGGU', 'AHAD', 'AKHAD'],
            Carbon::MONDAY => ['SENIN'],
            Carbon::TUESDAY => ['SELASA'],
            Carbon::WEDNESDAY => ['RABU'],
            Carbon::THURSDAY => ['KAMIS'],
            Carbon::FRIDAY => ['JUMAT', 'JUM\'AT'],
            Carbon::SATURDAY => ['SABTU'],
            default => [],
        };
    }

    private function dayLabel(Carbon $date): string
    {
        return match ($date->dayOfWeek) {
            Carbon::SUNDAY => 'Minggu',
            Carbon::MONDAY => 'Senin',
            Carbon::TUESDAY => 'Selasa',
            Carbon::WEDNESDAY => 'Rabu',
            Carbon::THURSDAY => 'Kamis',
            Carbon::FRIDAY => 'Jumat',
            Carbon::SATURDAY => 'Sabtu',
            default => '-',
        };
    }

    private function dateLabel(Carbon $date): string
    {
        return $date->format('d').' '.self::MONTHS[(int) $date->format('n')].' '.$date->format('Y');
    }
}
