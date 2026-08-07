<?php

namespace App\Http\Controllers\Epasien\menu\PermintaanTindakan;

use App\Http\Controllers\Controller;
use App\Services\epasien\menu\PermintaanTindakan\PemeriksaanLaboratService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class PemeriksaanLaboratController extends Controller
{
    public function __construct(
        private readonly PemeriksaanLaboratService $pemeriksaanLaboratService
    ) {}

    public function index(Request $request)
    {
        $endDateRules = ['nullable', 'date_format:Y-m-d'];

        if ($request->filled('tanggal_mulai')) {
            $endDateRules[] = 'after_or_equal:tanggal_mulai';
        }

        $validated = $request->validate([
            'status_hasil' => [
                'nullable',
                Rule::in(['menunggu', 'proses', 'selesai']),
            ],
            'status_layanan' => ['nullable', Rule::in(['Ralan', 'Ranap'])],
            'tanggal_mulai' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_selesai' => $endDateRules,
            'q' => ['nullable', 'string', 'max:60'],
        ]);
        $resultStatus = $validated['status_hasil'] ?? null;
        $careType = $validated['status_layanan'] ?? null;
        $startDate = $validated['tanggal_mulai'] ?? null;
        $endDate = $validated['tanggal_selesai'] ?? null;
        $search = trim((string) ($validated['q'] ?? ''));
        $patient = null;
        $requests = $this->emptyRequests();
        $counts = $this->pemeriksaanLaboratService->emptyCounts();
        $connectionError = null;

        try {
            $patient = $this->pemeriksaanLaboratService
                ->patientForUser($request->user());
            $requests = $this->pemeriksaanLaboratService->requestsForUser(
                $request->user(),
                $resultStatus,
                $careType,
                $startDate,
                $endDate,
                $search
            );
            $counts = $this->pemeriksaanLaboratService
                ->countsForUser($request->user());
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat permintaan pemeriksaan laboratorium pasien.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            $connectionError = 'Data pemeriksaan laboratorium belum dapat dimuat. '
                .'Koneksi data Khanza tidak tersedia.';
        }

        return view('e-pasien.menu.PermintaanTindakan.pemeriksaanLaborat.pemeriksaanLaborat', [
            'patient' => $patient,
            'requests' => $requests,
            'counts' => $counts,
            'resultStatus' => $resultStatus,
            'careType' => $careType,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'search' => $search,
            'connectionError' => $connectionError,
        ]);
    }

    public function result(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'noorder' => ['required', 'string', 'max:20'],
        ]);

        try {
            $result = $this->pemeriksaanLaboratService->resultForUser(
                $request->user(),
                $validated['noorder']
            );
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat hasil pemeriksaan laboratorium pasien.', [
                'user_id' => $request->user()?->id,
                'noorder' => $validated['noorder'],
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Hasil laboratorium belum dapat dimuat. '
                    .'Koneksi data Khanza tidak tersedia.',
            ], 503);
        }

        if ($result === null) {
            return response()->json([
                'message' => 'Permintaan laboratorium tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Hasil laboratorium berhasil dimuat.',
            'data' => $result,
        ]);
    }

    public function resultPdf(Request $request): Response
    {
        $validated = $request->validate([
            'noorder' => ['required', 'string', 'max:20'],
        ]);

        try {
            $result = $this->pemeriksaanLaboratService->resultForUser(
                $request->user(),
                $validated['noorder']
            );
        } catch (Throwable $exception) {
            Log::warning('Gagal membuat PDF hasil laboratorium pasien.', [
                'user_id' => $request->user()?->id,
                'noorder' => $validated['noorder'],
                'message' => $exception->getMessage(),
            ]);

            abort(503, 'PDF hasil laboratorium belum dapat dibuat. Koneksi data Khanza tidak tersedia.');
        }

        abort_if($result === null, 404, 'Permintaan laboratorium tidak ditemukan.');
        abort_if(
            empty($result['kelompok_hasil']),
            404,
            'Rincian hasil laboratorium belum tersedia.'
        );

        try {
            $patient = $this->pemeriksaanLaboratService
                ->patientForUser($request->user());
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat identitas pasien untuk PDF hasil laboratorium.', [
                'user_id' => $request->user()?->id,
                'noorder' => $validated['noorder'],
                'message' => $exception->getMessage(),
            ]);

            abort(503, 'Identitas pasien untuk PDF hasil laboratorium belum dapat dimuat.');
        }

        $patient ??= (object) [
            'no_rkm_medis' => $request->user()?->username,
            'nm_pasien' => $request->user()?->name,
        ];
        $logoPath = public_path('landing/assets/imagesArsy/logoarsy.png');
        $logoDataUri = is_file($logoPath)
            ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath))
            : null;
        $printedAt = Carbon::now('Asia/Jakarta')
            ->locale('id')
            ->translatedFormat('d F Y, H.i').' WIB';
        $reference = preg_replace(
            '/[^A-Za-z0-9]+/',
            '-',
            (string) data_get($result, 'permintaan.noorder', $validated['noorder'])
        );
        $reference = trim((string) $reference, '-') ?: 'Laboratorium';
        $filename = 'Hasil-Laboratorium-RS-ARSY-'.$reference.'.pdf';

        $pdf = Pdf::loadView(
            'e-pasien.menu.PermintaanTindakan.pemeriksaanLaborat.resultPdf',
            [
                'result' => $result,
                'patient' => $patient,
                'logoDataUri' => $logoDataUri,
                'printedAt' => $printedAt,
            ]
        )
            ->setPaper('a4', 'portrait')
            ->setOption([
                'defaultFont' => 'DejaVu Sans',
                'dpi' => 150,
                'isFontSubsettingEnabled' => true,
                'isPhpEnabled' => true,
            ]);

        $response = $pdf->stream($filename, ['Attachment' => false]);
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    private function emptyRequests(): LengthAwarePaginator
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
