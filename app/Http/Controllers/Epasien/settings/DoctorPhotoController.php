<?php

namespace App\Http\Controllers\Epasien\settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Epasien\Settings\UpdateDoctorPhotoRequest;
use App\Services\epasien\settings\DoctorPhotoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class DoctorPhotoController extends Controller
{
    public function __construct(
        private readonly DoctorPhotoService $doctorPhotoService
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
        ]);
        $search = trim((string) ($validated['q'] ?? ''));
        $page = $this->doctorPhotoService->emptyPage($search);
        $connectionError = null;

        try {
            $page = $this->doctorPhotoService->page($search);
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat data dokter untuk pengaturan foto.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            $connectionError = 'Data dokter belum dapat dimuat. Koneksi data rumah sakit sedang tidak tersedia.';
        }

        return view('e-pasien.settings.doctorPhotos.index', [
            ...$page,
            'connectionError' => $connectionError,
        ]);
    }

    public function update(UpdateDoctorPhotoRequest $request)
    {
        $validated = $request->validated();

        try {
            $this->doctorPhotoService->update(
                $validated['doctor_code'],
                $request->file('doctor_photo'),
                $validated['doctor_photo_cropped'] ?? null,
                $request->user()?->id
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Gagal menyimpan foto dokter.', [
                'user_id' => $request->user()?->id,
                'doctor_code' => $validated['doctor_code'],
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withErrors(['doctor_photo' => 'Foto dokter belum dapat disimpan. Silakan coba lagi.'])
                ->withInput();
        }

        return redirect()
            ->route('doctorPhotoSettings.index', array_filter([
                'q' => $validated['filter_q'] ?? null,
                'page' => $validated['filter_page'] ?? null,
            ], fn (mixed $value): bool => $value !== null && $value !== ''))
            ->with('status', 'Foto dokter berhasil disimpan dan langsung tampil pada landing page serta Jadwal Dokter.');
    }

    public function destroy(Request $request, string $doctorCode)
    {
        try {
            $this->doctorPhotoService->delete($doctorCode);
        } catch (Throwable $exception) {
            Log::error('Gagal menghapus foto dokter.', [
                'user_id' => $request->user()?->id,
                'doctor_code' => $doctorCode,
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors(['doctor_photo' => 'Foto dokter belum dapat dihapus. Silakan coba lagi.']);
        }

        return back()->with('status', 'Foto dokter berhasil dihapus. Tampilan kembali memakai inisial dokter.');
    }
}
