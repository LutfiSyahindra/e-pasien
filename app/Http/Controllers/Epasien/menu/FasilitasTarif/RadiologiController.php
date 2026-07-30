<?php

namespace App\Http\Controllers\Epasien\menu\FasilitasTarif;

use App\Http\Controllers\Controller;
use App\Services\epasien\menu\FasilitasTarif\RadiologiService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class RadiologiController extends Controller
{
    public function __construct(
        private readonly RadiologiService $radiologiService
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'kelas' => ['nullable', 'string', 'max:30'],
            'q' => ['nullable', 'string', 'max:80'],
        ]);
        $class = trim((string) ($validated['kelas'] ?? ''));
        $search = trim((string) ($validated['q'] ?? ''));
        $rates = $this->emptyRates();
        $summary = $this->radiologiService->emptySummary();
        $classes = new Collection;
        $connectionError = null;

        try {
            $rates = $this->radiologiService->rates($class, $search);
            $summary = $this->radiologiService->summary();
            $classes = $this->radiologiService->classes();
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat tarif radiologi.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            $connectionError = 'Informasi tarif radiologi belum dapat dimuat. '
                .'Koneksi data rumah sakit sedang tidak tersedia.';
        }

        return view('e-pasien.menu.FasilitasTarif.radiologi.index', [
            'rates' => $rates,
            'summary' => $summary,
            'classes' => $classes,
            'class' => $class,
            'search' => $search,
            'connectionError' => $connectionError,
        ]);
    }

    private function emptyRates(): LengthAwarePaginator
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
