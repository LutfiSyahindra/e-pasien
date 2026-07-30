<?php

namespace App\Http\Controllers\Epasien\menu\FasilitasTarif;

use App\Http\Controllers\Controller;
use App\Services\epasien\menu\FasilitasTarif\PoliklinikService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Throwable;

class PoliklinikController extends Controller
{
    public function __construct(
        private readonly PoliklinikService $poliklinikService
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
        ]);
        $search = trim((string) ($validated['q'] ?? ''));
        $clinics = $this->emptyClinics();
        $summary = $this->poliklinikService->emptySummary();
        $connectionError = null;

        try {
            $clinics = $this->poliklinikService->clinics($search);
            $summary = $this->poliklinikService->summary();
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat informasi poliklinik.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            $connectionError = 'Informasi poliklinik belum dapat dimuat. '
                .'Koneksi data rumah sakit sedang tidak tersedia.';
        }

        return view('e-pasien.menu.FasilitasTarif.poliklinik.index', [
            'clinics' => $clinics,
            'summary' => $summary,
            'search' => $search,
            'connectionError' => $connectionError,
        ]);
    }

    private function emptyClinics(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            [],
            0,
            12,
            LengthAwarePaginator::resolveCurrentPage(),
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }
}
