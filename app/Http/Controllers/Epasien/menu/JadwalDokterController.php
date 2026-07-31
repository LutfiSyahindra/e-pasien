<?php

namespace App\Http\Controllers\Epasien\menu;

use App\Http\Controllers\Controller;
use App\Services\epasien\menu\JadwalDokterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class JadwalDokterController extends Controller
{
    public function __construct(
        private readonly JadwalDokterService $jadwalDokterService
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'hari' => [
                'nullable',
                'string',
                Rule::in([
                    'SEMUA',
                    'SENIN',
                    'SELASA',
                    'RABU',
                    'KAMIS',
                    'JUMAT',
                    'SABTU',
                    'AKHAD',
                ]),
            ],
            'poli' => ['nullable', 'string', 'max:5'],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $day = $validated['hari'] ?? null;
        $clinicCode = trim((string) ($validated['poli'] ?? ''));
        $page = $this->jadwalDokterService->emptyPage(
            $search,
            $day,
            $clinicCode
        );
        $connectionError = null;

        try {
            $page = $this->jadwalDokterService->page(
                $search,
                $day,
                $clinicCode
            );
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat jadwal dokter spesialis.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            $connectionError = 'Jadwal dokter belum dapat dimuat. '
                .'Koneksi data rumah sakit sedang tidak tersedia.';
        }

        return view('e-pasien.menu.jadwalDokter.index', [
            ...$page,
            'connectionError' => $connectionError,
        ]);
    }
}
