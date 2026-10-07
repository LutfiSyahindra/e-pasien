<form method="POST" action="{{ route('doctorScheduleSettings.destroy') }}"
    class="schedule-delete-form"
    data-confirm-message="Hapus jadwal {{ $schedule['doctor_name'] }} di {{ $schedule['clinic_name'] }}, {{ $schedule['day_label'] }} pukul {{ $schedule['time_label'] }}?">
    @csrf
    @method('DELETE')
    <input type="hidden" name="original_doctor_code" value="{{ $schedule['doctor_code'] }}">
    <input type="hidden" name="original_clinic_code" value="{{ $schedule['clinic_code'] }}">
    <input type="hidden" name="original_day" value="{{ $schedule['day'] }}">
    <input type="hidden" name="original_start_time" value="{{ $schedule['start_time'] }}">
    <input type="hidden" name="filter_q" value="{{ $search }}">
    <input type="hidden" name="filter_day" value="{{ $day }}">
    <input type="hidden" name="filter_clinic" value="{{ $clinic_code }}">
    <button type="submit" class="schedule-admin-delete" aria-label="Hapus jadwal {{ $schedule['doctor_name'] }}">
        <i class="bi bi-trash3"></i> <span>Hapus</span>
    </button>
</form>
