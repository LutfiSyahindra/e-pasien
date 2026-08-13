<?php

namespace App\Http\Controllers\Epasien\settings;

use App\Http\Controllers\Controller;
use App\Services\epasien\menu\DaftarOnlineService;
use App\Services\epasien\settings\PatientGuarantorConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PatientGuarantorConfigurationController extends Controller
{
    public function __construct(
        private readonly DaftarOnlineService $daftarOnlineService,
        private readonly PatientGuarantorConfigurationService $configurationService,
    ) {}

    public function index(Request $request): View
    {
        $guarantors = [];
        $selectedCodes = [];
        $connectionError = null;

        try {
            $guarantors = $this->daftarOnlineService->penjaminOptions(true);
            $selectedCodes = $request->session()->hasOldInput('guarantor_codes')
                ? (array) $request->session()->getOldInput('guarantor_codes', [])
                : $this->configurationService->selectedCodes($guarantors);
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat konfigurasi penjamin pasien.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            $connectionError = 'Data penjamin belum dapat dimuat. '
                .'Koneksi data rumah sakit sedang tidak tersedia.';
        }

        return view('e-pasien.settings.patientGuarantors.index', [
            'guarantors' => $guarantors,
            'selectedCodes' => array_map('strval', $selectedCodes),
            'configurationSaved' => $this->configurationService->isConfigured(),
            'connectionError' => $connectionError,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'guarantor_codes' => ['required', 'array', 'min:1', 'max:500'],
            'guarantor_codes.*' => ['required', 'string', 'max:10', 'distinct'],
        ], [
            'guarantor_codes.required' => 'Pilih minimal satu penjamin untuk pendaftaran pasien.',
            'guarantor_codes.min' => 'Pilih minimal satu penjamin untuk pendaftaran pasien.',
            'guarantor_codes.*.distinct' => 'Pilihan penjamin tidak boleh duplikat.',
        ]);

        try {
            $availableCodes = collect($this->daftarOnlineService->penjaminOptions(true))
                ->pluck('kd_pj')
                ->map(fn (mixed $code): string => strtoupper(trim((string) $code)))
                ->filter()
                ->values()
                ->all();
        } catch (Throwable $exception) {
            Log::error('Gagal memvalidasi konfigurasi penjamin pasien.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withErrors([
                    'guarantor_codes' => 'Konfigurasi belum dapat disimpan karena data penjamin rumah sakit tidak tersedia.',
                ])
                ->withInput();
        }

        $requestedCodes = collect($validated['guarantor_codes'])
            ->map(fn (mixed $code): string => strtoupper(trim((string) $code)))
            ->filter()
            ->values()
            ->all();
        $unknownCodes = array_values(array_diff($requestedCodes, $availableCodes));

        if ($unknownCodes !== []) {
            throw ValidationException::withMessages([
                'guarantor_codes' => 'Terdapat penjamin yang tidak tersedia pada data rumah sakit. Muat ulang halaman lalu pilih kembali.',
            ]);
        }

        try {
            $this->configurationService->sync($requestedCodes, $request->user());
        } catch (Throwable $exception) {
            Log::error('Gagal menyimpan konfigurasi penjamin pasien.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withErrors([
                    'guarantor_codes' => 'Konfigurasi belum dapat disimpan. Silakan coba kembali.',
                ])
                ->withInput();
        }

        Log::info('Konfigurasi penjamin pasien diperbarui.', [
            'user_id' => $request->user()?->id,
            'allowed_guarantor_codes' => $requestedCodes,
        ]);

        return redirect()
            ->route('patientGuarantorSettings.index')
            ->with('status', 'Pilihan penjamin pasien berhasil disimpan dan langsung berlaku pada pendaftaran online.');
    }
}
