<?php

namespace App\Http\Controllers\Epasien\settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Epasien\Settings\DestroyDoctorScheduleRequest;
use App\Http\Requests\Epasien\Settings\StoreDoctorScheduleRequest;
use App\Http\Requests\Epasien\Settings\UpdateDoctorScheduleLeaveRequest;
use App\Http\Requests\Epasien\Settings\UpdateDoctorScheduleRequest;
use App\Services\epasien\settings\DoctorScheduleService;
use App\Support\Epasien\DoctorScheduleDay;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class DoctorScheduleController extends Controller
{
    public function __construct(
        private readonly DoctorScheduleService $doctorScheduleService
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'hari' => [
                'nullable',
                'string',
                Rule::in(DoctorScheduleDay::ACCEPTED_FILTER_DAYS),
            ],
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

    public function store(StoreDoctorScheduleRequest $request)
    {
        $schedule = $request->validated();
        $schedule['quota'] = (int) $schedule['quota'];

        try {
            $this->doctorScheduleService->create($schedule);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Gagal menambahkan jadwal dokter.', [
                'user_id' => $request->user()?->id,
                'doctor_code' => $schedule['doctor_code'],
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'schedule' => 'Jadwal belum dapat ditambahkan. Silakan coba kembali.',
            ])->withInput();
        }

        Log::info('Jadwal dokter ditambahkan melalui E-Pasien.', [
            'user_id' => $request->user()?->id,
            'schedule' => $schedule,
        ]);

        return redirect()->route('doctorScheduleSettings.index', ['q' => $schedule['doctor_code']])
            ->with('status', 'Jadwal dokter berhasil ditambahkan dan langsung tampil pada menu Jadwal Dokter.');
    }

    public function destroy(DestroyDoctorScheduleRequest $request)
    {
        $validated = $request->validated();
        $original = [
            'doctor_code' => $validated['original_doctor_code'],
            'clinic_code' => $validated['original_clinic_code'],
            'day' => $validated['original_day'],
            'start_time' => $validated['original_start_time'],
        ];

        try {
            $this->doctorScheduleService->delete($original);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Gagal menghapus jadwal dokter.', [
                'user_id' => $request->user()?->id,
                ...$original,
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors(['schedule' => 'Jadwal belum dapat dihapus. Silakan coba kembali.']);
        }

        Log::info('Jadwal dokter dihapus melalui E-Pasien.', ['user_id' => $request->user()?->id, ...$original]);

        return redirect()->route('doctorScheduleSettings.index', array_filter([
            'q' => $validated['filter_q'] ?? null,
            'hari' => $validated['filter_day'] ?? null,
            'poli' => $validated['filter_clinic'] ?? null,
        ], fn (mixed $value): bool => $value !== null && $value !== ''))
            ->with('status', 'Jadwal dokter berhasil dihapus.');
    }

    public function updateLeave(UpdateDoctorScheduleLeaveRequest $request)
    {
        $validated = $request->validated();
        $original = [
            'doctor_code' => $validated['original_doctor_code'],
            'day' => $validated['original_day'],
            'start_time' => $validated['original_start_time'],
        ];
        $isOnLeave = $request->boolean('is_on_leave');
        $date = $validated['leave_date'];
        $originalDate = $validated['original_leave_date'] ?? null;

        try {
            $this->doctorScheduleService->setOnLeave($original, $isOnLeave, $date, $originalDate);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Gagal memperbarui status libur jadwal dokter.', [
                'user_id' => $request->user()?->id,
                'doctor_code' => $original['doctor_code'],
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'schedule' => 'Status libur belum dapat disimpan. Silakan coba kembali.',
            ])->withInput();
        }

        Log::info('Status libur jadwal dokter diperbarui melalui E-Pasien.', [
            'user_id' => $request->user()?->id,
            ...$original,
            'is_on_leave' => $isOnLeave,
            'leave_date' => $date,
            'original_leave_date' => $originalDate,
        ]);

        return redirect()->route('doctorScheduleSettings.index', array_filter([
            'q' => $validated['filter_q'] ?? null,
            'hari' => $validated['filter_day'] ?? null,
            'poli' => $validated['filter_clinic'] ?? null,
            'page' => $validated['filter_page'] ?? null,
        ], fn (mixed $value): bool => $value !== null && $value !== ''))
            ->with('status', $originalDate !== null
                ? 'Tanggal libur dokter berhasil diubah dari '.Carbon::parse($originalDate)->format('d/m/Y').' menjadi '.Carbon::parse($date)->format('d/m/Y').'.'
                : ($isOnLeave
                ? 'Dokter libur pada tanggal '.Carbon::parse($date)->format('d/m/Y').' untuk jam dan poliklinik yang dipilih.'
                : 'Libur dokter pada tanggal '.Carbon::parse($date)->format('d/m/Y').' berhasil dibatalkan.'));
    }
}
