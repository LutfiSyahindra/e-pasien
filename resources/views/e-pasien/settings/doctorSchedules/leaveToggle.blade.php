@php($isOnLeave = $schedule["is_on_leave"] ?? false)
<form method="POST" action="{{ route("doctorScheduleSettings.updateLeave") }}"
    class="schedule-leave-form">
    @csrf
    @method("PATCH")
    <input type="hidden" name="original_doctor_code" value="{{ $schedule["doctor_code"] }}">
    <input type="hidden" name="original_day" value="{{ $schedule["day"] }}">
    <input type="hidden" name="original_start_time" value="{{ $schedule["start_time"] }}">
    <input type="hidden" name="is_on_leave" value="{{ $isOnLeave ? "0" : "1" }}">
    <input type="hidden" name="filter_q" value="{{ $search }}">
    <input type="hidden" name="filter_day" value="{{ $day }}">
    <input type="hidden" name="filter_clinic" value="{{ $clinic_code }}">
    <input type="hidden" name="filter_page" value="{{ $schedules->currentPage() }}">
    <button type="submit" class="schedule-leave-toggle" role="switch"
        aria-checked="{{ $isOnLeave ? "true" : "false" }}"
        aria-label="Libur untuk {{ $schedule["doctor_name"] }}, {{ $schedule["day_label"] }}, {{ $schedule["time_label"] }}">
        <span class="schedule-leave-track" aria-hidden="true"><span></span></span>
        <span>Libur</span>
    </button>
    <small class="schedule-leave-status">{{ $isOnLeave ? "Sedang libur" : "Jadwal aktif" }}</small>
</form>
