<?php

namespace App\Http\Controllers\Epasien\menu;

use App\Http\Controllers\Controller;
use App\Services\epasien\menu\DaftarOnlineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class DaftarOnlineController extends Controller
{
    public function __construct(private readonly DaftarOnlineService $daftarOnlineService) {}

    public function index(Request $request)
    {
        $patient = null;
        $penjaminOptions = [];
        $pendingRegistration = null;
        $connectionError = null;

        try {
            $patient = $this->daftarOnlineService->patientForUser($request->user());
            $pendingRegistration = $this->daftarOnlineService->pendingRegistration($request->user());

            if (! $pendingRegistration) {
                $penjaminOptions = $this->daftarOnlineService->penjaminOptions();
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
        ]);
    }

    public function history(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
        ]);

        $patient = null;
        $registrations = $this->emptyRegistrationHistory();
        $connectionError = null;
        $searchQuery = trim((string) ($validated['q'] ?? ''));

        try {
            $patient = $this->daftarOnlineService->patientForUser($request->user());
            $registrations = $this->daftarOnlineService->registrationHistory($request->user(), $searchQuery);
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
        $validated = $request->validate([
            'tgl_registrasi' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'kd_dokter' => ['required', 'string', 'max:20'],
            'kd_poli' => ['required', 'string', 'max:15'],
            'kd_pj' => ['required', 'string', 'max:10'],
        ]);

        try {
            $data = $this->daftarOnlineService->register($request->user(), $validated);

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
                'payload' => $validated,
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
