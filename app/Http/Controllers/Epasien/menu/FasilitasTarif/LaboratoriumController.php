<?php

namespace App\Http\Controllers\Epasien\menu\FasilitasTarif;

use App\Http\Controllers\Controller;
use App\Services\epasien\menu\FasilitasTarif\LaboratoriumService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class LaboratoriumController extends Controller
{
    public function __construct(
        private readonly LaboratoriumService $laboratoriumService
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'kelompok' => ['nullable', 'string', 'max:20'],
            'q' => ['nullable', 'string', 'max:80'],
        ]);
        $group = trim((string) ($validated['kelompok'] ?? ''));
        $search = trim((string) ($validated['q'] ?? ''));
        $items = $this->emptyItems();
        $summary = $this->laboratoriumService->emptySummary();
        $groups = new Collection;
        $connectionError = null;

        try {
            $items = $this->laboratoriumService->items($group, $search);
            $summary = $this->laboratoriumService->summary();
            $groups = $this->laboratoriumService->groups();
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat tarif laboratorium.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            $connectionError = 'Informasi tarif laboratorium belum dapat dimuat. '
                .'Koneksi data rumah sakit sedang tidak tersedia.';
        }

        return view('e-pasien.menu.FasilitasTarif.laboratorium.index', [
            'items' => $items,
            'summary' => $summary,
            'groups' => $groups,
            'group' => $group,
            'search' => $search,
            'connectionError' => $connectionError,
        ]);
    }

    private function emptyItems(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            [],
            0,
            16,
            LengthAwarePaginator::resolveCurrentPage(),
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }
}
