<?php

namespace App\Http\Controllers\Epasien\settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Epasien\Settings\UpdateDoctorScheduleRequest;
use App\Services\epasien\settings\DoctorScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class DoctorScheduleController extends Controller
{
    private const DAYS = [
        'SEMUA',
        'SENIN',
        'SELASA',
        'RABU',
        'KAMIS',
        'JUMAT',
        'SABTU',
        'AKHAD',
    ];

    public function __construct(
        private readonly DoctorScheduleService $doctorScheduleService
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'hari' => ['nullable', 'string', Rule::in(self::DAYS)],
            'poli' => ['nullable', 'string', 'max:5'],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $day = $validated['hari'] ?? 'SEMUA';
        $clinicCode = trim((string) ($validated['poli'] ?? ''));
        $page = $this->doctorScheduleService->emptyPage($search, $day, $clinicCode);
        $connectionError = null;

        try {
            $page = $this->doctorScheduleService->page($search, $day, $clinicCode);
        } catch (Throwable $exception) {
            Log::warning('Gagal memuat pengaturan jadwal dokter.', [
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            $connectionError = 'Data jadwal belum dapat dimuat. '
                .'Koneksi data rumah sakit sedang tidak tersedia.';
        }

        return view('e-pasien.settings.doctorSchedules.index', [
            ...$page,
            'connectionError' => $connectionError,
        ]);
    }

    public function update(UpdateDoctorScheduleRequest $request)
    {
        $validated = $request->validated();
        $original = [
            'doctor_code' => $validated['original_doctor_code'],
            'day' => $validated['original_day'],
            'start_time' => $validated['original_start_time'],
        ];
        $changes = [
            'day' => $validated['day'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'quota' => (int) $validated['quota'],
        ];

        try {
            $this->doctorScheduleService->update($original, $changes);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Gagal memperbarui jadwal dokter.', [
                'user_id' => $request->user()?->id,
                'doctor_code' => $original['doctor_code'],
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withErrors([
                    'schedule' => 'Jadwal belum dapat disimpan. '
                        .'Koneksi data rumah sakit sedang tidak tersedia.',
                ])
                ->withInput();
        }

        Log::info('Jadwal dokter diperbarui melalui E-Pasien.', [
            'user_id' => $request->user()?->id,
            'doctor_code' => $original['doctor_code'],
            'before' => $original,
            'after' => $changes,
        ]);

        return redirect()
            ->route('doctorScheduleSettings.index', array_filter([
                'q' => $validated['filter_q'] ?? null,
                'hari' => $validated['filter_day'] ?? null,
                'poli' => $validated['filter_clinic'] ?? null,
                'page' => $validated['filter_page'] ?? null,
            ], fn (mixed $value): bool => $value !== null && $value !== ''))
            ->with('status', 'Jadwal dokter berhasil diperbarui dan langsung tersinkron ke menu Jadwal Dokter.');
    }
}
