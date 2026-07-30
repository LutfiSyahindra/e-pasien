<?php

namespace App\Http\Controllers\Epasien\menu;

use App\Http\Controllers\Controller;
use App\Services\epasien\menu\OperasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class OperasiController extends Controller
{
    public function __construct(
        private readonly OperasiService $operasiService
    ) {}

    public function index(Request $request)
    {
        $endDateRules = ['nullable', 'date_format:Y-m-d'];

        if ($request->filled('tanggal_mulai')) {
            $endDateRules[] = 'after_or_equal:tanggal_mulai';
        }

        $validated = $request->validate([
            'status' => [
                'nullable',
                Rule::in([
                    'terjadwal',
                    'proses',
                    'menunggu_laporan',
                    'selesai',
                ]),
            ],
            'status_layanan' => ['nullable', Rule::in(['Ralan', 'Ranap'])],
            'tanggal_mulai' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_selesai' => $endDateRules,
            'q' => ['nullable', 'string', 'max:60'],
        ]);
        $workflowStatus = $validated['status'] ?? null;
        $careType = $validated['status_layanan'] ?? null;
        $startDate = $validated['tanggal_mulai'] ?? null;
        $endDate = $validated['tanggal_selesai'] ?? null;
        $search = trim((string) ($validated['q'] ?? ''));
        $patient = null;
        $operations = $this->emptyOperations();
        $counts = $this->operasiService->emptyCounts();
        $connectionError = null;

        try {
            $patient = $this->operasiService
                ->patientForUser($request->user());
            $operations = $this->operasiService->operationsForUser(
                $request->user(),
                $workflowStatus,
                $careType,
                $startDate,
                $endDate,
                $search
            );
            $counts = $this->operasiService
                ->countsForUser($request->user());
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat data operasi pasien.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            $connectionError = 'Data operasi belum dapat dimuat. '
                .'Koneksi data Khanza tidak tersedia.';
        }

        return view('e-pasien.menu.operasi.index', [
            'patient' => $patient,
            'operations' => $operations,
            'counts' => $counts,
            'workflowStatus' => $workflowStatus,
            'careType' => $careType,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'search' => $search,
            'connectionError' => $connectionError,
        ]);
    }

    public function detail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'no_rawat' => ['required', 'string', 'max:17'],
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'jam_mulai' => ['required', 'date_format:H:i:s'],
        ]);

        try {
            $detail = $this->operasiService->detailForUser(
                $request->user(),
                $validated['no_rawat'],
                $validated['tanggal'],
                $validated['jam_mulai']
            );
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat detail operasi pasien.', [
                'user_id' => $request->user()?->id,
                'no_rawat' => $validated['no_rawat'],
                'tanggal' => $validated['tanggal'],
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Detail operasi belum dapat dimuat. '
                    .'Koneksi data Khanza tidak tersedia.',
            ], 503);
        }

        if ($detail === null) {
            return response()->json([
                'message' => 'Data operasi tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail operasi berhasil dimuat.',
            'data' => $detail,
        ]);
    }

    private function emptyOperations(): LengthAwarePaginator
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
