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
        ]);

        $patient = null;
        $penjaminOptions = [];
        $pendingRegistration = null;
        $connectionError = null;
        $isRegistrationStaff = $this->roleConfigurationService->isConfigured($request->user());
        $selectedMedicalRecordNumber = trim((string) ($validated['no_rkm_medis'] ?? ''));
        $patientSearchPerformed = $isRegistrationStaff && $selectedMedicalRecordNumber !== '';

        try {
            $patient = $isRegistrationStaff
                ? ($patientSearchPerformed
                    ? $this->daftarOnlineService->patientForMedicalRecord($selectedMedicalRecordNumber)
                    : null)
                : $this->daftarOnlineService->patientForUser($request->user());

            if ($patient) {
                $pendingRegistration = $this->daftarOnlineService
                    ->pendingRegistrationForMedicalRecord((string) $patient->no_rkm_medis);
            }

            if (! $pendingRegistration) {
                $penjaminOptions = $this->daftarOnlineService->penjaminOptions($isRegistrationStaff);
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
            'patientSearchPerformed' => $patientSearchPerformed,
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
