<?php

namespace App\Http\Controllers\Epasien\menu;

use App\Http\Controllers\Controller;
use App\Services\epasien\menu\PemeriksaanLaboratService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class PemeriksaanLaboratController extends Controller
{
    public function __construct(
        private readonly PemeriksaanLaboratService $pemeriksaanLaboratService
    ) {}

    public function index(Request $request)
    {
        $endDateRules = ['nullable', 'date_format:Y-m-d'];

        if ($request->filled('tanggal_mulai')) {
            $endDateRules[] = 'after_or_equal:tanggal_mulai';
        }

        $validated = $request->validate([
            'status_hasil' => [
                'nullable',
                Rule::in(['menunggu', 'proses', 'selesai']),
            ],
            'status_layanan' => ['nullable', Rule::in(['Ralan', 'Ranap'])],
            'tanggal_mulai' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_selesai' => $endDateRules,
            'q' => ['nullable', 'string', 'max:60'],
        ]);
        $resultStatus = $validated['status_hasil'] ?? null;
        $careType = $validated['status_layanan'] ?? null;
        $startDate = $validated['tanggal_mulai'] ?? null;
        $endDate = $validated['tanggal_selesai'] ?? null;
        $search = trim((string) ($validated['q'] ?? ''));
        $patient = null;
        $requests = $this->emptyRequests();
        $counts = $this->pemeriksaanLaboratService->emptyCounts();
        $connectionError = null;

        try {
            $patient = $this->pemeriksaanLaboratService
                ->patientForUser($request->user());
            $requests = $this->pemeriksaanLaboratService->requestsForUser(
                $request->user(),
                $resultStatus,
                $careType,
                $startDate,
                $endDate,
                $search
            );
            $counts = $this->pemeriksaanLaboratService
                ->countsForUser($request->user());
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat permintaan pemeriksaan laboratorium pasien.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            $connectionError = 'Data pemeriksaan laboratorium belum dapat dimuat. '
                .'Koneksi data Khanza tidak tersedia.';
        }

        return view('e-pasien.menu.pemeriksaanLaborat.pemeriksaanLaborat', [
            'patient' => $patient,
            'requests' => $requests,
            'counts' => $counts,
            'resultStatus' => $resultStatus,
            'careType' => $careType,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'search' => $search,
            'connectionError' => $connectionError,
        ]);
    }

    public function result(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'noorder' => ['required', 'string', 'max:20'],
        ]);

        try {
            $result = $this->pemeriksaanLaboratService->resultForUser(
                $request->user(),
                $validated['noorder']
            );
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat hasil pemeriksaan laboratorium pasien.', [
                'user_id' => $request->user()?->id,
                'noorder' => $validated['noorder'],
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Hasil laboratorium belum dapat dimuat. '
                    .'Koneksi data Khanza tidak tersedia.',
            ], 503);
        }

        if ($result === null) {
            return response()->json([
                'message' => 'Permintaan laboratorium tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Hasil laboratorium berhasil dimuat.',
            'data' => $result,
        ]);
    }

    private function emptyRequests(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            [],
            0,
            8,
            LengthAwarePaginator::resolveCurrentPage(),
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }
}
