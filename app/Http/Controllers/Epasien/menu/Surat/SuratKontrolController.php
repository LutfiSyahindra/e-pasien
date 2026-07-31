<?php

namespace App\Http\Controllers\Epasien\menu\Surat;

use App\Http\Controllers\Controller;
use App\Services\epasien\menu\Surat\SuratKontrolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class SuratKontrolController extends Controller
{
    public function __construct(
        private readonly SuratKontrolService $suratKontrolService
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => [
                'nullable',
                Rule::in(['Menunggu', 'Sudah Periksa', 'Batal Periksa']),
            ],
            'tab' => ['nullable', Rule::in(['umum', 'bpjs'])],
        ]);
        $status = $validated['status'] ?? null;
        $activeTab = $validated['tab'] ?? 'umum';
        $patient = null;
        $generalLetters = $this->emptyLetters();
        $generalCounts = $this->suratKontrolService->emptyCounts();
        $connectionError = null;

        try {
            $patient = $this->suratKontrolService->patientForUser($request->user());
            $generalLetters = $this->suratKontrolService
                ->generalLettersForUser($request->user(), $status);
            $generalCounts = $this->suratKontrolService
                ->generalCountsForUser($request->user());
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat surat kontrol umum pasien.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            $connectionError = 'Surat kontrol umum belum dapat dimuat. '
                .'Koneksi data Khanza tidak tersedia.';
        }

        return view('e-pasien.menu.Surat.suratKontrol.index', [
            'patient' => $patient,
            'generalLetters' => $generalLetters,
            'generalCounts' => $generalCounts,
            'status' => $status,
            'activeTab' => $activeTab,
            'connectionError' => $connectionError,
            'defaultBpjsPeriod' => now()->format('Y-m'),
        ]);
    }

    public function bpjs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['nullable', Rule::in(['automatic', 'period'])],
            'periode' => ['nullable', 'required_if:mode,period', 'date_format:Y-m'],
        ], [
            'mode.in' => 'Mode pencarian surat kontrol tidak valid.',
            'periode.required_if' => 'Periode surat kontrol wajib dipilih.',
            'periode.date_format' => 'Format periode surat kontrol tidak valid.',
        ]);
        $searchMode = $validated['mode']
            ?? ($request->filled('periode') ? 'period' : 'automatic');
        $period = $searchMode === 'automatic'
            ? now()->format('Y-m')
            : (string) $validated['periode'];

        try {
            $patient = $this->suratKontrolService->patientForUser($request->user());

            if ($patient === null) {
                return response()->json([
                    'status' => 'not_found',
                    'message' => 'Data pasien tidak ditemukan.',
                ], 404);
            }

            $data = $this->suratKontrolService->bpjsLettersForPatient(
                $patient,
                $period,
                $searchMode === 'automatic'
            );
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat surat kontrol BPJS pasien.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Surat kontrol BPJS belum dapat dimuat. Silakan coba kembali.',
            ], 503);
        }

        if (! $data['available']) {
            return response()->json([
                'status' => 'unavailable',
                'message' => $data['meta_data']['message'],
                'data' => $data,
            ], 422);
        }

        $metadataCode = (string) ($data['meta_data']['code'] ?? '500');
        $letters = is_array($data['surat_kontrol'] ?? null)
            ? $data['surat_kontrol']
            : [];

        if ($letters !== []) {
            return response()->json([
                'status' => 'success',
                'message' => count($letters).' surat kontrol BPJS ditemukan.',
                'data' => $data,
            ]);
        }

        if (in_array($metadataCode, ['200', '201', '204'], true)) {
            return response()->json([
                'status' => 'empty',
                'message' => $data['meta_data']['message']
                    ?: 'Surat kontrol BPJS tidak ditemukan pada periode ini.',
                'data' => $data,
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $data['meta_data']['message']
                ?: 'Surat kontrol BPJS belum dapat dimuat dari VClaim.',
            'data' => $data,
        ], $metadataCode === '504' ? 504 : 502);
    }

    private function emptyLetters(): LengthAwarePaginator
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
