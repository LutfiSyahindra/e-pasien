<?php

namespace App\Http\Controllers\Epasien\menu\PermintaanTindakan;

use App\Http\Controllers\Controller;
use App\Services\epasien\menu\PermintaanTindakan\PemeriksaanRadiologiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class PemeriksaanRadiologiController extends Controller
{
    public function __construct(
        private readonly PemeriksaanRadiologiService $pemeriksaanRadiologiService
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
            'status_layanan' => [
                'nullable',
                Rule::in(['ralan', 'ranap']),
            ],
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
        $counts = $this->pemeriksaanRadiologiService->emptyCounts();
        $connectionError = null;

        try {
            $patient = $this->pemeriksaanRadiologiService
                ->patientForUser($request->user());
            $requests = $this->pemeriksaanRadiologiService->requestsForUser(
                $request->user(),
                $resultStatus,
                $careType,
                $startDate,
                $endDate,
                $search
            );
            $counts = $this->pemeriksaanRadiologiService
                ->countsForUser($request->user());
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat pemeriksaan radiologi pasien.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            $connectionError = 'Data pemeriksaan radiologi belum dapat dimuat. '
                .'Koneksi data Khanza tidak tersedia.';
        }

        return view('e-pasien.menu.PermintaanTindakan.pemeriksaanRadiologi.index', [
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
            $result = $this->pemeriksaanRadiologiService->resultForUser(
                $request->user(),
                $validated['noorder']
            );
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat hasil radiologi pasien.', [
                'user_id' => $request->user()?->id,
                'noorder' => $validated['noorder'],
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Hasil radiologi belum dapat dimuat. '
                    .'Koneksi data Khanza tidak tersedia.',
            ], 503);
        }

        if ($result === null) {
            return response()->json([
                'message' => 'Permintaan radiologi tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Hasil radiologi berhasil dimuat.',
            'data' => $result,
        ]);
    }

    public function resultPdf(Request $request): Response
    {
        $validated = $request->validate([
            'noorder' => ['required', 'string', 'max:20'],
        ]);

        try {
            $result = $this->pemeriksaanRadiologiService->resultForUser(
                $request->user(),
                $validated['noorder']
            );
        } catch (Throwable $exception) {
            Log::warning('Gagal membuat PDF hasil radiologi pasien.', [
                'user_id' => $request->user()?->id,
                'noorder' => $validated['noorder'],
                'message' => $exception->getMessage(),
            ]);

            abort(503, 'PDF hasil radiologi belum dapat dibuat. Koneksi data Khanza tidak tersedia.');
        }

        abort_if($result === null, 404, 'Permintaan radiologi tidak ditemukan.');
        abort_if(
            empty($result['hasil']) && empty($result['gambar']),
            404,
            'Hasil radiologi belum tersedia.'
        );

        try {
            $patient = $this->pemeriksaanRadiologiService
                ->patientForUser($request->user());
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat identitas pasien untuk PDF hasil radiologi.', [
                'user_id' => $request->user()?->id,
                'noorder' => $validated['noorder'],
                'message' => $exception->getMessage(),
            ]);

            abort(503, 'Identitas pasien untuk PDF hasil radiologi belum dapat dimuat.');
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
        $reference = trim((string) $reference, '-') ?: 'Radiologi';
        $filename = 'Hasil-Radiologi-RS-ARSY-'.$reference.'.pdf';

        $pdf = Pdf::loadView(
            'e-pasien.menu.PermintaanTindakan.pemeriksaanRadiologi.resultPdf',
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

    public function image(
        Request $request,
        string $noorder,
        int $image
    ) {
        try {
            $radiologyImage = $this->pemeriksaanRadiologiService->imageForUser(
                $request->user(),
                $noorder,
                $image
            );

            if ($radiologyImage === null) {
                abort(404);
            }

            $baseUrl = rtrim(
                (string) config('services.radiology.image_base_url'),
                '/'
            );

            if ($baseUrl === '') {
                throw new \RuntimeException(
                    'Alamat server gambar radiologi belum dikonfigurasi.'
                );
            }

            $response = Http::connectTimeout(
                (int) config('services.radiology.connect_timeout', 5)
            )
                ->timeout((int) config('services.radiology.timeout', 15))
                ->get($baseUrl.'/'.$radiologyImage['path']);

            if (! $response->successful()) {
                Log::warning('Server gambar radiologi mengembalikan respons gagal.', [
                    'noorder' => $noorder,
                    'image_index' => $image,
                    'status' => $response->status(),
                ]);

                abort(502, 'Gambar radiologi belum dapat dimuat.');
            }

            $contentType = strtolower(trim(
                (string) $response->header('Content-Type')
            ));

            if (! str_starts_with($contentType, 'image/')) {
                Log::warning('Server radiologi mengembalikan berkas non-gambar.', [
                    'noorder' => $noorder,
                    'image_index' => $image,
                    'content_type' => $contentType,
                ]);

                abort(502, 'Berkas radiologi tidak valid.');
            }

            return response($response->body())
                ->header('Content-Type', $contentType)
                ->header(
                    'Content-Disposition',
                    'inline; filename="'.addcslashes(
                        $radiologyImage['filename'],
                        '"\\'
                    ).'"'
                )
                ->header('Cache-Control', 'private, max-age=3600')
                ->header('X-Content-Type-Options', 'nosniff');
        } catch (HttpException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::warning('Gagal mengambil gambar radiologi.', [
                'user_id' => $request->user()?->id,
                'noorder' => $noorder,
                'image_index' => $image,
                'message' => $exception->getMessage(),
            ]);

            abort(502, 'Gambar radiologi belum dapat dimuat.');
        }
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
