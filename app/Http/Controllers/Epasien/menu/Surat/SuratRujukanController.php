<?php

namespace App\Http\Controllers\Epasien\menu\Surat;

use App\Http\Controllers\Controller;
use App\Services\epasien\menu\Surat\SuratRujukanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class SuratRujukanController extends Controller
{
    public function __construct(
        private readonly SuratRujukanService $suratRujukanService
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'tab' => ['nullable', Rule::in(['masuk', 'keluar'])],
            'jenis' => ['nullable', Rule::in(['umum', 'bpjs'])],
            'dari' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'sampai' => [
                'nullable',
                'date_format:Y-m-d',
            ],
        ], [
            'tab.in' => 'Tab surat rujukan tidak valid.',
            'jenis.in' => 'Jenis rujukan keluar tidak valid.',
            'dari.date_format' => 'Format tanggal mulai tidak valid.',
            'sampai.date_format' => 'Format tanggal akhir tidak valid.',
        ]);

        if (
            isset($validated['dari'], $validated['sampai'])
            && $validated['dari'] > $validated['sampai']
        ) {
            throw ValidationException::withMessages([
                'dari' => 'Tanggal mulai harus sebelum atau sama dengan tanggal akhir.',
            ]);
        }

        $patient = null;
        $generalReferrals = $this->emptyReferrals();
        $connectionError = null;
        $startDate = $validated['dari'] ?? null;
        $endDate = $validated['sampai'] ?? null;

        try {
            $patient = $this->suratRujukanService->patientForUser(
                $request->user()
            );
            $generalReferrals = $this->suratRujukanService
                ->generalOutgoingForUser(
                    $request->user(),
                    $startDate,
                    $endDate
                );
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat surat rujukan umum pasien.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            $connectionError = 'Rujukan keluar Umum belum dapat dimuat. '
                .'Koneksi data Khanza tidak tersedia.';
        }

        return view('e-pasien.menu.Surat.suratRujukan.index', [
            'patient' => $patient,
            'generalReferrals' => $generalReferrals,
            'connectionError' => $connectionError,
            'activeTab' => $validated['tab'] ?? 'masuk',
            'activeOutgoingType' => $validated['jenis'] ?? 'umum',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'defaultBpjsStartDate' => now()->startOfMonth()->toDateString(),
            'defaultBpjsEndDate' => now()->toDateString(),
        ]);
    }

    public function incomingBpjs(Request $request): JsonResponse
    {
        try {
            $patient = $this->suratRujukanService->patientForUser(
                $request->user()
            );

            if ($patient === null) {
                return response()->json([
                    'status' => 'not_found',
                    'message' => 'Data pasien tidak ditemukan.',
                ], 404);
            }

            $data = $this->suratRujukanService
                ->incomingBpjsForPatient($patient);
        } catch (Throwable $exception) {
            Log::warning('Gagal mencari rujukan masuk BPJS pasien.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Rujukan BPJS belum dapat dimuat. Silakan coba kembali.',
            ], 503);
        }

        return $this->referralResponse(
            $data,
            'Rujukan BPJS tidak ditemukan.'
        );
    }

    public function outgoingBpjs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tanggal_mulai' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:tanggal_akhir',
            ],
            'tanggal_akhir' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:tanggal_mulai',
            ],
        ], [
            'tanggal_mulai.required' => 'Tanggal mulai wajib dipilih.',
            'tanggal_mulai.date_format' => 'Format tanggal mulai tidak valid.',
            'tanggal_mulai.before_or_equal' => 'Tanggal mulai harus sebelum tanggal akhir.',
            'tanggal_akhir.required' => 'Tanggal akhir wajib dipilih.',
            'tanggal_akhir.date_format' => 'Format tanggal akhir tidak valid.',
            'tanggal_akhir.after_or_equal' => 'Tanggal akhir harus setelah tanggal mulai.',
        ]);

        try {
            $patient = $this->suratRujukanService->patientForUser(
                $request->user()
            );

            if ($patient === null) {
                return response()->json([
                    'status' => 'not_found',
                    'message' => 'Data pasien tidak ditemukan.',
                ], 404);
            }

            $data = $this->suratRujukanService->bpjsOutgoingForPatient(
                $patient,
                $validated['tanggal_mulai'],
                $validated['tanggal_akhir']
            );
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat rujukan keluar BPJS pasien.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Rujukan keluar BPJS belum dapat dimuat. Silakan coba kembali.',
            ], 503);
        }

        return $this->referralResponse(
            $data,
            'Rujukan keluar BPJS tidak ditemukan pada rentang tanggal ini.'
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function referralResponse(
        array $data,
        string $emptyMessage
    ): JsonResponse {
        if (! ($data['available'] ?? false)) {
            return response()->json([
                'status' => 'unavailable',
                'message' => $data['meta_data']['message']
                    ?? 'Nomor kartu BPJS belum tersedia.',
                'data' => $data,
            ], 422);
        }

        $referrals = is_array($data['rujukan'] ?? null)
            ? $data['rujukan']
            : [];
        $metadataCode = (string) ($data['meta_data']['code'] ?? '500');
        $metadataMessage = trim(
            (string) ($data['meta_data']['message'] ?? '')
        );

        if ($referrals !== []) {
            return response()->json([
                'status' => 'success',
                'message' => count($referrals).' surat rujukan ditemukan.',
                'data' => $data,
            ]);
        }

        if (in_array($metadataCode, ['200', '201', '204'], true)) {
            return response()->json([
                'status' => 'empty',
                'message' => $metadataMessage !== ''
                    ? $metadataMessage
                    : $emptyMessage,
                'data' => $data,
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $metadataMessage !== ''
                ? $metadataMessage
                : 'Layanan rujukan BPJS belum dapat diakses.',
            'data' => $data,
        ], $metadataCode === '504' ? 504 : 502);
    }

    private function emptyReferrals(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            [],
            0,
            8,
            LengthAwarePaginator::resolveCurrentPage('umum_page'),
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'umum_page',
            ]
        );
    }
}
