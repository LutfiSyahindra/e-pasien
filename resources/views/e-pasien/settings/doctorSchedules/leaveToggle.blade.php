@php($leaveDates = $schedule["leave_dates"] ?? [])
<div class="schedule-leave-setting">
    <small class="schedule-leave-status">
        @if ($schedule["has_undated_leave"] ?? false)
            Libur lama perlu tanggal
        @elseif ($leaveDates !== [])
            {{ \Illuminate\Support\Carbon::parse($leaveDates[0])->format("d/m/Y") }}
            @if (count($leaveDates) > 1)
                (+{{ count($leaveDates) - 1 }} tanggal)
            @endif
        @else
            Belum ada tanggal libur
        @endif
    </small>
</div>
