<?php

namespace App\Http\Controllers\Epasien\menu;

use App\Http\Controllers\Controller;
use App\Services\epasien\menu\RiwayatMcuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Throwable;

class RiwayatMcuController extends Controller
{
    public function __construct(
        private readonly RiwayatMcuService $riwayatMcuService
    ) {}

    public function index(Request $request)
    {
        $endDateRules = ['nullable', 'date_format:Y-m-d'];

        if ($request->filled('tanggal_mulai')) {
            $endDateRules[] = 'after_or_equal:tanggal_mulai';
        }

        $validated = $request->validate([
            'tanggal_mulai' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_selesai' => $endDateRules,
            'dokter' => ['nullable', 'string', 'max:20'],
            'q' => ['nullable', 'string', 'max:80'],
        ]);
        $startDate = $validated['tanggal_mulai'] ?? null;
        $endDate = $validated['tanggal_selesai'] ?? null;
        $doctorCode = $validated['dokter'] ?? null;
        $search = trim((string) ($validated['q'] ?? ''));
        $patient = null;
        $assessments = $this->emptyAssessments();
        $summary = $this->riwayatMcuService->emptySummary();
        $doctors = collect();
        $connectionError = null;

        try {
            $patient = $this->riwayatMcuService
                ->patientForUser($request->user());
            $assessments = $this->riwayatMcuService
                ->assessmentsForUser(
                    $request->user(),
                    $startDate,
                    $endDate,
                    $doctorCode,
                    $search
                );
            $summary = $this->riwayatMcuService
                ->summaryForUser($request->user());
            $doctors = $this->riwayatMcuService
                ->doctorsForUser($request->user());
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat riwayat MCU pasien.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            $connectionError = 'Riwayat MCU belum dapat dimuat. '
                .'Koneksi data Khanza tidak tersedia.';
        }

        return view('e-pasien.menu.riwayatMcu.index', [
            'patient' => $patient,
            'assessments' => $assessments,
            'summary' => $summary,
            'doctors' => $doctors,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'doctorCode' => $doctorCode,
            'search' => $search,
            'connectionError' => $connectionError,
        ]);
    }

    public function detail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'no_rawat' => ['required', 'string', 'max:17'],
        ]);

        try {
            $detail = $this->riwayatMcuService->detailForUser(
                $request->user(),
                $validated['no_rawat']
            );
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat detail MCU pasien.', [
                'user_id' => $request->user()?->id,
                'no_rawat' => $validated['no_rawat'],
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Detail MCU belum dapat dimuat. '
                    .'Koneksi data Khanza tidak tersedia.',
            ], 503);
        }

        if ($detail === null) {
            return response()->json([
                'message' => 'Penilaian MCU tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail MCU berhasil dimuat.',
            'data' => $detail,
        ]);
    }

    private function emptyAssessments(): LengthAwarePaginator
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
