<?php

namespace App\Http\Controllers\Epasien\bridging;

use App\Http\Controllers\Controller;
use App\Services\epasien\bridging\RencanaKontrolService;
use App\Services\epasien\settings\RegistrationRoleConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RencanaKontrolController extends Controller
{
    public function __construct(
        private readonly RencanaKontrolService $rencanaKontrolService,
        private readonly RegistrationRoleConfigurationService $roleConfigurationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (! $this->roleConfigurationService->isConfigured($request->user())) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk mencari surat kontrol BPJS.',
            ], 403);
        }

        $validated = $request->validate([
            'tgl_registrasi' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'no_peserta' => ['required', 'string', 'max:25', 'regex:/^\d+$/'],
        ], [
            'no_peserta.required' => 'No. kartu wajib diisi untuk mencari surat kontrol.',
            'no_peserta.max' => 'No. kartu tidak boleh lebih dari 25 karakter.',
            'no_peserta.regex' => 'No. kartu hanya boleh berisi angka.',
        ]);

        $data = $this->rencanaKontrolService->listByCardNumber(
            $validated['tgl_registrasi'],
            $validated['no_peserta'],
            2
        );
        $metadataCode = (string) $data['meta_data']['code'];
        $controlLetterCount = count($data['surat_kontrol']);
        $referral = is_array($data['rujukan'] ?? null)
            ? $data['rujukan']
            : null;
        $referralCount = is_array($data['daftar_rujukan'] ?? null)
            ? count($data['daftar_rujukan'])
            : ($referral !== null ? 1 : 0);
        $documentSource = (string) ($data['sumber_dokumen'] ?? '');
        $allControlLettersHaveIssuedSep = (bool) (
            $data['semua_surat_kontrol_sep_terbit'] ?? false
        );
        $isSuccessful = in_array($metadataCode, ['200', '201', '204'], true);

        if (! $isSuccessful) {
            return response()->json([
                'status' => 'error',
                'message' => $data['meta_data']['message'] ?: 'Surat kontrol belum dapat dimuat dari VClaim.',
                'data' => $data,
            ], $metadataCode === '504' ? 504 : 502);
        }

        return response()->json([
            'status' => 'success',
            'message' => $referral !== null
                ? ($referralCount > 1
                    ? "{$referralCount} rujukan "
                        .($documentSource === 'rujukan_pcare' ? 'PCare' : 'rumah sakit')
                        .' ditemukan.'
                    : ($documentSource === 'rujukan_pcare'
                        ? 'Rujukan PCare ditemukan.'
                        : 'Rujukan rumah sakit ditemukan.'))
                : ($controlLetterCount > 0 && ! $allControlLettersHaveIssuedSep
                    ? "{$controlLetterCount} surat kontrol ditemukan."
                    : ($allControlLettersHaveIssuedSep
                        ? 'Semua surat kontrol sudah memiliki SEP terbit dan rujukan tidak ditemukan.'
                        : ($data['meta_data']['message']
                            ?: 'Surat kontrol dan rujukan tidak ditemukan.'))),
            'data' => $data,
        ]);
    }

    public function show(Request $request, string $controlLetterNumber): JsonResponse
    {
        if (! $this->roleConfigurationService->isConfigured($request->user())) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk melihat detail surat kontrol BPJS.',
            ], 403);
        }

        $validator = validator(
            ['no_surat_kontrol' => $controlLetterNumber],
            [
                'no_surat_kontrol' => [
                    'required',
                    'string',
                    'max:50',
                    'regex:/^[A-Za-z0-9-]+$/',
                ],
            ],
            [
                'no_surat_kontrol.required' => 'Nomor surat kontrol wajib diisi.',
                'no_surat_kontrol.max' => 'Nomor surat kontrol tidak boleh lebih dari 50 karakter.',
                'no_surat_kontrol.regex' => 'Format nomor surat kontrol tidak valid.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Nomor surat kontrol belum valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $this->rencanaKontrolService->detailByControlLetterNumber(
            $validator->validated()['no_surat_kontrol']
        );
        $metadataCode = (string) $data['meta_data']['code'];

        if ($metadataCode !== '200' || $data['surat_kontrol'] === null) {
            $isNotFound = in_array($metadataCode, ['201', '204'], true);

            return response()->json([
                'status' => $isNotFound ? 'not_found' : 'error',
                'message' => $data['meta_data']['message']
                    ?: ($isNotFound
                        ? 'Detail surat kontrol tidak ditemukan.'
                        : 'Detail surat kontrol belum dapat dimuat dari VClaim.'),
                'data' => $data,
            ], $isNotFound ? 404 : ($metadataCode === '504' ? 504 : 502));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Detail surat kontrol berhasil dimuat.',
            'data' => $data,
        ]);
    }
}
