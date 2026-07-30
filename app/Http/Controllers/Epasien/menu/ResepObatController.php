<?php

namespace App\Http\Controllers\Epasien\menu;

use App\Http\Controllers\Controller;
use App\Services\epasien\menu\ResepObatService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class ResepObatController extends Controller
{
    public function __construct(
        private readonly ResepObatService $resepObatService
    ) {}

    public function index(Request $request)
    {
        $endDateRules = ['nullable', 'date_format:Y-m-d'];

        if ($request->filled('tanggal_mulai')) {
            $endDateRules[] = 'after_or_equal:tanggal_mulai';
        }

        $validated = $request->validate([
            'jenis_resep' => [
                'nullable',
                Rule::in(['dokter', 'pulang']),
            ],
            'status_layanan' => [
                'nullable',
                Rule::in(['ralan', 'ranap']),
            ],
            'tanggal_mulai' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_selesai' => $endDateRules,
            'q' => ['nullable', 'string', 'max:60'],
        ]);
        $prescriptionType = $validated['jenis_resep'] ?? null;
        $careType = $validated['status_layanan'] ?? null;
        $startDate = $validated['tanggal_mulai'] ?? null;
        $endDate = $validated['tanggal_selesai'] ?? null;
        $search = trim((string) ($validated['q'] ?? ''));
        $patient = null;
        $prescriptions = $this->emptyPrescriptions();
        $counts = $this->resepObatService->emptyCounts();
        $connectionError = null;

        try {
            $patient = $this->resepObatService
                ->patientForUser($request->user());
            $prescriptions = $this->resepObatService->prescriptionsForUser(
                $request->user(),
                $prescriptionType,
                $careType,
                $startDate,
                $endDate,
                $search
            );
            $counts = $this->resepObatService
                ->countsForUser($request->user());
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat resep obat pasien.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            $connectionError = 'Data resep obat belum dapat dimuat. '
                .'Koneksi data Khanza tidak tersedia.';
        }

        return view('e-pasien.menu.resepObat.index', [
            'patient' => $patient,
            'prescriptions' => $prescriptions,
            'counts' => $counts,
            'prescriptionType' => $prescriptionType,
            'careType' => $careType,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'search' => $search,
            'connectionError' => $connectionError,
        ]);
    }

    private function emptyPrescriptions(): LengthAwarePaginator
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
