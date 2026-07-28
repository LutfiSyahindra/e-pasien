<?php

namespace App\Services\epasien\menu;

use App\Exceptions\RegistrationLockException;
use App\Models\OnlineRegistrationAudit;
use App\Models\User;
use App\Repositories\epasien\menu\DaftarOnlineRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class DaftarOnlineService
{
    private const ANTROL_SERVICE_INTERVAL_MINUTES = 5;

    private const HOSPITAL_TIMEZONE = 'Asia/Jakarta';

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

    public function searchPatients(
        string $searchQuery,
        ?string $birthDate = null,
        int $limit = 10
    ): Collection {
        $searchQuery = trim($searchQuery);
        $birthDate = trim((string) $birthDate);

        if ($searchQuery === '') {
            return collect();
        }

        return $this->daftarOnlineRepository->searchPatients(
            $searchQuery,
            $birthDate !== '' ? $birthDate : null,
            max(1, min($limit, 25))
        );
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
                ->filter(
                    fn (object $schedule): bool => (int) ($schedule->kuota ?? 0) > 0
                )
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
                        'kd_poli_bpjs' => trim((string) ($schedule->kd_poli_bpjs ?? '')),
                        'nm_poli_bpjs' => trim((string) ($schedule->nm_poli_bpjs ?? '')),
                        'hari_kerja' => trim((string) $schedule->hari_kerja),
                        'jam_mulai' => $this->timeValue($schedule->jam_mulai),
                        'jam_selesai' => $this->timeValue($schedule->jam_selesai),
                        'kd_dokter_bpjs' => trim((string) ($schedule->kd_dokter_bpjs ?? '')),
                        'nm_dokter_bpjs' => trim((string) ($schedule->nm_dokter_bpjs ?? '')),
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

    public function cancelRegistration(
        User $user,
        string $treatmentNumber,
        ?string $requestedMedicalRecordNumber = null,
        bool $configuredRegistrationRole = false
    ): array {
        $treatmentNumber = trim($treatmentNumber);
        $requestedMedicalRecordNumber = trim((string) $requestedMedicalRecordNumber);
        $userMedicalRecordNumber = trim((string) $user->username);

        if ($treatmentNumber === '') {
            throw ValidationException::withMessages([
                'no_rawat' => 'Nomor rawat wajib diisi.',
            ]);
        }

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
                'no_rkm_medis' => 'Anda tidak memiliki akses untuk membatalkan pendaftaran pasien lain.',
            ]);
        }

        $medicalRecordNumber = $configuredRegistrationRole
            ? $requestedMedicalRecordNumber
            : $userMedicalRecordNumber;

        if ($medicalRecordNumber === '') {
            throw ValidationException::withMessages([
                'no_rkm_medis' => 'Nomor rekam medis pasien tidak tersedia.',
            ]);
        }

        $result = $this->daftarOnlineRepository->cancelPendingRegistration(
            $treatmentNumber,
            $medicalRecordNumber
        );

        if ($result === DaftarOnlineRepository::CANCELLATION_CHECKED_IN) {
            throw ValidationException::withMessages([
                'pendaftaran' => 'Pendaftaran tidak dapat dibatalkan karena pasien sudah check-in di poli.',
            ]);
        }

        if ($result === DaftarOnlineRepository::CANCELLATION_NOT_PENDING) {
            throw ValidationException::withMessages([
                'pendaftaran' => 'Pendaftaran hanya dapat dibatalkan selama status masih Belum.',
            ]);
        }

        if ($result !== DaftarOnlineRepository::CANCELLATION_CANCELLED) {
            throw ValidationException::withMessages([
                'pendaftaran' => 'Pendaftaran aktif tidak ditemukan atau bukan milik pasien yang dipilih.',
            ]);
        }

        return [
            'no_rawat' => $treatmentNumber,
            'status' => 'Batal',
        ];
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

    public function previewAntrolPayload(
        User $user,
        array $data,
        bool $configuredRegistrationRole = false
    ): array {
        $registrationDate = Carbon::createFromFormat(
            'Y-m-d',
            (string) $data['tgl_registrasi'],
            self::HOSPITAL_TIMEZONE
        )->toDateString();
        $doctorCode = trim((string) $data['kd_dokter']);
        $clinicCode = trim((string) $data['kd_poli']);
        $guarantorCode = strtoupper(trim((string) $data['kd_pj']));
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
                'no_rkm_medis' => 'Anda tidak memiliki akses untuk menampilkan payload pasien lain.',
            ]);
        }

        if ($guarantorCode !== 'BPJ') {
            throw ValidationException::withMessages([
                'kd_pj' => 'Preview payload Antrol hanya tersedia untuk penjamin BPJ.',
            ]);
        }

        $medicalRecordNumber = $configuredRegistrationRole
            ? $requestedMedicalRecordNumber
            : $userMedicalRecordNumber;
        $patient = $this->patientForMedicalRecord($medicalRecordNumber);

        if (! $patient) {
            throw ValidationException::withMessages([
                'pasien' => 'Data pasien tidak ditemukan.',
            ]);
        }

        $requestedCardNumber = trim((string) ($data['no_peserta'] ?? ''));
        $documentType = trim((string) $data['bpjs_document_type']);
        $documentSource = trim((string) $data['bpjs_document_source']);
        $documentDate = trim((string) ($data['bpjs_document_date'] ?? ''));
        $documentCardNumber = trim((string) ($data['bpjs_document_card_number'] ?? ''));
        $documentNationalIdentityNumber = trim((string) ($data['bpjs_document_nik'] ?? ''));
        $documentPhoneNumber = trim((string) ($data['bpjs_document_phone'] ?? ''));
        $documentMedicalRecordNumber = trim((string) (
            $data['bpjs_document_medical_record']
            ?? ''
        ));
        $patientNationalIdentityNumber = trim((string) ($patient->no_ktp ?? ''));
        $patientPhoneNumber = trim((string) ($patient->no_tlp ?? ''));
        $cardNumber = $documentCardNumber !== ''
            ? $documentCardNumber
            : $requestedCardNumber;
        $nationalIdentityNumber = preg_match('/^\d{16}$/', $documentNationalIdentityNumber) === 1
            ? $documentNationalIdentityNumber
            : $patientNationalIdentityNumber;
        $phoneNumber = preg_match('/^\d{8,15}$/', $documentPhoneNumber) === 1
            ? $documentPhoneNumber
            : $patientPhoneNumber;
        $payloadMedicalRecordNumber = $documentMedicalRecordNumber !== ''
            ? $documentMedicalRecordNumber
            : trim((string) $patient->no_rkm_medis);

        if (
            $documentCardNumber !== ''
            && $documentCardNumber !== $requestedCardNumber
        ) {
            throw ValidationException::withMessages([
                'no_peserta' => 'No. kartu pada dokumen BPJS berbeda dengan no. kartu yang dicari.',
            ]);
        }

        if (
            $documentMedicalRecordNumber !== ''
            && $documentMedicalRecordNumber !== trim((string) $patient->no_rkm_medis)
        ) {
            throw ValidationException::withMessages([
                'pasien' => 'Nomor rekam medis pada dokumen BPJS berbeda dengan pasien yang dipilih.',
            ]);
        }

        if (
            $documentType === 'surat_kontrol'
            && $documentDate !== ''
            && $documentDate !== $registrationDate
        ) {
            throw ValidationException::withMessages([
                'tgl_registrasi' => 'Tanggal kunjungan harus sama dengan tanggal rencana pada surat kontrol yang dipilih.',
            ]);
        }

        if (preg_match('/^\d+$/', $cardNumber) !== 1) {
            throw ValidationException::withMessages([
                'no_peserta' => 'No. kartu BPJS hanya boleh berisi angka.',
            ]);
        }

        if (preg_match('/^\d{16}$/', $nationalIdentityNumber) !== 1) {
            throw ValidationException::withMessages([
                'pasien' => 'NIK pasien harus berisi 16 angka sebelum payload Antrol dapat dibuat.',
            ]);
        }

        if (preg_match('/^\d{8,15}$/', $phoneNumber) !== 1) {
            throw ValidationException::withMessages([
                'pasien' => 'No. HP pasien harus berisi 8 sampai 15 angka sebelum payload Antrol dapat dibuat.',
            ]);
        }

        $schedule = $this->scheduleFor(
            $registrationDate,
            $doctorCode,
            $clinicCode
        );

        if (! $schedule) {
            throw ValidationException::withMessages([
                'jadwal' => 'Jadwal dokter dan poli tidak tersedia pada tanggal yang dipilih.',
            ]);
        }

        $mappedBpjsDoctorCode = trim((string) ($schedule->kd_dokter_bpjs ?? ''));
        $mappedBpjsClinicCode = trim((string) ($schedule->kd_poli_bpjs ?? ''));
        $documentDoctorCode = trim((string) ($data['bpjs_document_doctor_code'] ?? ''));
        $documentDoctorName = trim((string) ($data['bpjs_document_doctor_name'] ?? ''));
        $documentClinicCode = trim((string) ($data['bpjs_document_clinic_code'] ?? ''));
        $documentClinicName = trim((string) ($data['bpjs_document_clinic_name'] ?? ''));
        $bpjsDoctorCode = $documentDoctorCode !== ''
            ? $documentDoctorCode
            : $mappedBpjsDoctorCode;
        $bpjsClinicCode = $documentClinicCode !== ''
            ? $documentClinicCode
            : $mappedBpjsClinicCode;

        if (preg_match('/^\d+$/', $bpjsDoctorCode) !== 1) {
            throw ValidationException::withMessages([
                'jadwal' => 'Mapping kode dokter BPJS belum tersedia atau belum valid.',
            ]);
        }

        if ($bpjsClinicCode === '') {
            throw ValidationException::withMessages([
                'jadwal' => 'Mapping kode subspesialis poli BPJS belum tersedia.',
            ]);
        }

        if (
            $documentDoctorCode !== ''
            && $mappedBpjsDoctorCode !== ''
            && $documentDoctorCode !== $mappedBpjsDoctorCode
        ) {
            throw ValidationException::withMessages([
                'jadwal' => 'Dokter pada jadwal tidak sesuai dengan dokter tujuan pada dokumen BPJS.',
            ]);
        }

        if (
            $documentClinicCode !== ''
            && $mappedBpjsClinicCode !== ''
            && $documentClinicCode !== $mappedBpjsClinicCode
        ) {
            throw ValidationException::withMessages([
                'jadwal' => 'Poli pada jadwal tidak sesuai dengan poli tujuan pada dokumen BPJS.',
            ]);
        }

        $visitType = $this->antrolVisitType(
            $documentType,
            $documentSource
        );
        $referenceNumber = trim((string) $data['bpjs_document_number']);
        $nextRegistrationNumber = $this->daftarOnlineRepository
            ->previewNextRegistrationNumber($registrationDate, $doctorCode, $clinicCode);
        $queueNumber = max(1, (int) $nextRegistrationNumber);
        $registeredCount = $this->daftarOnlineRepository
            ->countActiveRegistrations($registrationDate, $doctorCode, $clinicCode);
        $quota = max(0, (int) ($schedule->kuota ?? 0));
        $remainingQuota = max(0, $quota - $registeredCount - 1);
        $nextTreatmentNumber = $this->daftarOnlineRepository
            ->previewNextTreatmentNumber($registrationDate);
        $bookingCode = str_replace('/', '', $nextTreatmentNumber);
        $practiceStart = $this->timeValue($schedule->jam_mulai ?? null);
        $practiceEnd = $this->timeValue($schedule->jam_selesai ?? null);

        if ($practiceStart === '-' || $practiceEnd === '-') {
            throw ValidationException::withMessages([
                'jadwal' => 'Jam praktek dokter belum lengkap untuk membuat payload Antrol.',
            ]);
        }

        $estimatedServiceTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $registrationDate.' '.$practiceStart,
            self::HOSPITAL_TIMEZONE
        )
            ->addMinutes($queueNumber * self::ANTROL_SERVICE_INTERVAL_MINUTES)
            ->getTimestampMs();
        $patientRegistrationDate = trim((string) ($patient->tgl_daftar ?? ''));
        $isNewPatient = $patientRegistrationDate !== ''
            && ! str_starts_with($patientRegistrationDate, '0000-00-00')
            && Carbon::parse($patientRegistrationDate, self::HOSPITAL_TIMEZONE)
                ->isSameDay(Carbon::parse($registrationDate, self::HOSPITAL_TIMEZONE));

        return [
            'endpoint' => 'antrean/add',
            'method' => 'POST',
            'preview_only' => true,
            'document' => [
                'type' => $documentType,
                'source' => $documentSource,
                'number' => $referenceNumber,
                'date' => $documentDate,
                'clinic_code' => $documentClinicCode,
                'clinic_name' => $documentClinicName,
                'doctor_code' => $documentDoctorCode,
                'doctor_name' => $documentDoctorName,
            ],
            'field_sources' => [
                'nomorkartu' => $documentCardNumber !== '' ? 'Dokumen BPJS' : 'Form pencarian',
                'nik' => $nationalIdentityNumber === $documentNationalIdentityNumber
                    ? 'Dokumen BPJS'
                    : 'Data pasien Khanza',
                'nohp' => $phoneNumber === $documentPhoneNumber
                    ? 'Dokumen BPJS'
                    : 'Data pasien Khanza',
                'kodepoli' => $documentClinicCode !== ''
                    ? 'Dokumen BPJS'
                    : 'Mapping poli Khanza',
                'namapoli' => $documentClinicName !== ''
                    ? 'Dokumen BPJS'
                    : 'Mapping poli Khanza',
                'kodedokter' => $documentDoctorCode !== ''
                    ? 'Dokumen BPJS'
                    : 'Mapping dokter Khanza',
                'namadokter' => $documentDoctorName !== ''
                    ? 'Dokumen BPJS'
                    : 'Mapping dokter Khanza',
                'tanggalperiksa' => $documentType === 'surat_kontrol' && $documentDate !== ''
                    ? 'Jadwal (sesuai Surat Kontrol)'
                    : 'Jadwal pendaftaran',
                'nomorreferensi' => 'Dokumen BPJS',
            ],
            'payload' => [
                'kodebooking' => $bookingCode,
                'jenispasien' => 'JKN',
                'nomorkartu' => $cardNumber,
                'nik' => $nationalIdentityNumber,
                'nohp' => $phoneNumber,
                'kodepoli' => $bpjsClinicCode,
                'namapoli' => $documentClinicName !== ''
                    ? $documentClinicName
                    : trim((string) (
                        $schedule->nm_poli_bpjs
                        ?? $schedule->nm_poli
                        ?? ''
                    )),
                'pasienbaru' => $isNewPatient ? 1 : 0,
                'norm' => $payloadMedicalRecordNumber,
                'tanggalperiksa' => $registrationDate,
                'kodedokter' => (int) $bpjsDoctorCode,
                'namadokter' => $documentDoctorName !== ''
                    ? $documentDoctorName
                    : trim((string) (
                        $schedule->nm_dokter_bpjs
                        ?? $schedule->nm_dokter
                        ?? ''
                    )),
                'jampraktek' => $practiceStart.'-'.$practiceEnd,
                'jeniskunjungan' => $visitType,
                'nomorreferensi' => $referenceNumber,
                'nomorantrean' => $bpjsClinicCode.'-'.$nextRegistrationNumber,
                'angkaantrean' => $queueNumber,
                'estimasidilayani' => $estimatedServiceTime,
                'sisakuotajkn' => $remainingQuota,
                'kuotajkn' => $quota,
                'sisakuotanonjkn' => $remainingQuota,
                'kuotanonjkn' => $quota,
                'keterangan' => 'Peserta harap 30 menit lebih awal guna pencatatan administrasi.',
            ],
        ];
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

        if (strtoupper($guarantorCode) === 'BPJ') {
            throw ValidationException::withMessages([
                'kd_pj' => 'Pendaftaran BPJ tidak disimpan pada tahap ini. Gunakan modal Proses Daftar MJKN untuk memilih dokumen BPJS dan meninjau payload Antrol.',
            ]);
        }

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

        $isBpjsGuarantor = strtoupper($guarantorCode) === 'BPJ';
        $patientCardNumber = $isBpjsGuarantor
            ? trim((string) ($data['no_peserta'] ?? ''))
            : null;

        if ($isBpjsGuarantor && $patientCardNumber === '') {
            throw ValidationException::withMessages([
                'no_peserta' => 'No. kartu wajib diisi untuk penjamin BPJ.',
            ]);
        }

        if ($patientCardNumber !== null && Str::length($patientCardNumber) > 25) {
            throw ValidationException::withMessages([
                'no_peserta' => 'No. kartu tidak boleh lebih dari 25 karakter.',
            ]);
        }

        $registration = [
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
        ];

        try {
            $row = $patientCardNumber !== null
                ? $this->daftarOnlineRepository->createRegistration($registration, $patientCardNumber)
                : $this->daftarOnlineRepository->createRegistration($registration);
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
                'no_peserta' => $patientCardNumber,
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
        $checkedIn = (bool) ($registration->sudah_checkin ?? false);

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
            'sudah_checkin' => $checkedIn,
            'can_cancel' => strtoupper($status) === 'BELUM' && ! $checkedIn,
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

    private function antrolVisitType(string $documentType, string $documentSource): int
    {
        if ($documentType === 'surat_kontrol' && $documentSource === 'surat_kontrol') {
            return 3;
        }

        if ($documentType === 'rujukan' && $documentSource === 'rujukan_pcare') {
            return 1;
        }

        if ($documentType === 'rujukan' && $documentSource === 'rujukan_internal') {
            return 2;
        }

        if (
            $documentType === 'rujukan'
            && in_array($documentSource, ['rujukan_rumah_sakit', 'rujukan_rs'], true)
        ) {
            return 4;
        }

        throw ValidationException::withMessages([
            'bpjs_document_type' => 'Jenis dan sumber dokumen BPJS tidak sesuai.',
        ]);
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
