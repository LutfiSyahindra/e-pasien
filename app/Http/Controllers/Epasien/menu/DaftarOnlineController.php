<?php

namespace App\Http\Controllers\Epasien\menu;

use App\Http\Controllers\Controller;
use App\Services\epasien\menu\DaftarOnlineService;
use App\Services\epasien\settings\RegistrationRoleConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class DaftarOnlineController extends Controller
{
    public function __construct(
        private readonly DaftarOnlineService $daftarOnlineService,
        private readonly RegistrationRoleConfigurationService $roleConfigurationService
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'no_rkm_medis' => ['nullable', 'string', 'max:20'],
            'patient_search' => ['nullable', 'string', 'max:100'],
            'patient_birth_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);

        $patient = null;
        $patientSearchResults = collect();
        $penjaminOptions = [];
        $pendingRegistration = null;
        $connectionError = null;
        $isRegistrationStaff = $this->roleConfigurationService->isConfigured($request->user());
        $selectedMedicalRecordNumber = trim((string) ($validated['no_rkm_medis'] ?? ''));
        $patientSearchQuery = trim((string) ($validated['patient_search'] ?? $selectedMedicalRecordNumber));
        $patientSearchBirthDate = trim((string) ($validated['patient_birth_date'] ?? ''));
        $patientSearchPerformed = $isRegistrationStaff && $patientSearchQuery !== '';

        try {
            if ($isRegistrationStaff) {
                if ($selectedMedicalRecordNumber !== '') {
                    $patient = $this->daftarOnlineService
                        ->patientForMedicalRecord($selectedMedicalRecordNumber);
                } elseif ($patientSearchQuery !== '') {
                    $patientSearchResults = $this->daftarOnlineService
                        ->searchPatients(
                            $patientSearchQuery,
                            $patientSearchBirthDate !== '' ? $patientSearchBirthDate : null
                        );
                    $exactMedicalRecordMatch = $patientSearchResults->first(
                        fn (object $result): bool => trim((string) $result->no_rkm_medis) === $patientSearchQuery
                    );

                    if ($exactMedicalRecordMatch || $patientSearchResults->count() === 1) {
                        $patient = $exactMedicalRecordMatch ?? $patientSearchResults->first();
                        $selectedMedicalRecordNumber = trim((string) $patient->no_rkm_medis);
                        $patientSearchResults = collect();
                    }
                }
            } else {
                $patient = $this->daftarOnlineService->patientForUser($request->user());
            }

            if ($patient) {
                $pendingRegistration = $this->daftarOnlineService
                    ->pendingRegistrationForMedicalRecord((string) $patient->no_rkm_medis);
            }

            if (! $pendingRegistration) {
                $penjaminOptions = $this->daftarOnlineService->penjaminOptions(true);
            }

        } catch (Throwable $exception) {
            Log::warning('Gagal memuat halaman pendaftaran online.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            $connectionError = 'Koneksi data Khanza belum tersedia. Periksa konfigurasi DB_KHANZA_HOST dan database Khanza.';
        }

        return view('e-pasien.menu.daftarOnline.daftarOnline', [
            'patient' => $patient,
            'penjaminOptions' => $penjaminOptions,
            'pendingRegistration' => $pendingRegistration,
            'connectionError' => $connectionError,
            'isRegistrationStaff' => $isRegistrationStaff,
            'selectedMedicalRecordNumber' => $selectedMedicalRecordNumber,
            'patientSearchQuery' => $patientSearchQuery,
            'patientSearchBirthDate' => $patientSearchBirthDate,
            'patientSearchPerformed' => $patientSearchPerformed,
            'patientSearchResults' => $patientSearchResults,
        ]);
    }

    public function history(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'kd_pj' => ['nullable', 'string', 'max:10'],
        ]);

        $patient = null;
        $registrations = $this->emptyRegistrationHistory();
        $penjaminOptions = [];
        $connectionError = null;
        $searchQuery = trim((string) ($validated['q'] ?? ''));
        $guarantorCode = trim((string) ($validated['kd_pj'] ?? ''));
        $viewAllPatients = $this->roleConfigurationService->isConfigured($request->user());

        try {
            if (! $viewAllPatients) {
                $patient = $this->daftarOnlineService->patientForUser($request->user());
            }

            $registrations = $this->daftarOnlineService->registrationHistory(
                user: $request->user(),
                searchQuery: $searchQuery,
                guarantorCode: $guarantorCode,
                viewAllPatients: $viewAllPatients,
            );
            $penjaminOptions = $this->daftarOnlineService->penjaminOptions(true);
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat riwayat pendaftaran online.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            $connectionError = 'Koneksi data Khanza belum tersedia. Periksa konfigurasi DB_KHANZA_HOST dan database Khanza.';
        }

        return view('e-pasien.menu.daftarOnline.history', [
            'patient' => $patient,
            'registrations' => $registrations,
            'connectionError' => $connectionError,
            'searchQuery' => $searchQuery,
            'guarantorCode' => $guarantorCode,
            'penjaminOptions' => $penjaminOptions,
            'viewAllPatients' => $viewAllPatients,
        ]);
    }

    public function schedules(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tgl_registrasi' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        try {
            return response()->json([
                'status' => 'success',
                'message' => 'Jadwal tersedia berhasil dimuat.',
                'data' => $this->daftarOnlineService->availableSchedules($validated['tgl_registrasi']),
            ]);
        } catch (Throwable $exception) {
            Log::error('Gagal memuat jadwal pendaftaran online.', [
                'tgl_registrasi' => $validated['tgl_registrasi'],
                'message' => $exception->getMessage(),
            ]);

            return $this->khanzaErrorResponse();
        }
    }

    public function previewAntrol(Request $request): JsonResponse
    {
        if (! $this->roleConfigurationService->isConfigured($request->user())) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk menampilkan payload Antrol BPJS.',
            ], 403);
        }

        $validated = $request->validate([
            'no_rkm_medis' => ['required', 'string', 'max:20'],
            'tgl_registrasi' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'kd_dokter' => ['required', 'string', 'max:20'],
            'kd_poli' => ['required', 'string', 'max:15'],
            'kd_pj' => ['required', Rule::in(['BPJ'])],
            'no_peserta' => ['required', 'string', 'max:25', 'regex:/^\d+$/'],
            'bpjs_document_type' => [
                'required',
                Rule::in(['surat_kontrol', 'rujukan']),
            ],
            'bpjs_document_source' => [
                'required',
                Rule::in([
                    'surat_kontrol',
                    'rujukan_pcare',
                    'rujukan_internal',
                    'rujukan_rumah_sakit',
                    'rujukan_rs',
                ]),
            ],
            'bpjs_document_number' => [
                'required',
                'string',
                'max:40',
                'regex:/^[A-Za-z0-9-]+$/',
            ],
            'bpjs_document_date' => ['nullable', 'date_format:Y-m-d'],
            'bpjs_document_card_number' => ['nullable', 'string', 'max:25', 'regex:/^\d+$/'],
            'bpjs_document_nik' => ['nullable', 'string', 'max:30', 'regex:/^\d+$/'],
            'bpjs_document_phone' => ['nullable', 'string', 'max:20', 'regex:/^\d+$/'],
            'bpjs_document_medical_record' => ['nullable', 'string', 'max:20'],
            'bpjs_document_clinic_code' => ['nullable', 'string', 'max:15'],
            'bpjs_document_clinic_name' => ['nullable', 'string', 'max:100'],
            'bpjs_document_doctor_code' => ['nullable', 'string', 'max:20', 'regex:/^\d+$/'],
            'bpjs_document_doctor_name' => ['nullable', 'string', 'max:100'],
        ], [
            'kd_pj.in' => 'Preview payload Antrol hanya tersedia untuk penjamin BPJ.',
            'no_peserta.required' => 'No. kartu BPJS wajib diisi.',
            'no_peserta.regex' => 'No. kartu BPJS hanya boleh berisi angka.',
            'bpjs_document_type.required' => 'Pilih dokumen BPJS terlebih dahulu.',
            'bpjs_document_source.required' => 'Sumber dokumen BPJS belum tersedia.',
            'bpjs_document_number.required' => 'Nomor referensi BPJS wajib dipilih.',
            'bpjs_document_number.regex' => 'Format nomor referensi BPJS tidak valid.',
        ]);

        try {
            $data = $this->daftarOnlineService->previewAntrolPayload(
                $request->user(),
                $validated,
                true
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Payload tambah antrean Antrol siap ditinjau. Data belum disimpan atau dikirim.',
                'data' => $data,
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Gagal membuat preview payload tambah antrean Antrol.', [
                'user_id' => $request->user()?->id,
                'payload' => Arr::except($validated, [
                    'no_peserta',
                    'bpjs_document_card_number',
                    'bpjs_document_nik',
                    'bpjs_document_phone',
                ]),
                'message' => $exception->getMessage(),
            ]);

            return $this->khanzaErrorResponse(
                'Payload Antrol belum dapat dibuat karena data Khanza belum siap.'
            );
        }
    }

    public function submitAntrol(Request $request): JsonResponse
    {
        if (! $this->roleConfigurationService->isConfigured($request->user())) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk memproses pendaftaran MJKN.',
            ], 403);
        }

        $validated = $request->validate([
            'no_rkm_medis' => ['required', 'string', 'max:20'],
            'tgl_registrasi' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'kd_dokter' => ['required', 'string', 'max:20'],
            'kd_poli' => ['required', 'string', 'max:15'],
            'kd_pj' => ['required', Rule::in(['BPJ'])],
            'no_peserta' => ['required', 'string', 'max:25', 'regex:/^\d+$/'],
            'bpjs_document_type' => [
                'required',
                Rule::in(['surat_kontrol', 'rujukan']),
            ],
            'bpjs_document_source' => [
                'required',
                Rule::in([
                    'surat_kontrol',
                    'rujukan_pcare',
                    'rujukan_internal',
                    'rujukan_rumah_sakit',
                    'rujukan_rs',
                ]),
            ],
            'bpjs_document_number' => [
                'required',
                'string',
                'max:40',
                'regex:/^[A-Za-z0-9-]+$/',
            ],
            'bpjs_document_date' => ['nullable', 'date_format:Y-m-d'],
            'bpjs_document_card_number' => ['nullable', 'string', 'max:25', 'regex:/^\d+$/'],
            'bpjs_document_nik' => ['nullable', 'string', 'max:30', 'regex:/^\d+$/'],
            'bpjs_document_phone' => ['nullable', 'string', 'max:20', 'regex:/^\d+$/'],
            'bpjs_document_medical_record' => ['nullable', 'string', 'max:20'],
            'bpjs_document_clinic_code' => ['nullable', 'string', 'max:15'],
            'bpjs_document_clinic_name' => ['nullable', 'string', 'max:100'],
            'bpjs_document_doctor_code' => ['nullable', 'string', 'max:20', 'regex:/^\d+$/'],
            'bpjs_document_doctor_name' => ['nullable', 'string', 'max:100'],
            'preview_hash' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/'],
        ], [
            'kd_pj.in' => 'Pendaftaran MJKN hanya tersedia untuk penjamin BPJ.',
            'no_peserta.required' => 'No. kartu BPJS wajib diisi.',
            'no_peserta.regex' => 'No. kartu BPJS hanya boleh berisi angka.',
            'bpjs_document_type.required' => 'Pilih dokumen BPJS terlebih dahulu.',
            'bpjs_document_source.required' => 'Sumber dokumen BPJS belum tersedia.',
            'bpjs_document_number.required' => 'Nomor referensi BPJS wajib dipilih.',
            'bpjs_document_number.regex' => 'Format nomor referensi BPJS tidak valid.',
            'preview_hash.required' => 'Data final wajib ditinjau sebelum dikirim ke BPJS.',
            'preview_hash.size' => 'Konfirmasi data final tidak valid. Tampilkan preview kembali.',
            'preview_hash.regex' => 'Konfirmasi data final tidak valid. Tampilkan preview kembali.',
        ]);
        $previewHash = (string) Arr::pull($validated, 'preview_hash');

        try {
            $data = $this->daftarOnlineService->registerMjkn(
                $request->user(),
                $validated,
                $previewHash,
                true
            );
            $sent = (bool) ($data['antrol']['sent'] ?? false);

            return response()->json([
                'status' => $sent ? 'success' : 'partial',
                'message' => $sent
                    ? 'Pendaftaran MJKN tersimpan dan antrean berhasil ditambahkan ke BPJS.'
                    : 'Pendaftaran MJKN tersimpan. Pengiriman ke BPJS masih berstatus Belum: '
                        .($data['antrol']['message'] ?? 'layanan BPJS belum memberi respons sukses.'),
                'data' => $data,
            ], $sent ? 201 : 202);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Gagal memproses pendaftaran MJKN.', [
                'user_id' => $request->user()?->id,
                'payload' => Arr::except($validated, [
                    'no_peserta',
                    'bpjs_document_card_number',
                    'bpjs_document_nik',
                    'bpjs_document_phone',
                ]),
                'message' => $exception->getMessage(),
            ]);

            return $this->khanzaErrorResponse(
                'Pendaftaran MJKN belum dapat diproses karena data Khanza atau layanan BPJS belum siap.'
            );
        }
    }

    public function store(Request $request): JsonResponse
    {
        $isRegistrationStaff = $this->roleConfigurationService->isConfigured($request->user());

        $validated = $request->validate([
            'no_rkm_medis' => [
                Rule::requiredIf($isRegistrationStaff),
                'nullable',
                'string',
                'max:20',
            ],
            'tgl_registrasi' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'kd_dokter' => ['required', 'string', 'max:20'],
            'kd_poli' => ['required', 'string', 'max:15'],
            'kd_pj' => ['required', 'string', 'max:10'],
            'no_peserta' => [
                Rule::requiredIf(
                    fn (): bool => strtoupper(trim((string) $request->input('kd_pj'))) === 'BPJ'
                ),
                'nullable',
                'string',
                'max:25',
            ],
        ], [
            'no_peserta.required' => 'No. kartu wajib diisi untuk penjamin BPJ.',
            'no_peserta.max' => 'No. kartu tidak boleh lebih dari 25 karakter.',
        ]);

        if (
            $isRegistrationStaff
            && strtoupper(trim((string) $validated['kd_pj'])) === 'BPJ'
        ) {
            throw ValidationException::withMessages([
                'kd_pj' => 'Pendaftaran BPJ tidak disimpan pada tahap ini. Gunakan modal Proses Daftar MJKN untuk memilih dokumen BPJS dan meninjau payload Antrol.',
            ]);
        }

        try {
            $data = $this->daftarOnlineService->register(
                $request->user(),
                $validated,
                $isRegistrationStaff
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Pendaftaran online berhasil tersimpan ke reg_periksa.',
                'data' => $data,
            ], 201);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Gagal menyimpan pendaftaran online.', [
                'user_id' => $request->user()?->id,
                'payload' => Arr::except($validated, ['no_peserta']),
                'message' => $exception->getMessage(),
            ]);

            return $this->khanzaErrorResponse('Pendaftaran belum dapat disimpan karena koneksi atau struktur data Khanza belum siap.');
        }
    }

    public function cancel(Request $request): JsonResponse
    {
        $isRegistrationStaff = $this->roleConfigurationService->isConfigured($request->user());

        $validated = $request->validate([
            'no_rawat' => ['required', 'string', 'max:30'],
            'no_rkm_medis' => [
                Rule::requiredIf($isRegistrationStaff),
                'nullable',
                'string',
                'max:20',
            ],
            'keterangan' => ['nullable', 'string', 'min:5', 'max:255'],
        ]);

        try {
            $data = $this->daftarOnlineService->cancelRegistration(
                $request->user(),
                $validated['no_rawat'],
                $validated['no_rkm_medis'] ?? null,
                $isRegistrationStaff,
                $validated['keterangan'] ?? ''
            );
            $antrolCancelled = (bool) ($data['antrol']['cancelled'] ?? false);

            return response()->json([
                'status' => 'success',
                'message' => $antrolCancelled
                    ? 'Pendaftaran dan antrean JKN berhasil dibatalkan.'
                    : 'Pendaftaran berhasil dibatalkan.',
                'data' => $data,
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Gagal membatalkan pendaftaran online.', [
                'user_id' => $request->user()?->id,
                'no_rawat' => $validated['no_rawat'],
                'message' => $exception->getMessage(),
            ]);

            return $this->khanzaErrorResponse(
                'Pendaftaran belum dapat dibatalkan karena koneksi atau struktur data Khanza belum siap.'
            );
        }
    }

    private function khanzaErrorResponse(string $message = 'Koneksi data Khanza belum tersedia. Silakan coba lagi setelah konfigurasi database diperiksa.'): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], 500);
    }

    private function emptyRegistrationHistory(): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, 8, LengthAwarePaginator::resolveCurrentPage(), [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);
    }
}
