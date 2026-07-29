<?php

namespace App\Http\Controllers\Epasien\menu;

use App\Http\Controllers\Controller;
use App\Services\epasien\menu\RiwayatPemeriksaanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class RiwayatPemeriksaanController extends Controller
{
    public function __construct(
        private readonly RiwayatPemeriksaanService $riwayatPemeriksaanService
    ) {}

    public function index(Request $request)
    {
        $endDateRules = ['nullable', 'date_format:Y-m-d'];

        if ($request->filled('tanggal_mulai')) {
            $endDateRules[] = 'after_or_equal:tanggal_mulai';
        }

        $validated = $request->validate([
            'status_lanjut' => ['nullable', Rule::in(['Ralan', 'Ranap'])],
            'tanggal_mulai' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_selesai' => $endDateRules,
            'dokter' => ['nullable', 'string', 'max:20'],
        ]);
        $careType = $validated['status_lanjut'] ?? null;
        $startDate = $validated['tanggal_mulai'] ?? null;
        $endDate = $validated['tanggal_selesai'] ?? null;
        $doctorCode = $validated['dokter'] ?? null;
        $patient = null;
        $examinations = $this->emptyHistory();
        $counts = $this->riwayatPemeriksaanService->emptyCounts();
        $doctors = [];
        $connectionError = null;

        try {
            $patient = $this->riwayatPemeriksaanService->patientForUser($request->user());
            $examinations = $this->riwayatPemeriksaanService
                ->completedHistory(
                    $request->user(),
                    $careType,
                    $startDate,
                    $endDate,
                    $doctorCode
                );
            $counts = $this->riwayatPemeriksaanService
                ->completedCounts($request->user());
            $doctors = $this->riwayatPemeriksaanService
                ->completedDoctors($request->user());
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat riwayat pemeriksaan pasien.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            $connectionError = 'Riwayat pemeriksaan belum dapat dimuat. Koneksi data Khanza tidak tersedia.';
        }

        return view('e-pasien.menu.riwayatPemeriksaan.riwayatPemeriksaan', [
            'patient' => $patient,
            'examinations' => $examinations,
            'counts' => $counts,
            'careType' => $careType,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'doctorCode' => $doctorCode,
            'doctors' => $doctors,
            'connectionError' => $connectionError,
        ]);
    }

    public function resume(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'no_rawat' => ['required', 'string', 'max:30'],
            'status_lanjut' => ['required', Rule::in(['Ralan', 'Ranap'])],
        ]);

        try {
            $resume = $this->riwayatPemeriksaanService->resumeForUser(
                $request->user(),
                $validated['no_rawat'],
                $validated['status_lanjut']
            );
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat resume pemeriksaan pasien.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Resume belum dapat dimuat. Koneksi data Khanza tidak tersedia.',
            ], 503);
        }

        if ($resume === null) {
            return response()->json([
                'message' => 'Resume pemeriksaan belum tersedia.',
            ], 404);
        }

        return response()->json([
            'message' => 'Resume pemeriksaan berhasil dimuat.',
            'data' => $resume,
        ]);
    }

    public function payment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'no_rawat' => ['required', 'string', 'max:30'],
        ]);

        try {
            $payment = $this->riwayatPemeriksaanService->paymentForUser(
                $request->user(),
                $validated['no_rawat']
            );
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat nota pembayaran pasien.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Nota pembayaran belum dapat dimuat. Koneksi data Khanza tidak tersedia.',
            ], 503);
        }

        if ($payment === null) {
            return response()->json([
                'message' => 'Nota pembayaran tidak ditemukan untuk kunjungan ini.',
            ], 404);
        }

        return response()->json([
            'message' => 'Nota pembayaran berhasil dimuat.',
            'data' => $payment,
        ]);
    }

    private function emptyHistory(): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, 8, LengthAwarePaginator::resolveCurrentPage(), [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);
    }
}
