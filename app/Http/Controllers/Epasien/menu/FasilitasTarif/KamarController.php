<?php

namespace App\Http\Controllers\Epasien\menu\FasilitasTarif;

use App\Http\Controllers\Controller;
use App\Services\epasien\menu\FasilitasTarif\KamarService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class KamarController extends Controller
{
    public function __construct(
        private readonly KamarService $kamarService
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => [
                'nullable',
                Rule::in([
                    'tersedia',
                    'terisi',
                    'dibersihkan',
                    'dipesan',
                ]),
            ],
            'kelas' => ['nullable', 'string', 'max:30'],
            'q' => ['nullable', 'string', 'max:60'],
        ]);
        $status = $validated['status'] ?? null;
        $class = trim((string) ($validated['kelas'] ?? ''));
        $search = trim((string) ($validated['q'] ?? ''));
        $rooms = $this->emptyRooms();
        $counts = $this->kamarService->emptyCounts();
        $classes = collect();
        $connectionError = null;

        try {
            $rooms = $this->kamarService->rooms(
                $status,
                $class,
                $search
            );
            $counts = $this->kamarService->counts();
            $classes = $this->kamarService->classes();
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat informasi kamar.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            $connectionError = 'Informasi kamar belum dapat dimuat. '
                .'Koneksi data rumah sakit sedang tidak tersedia.';
        }

        return view('e-pasien.menu.FasilitasTarif.kamar.index', [
            'rooms' => $rooms,
            'counts' => $counts,
            'classes' => $classes,
            'status' => $status,
            'class' => $class,
            'search' => $search,
            'connectionError' => $connectionError,
        ]);
    }

    private function emptyRooms(): LengthAwarePaginator
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
