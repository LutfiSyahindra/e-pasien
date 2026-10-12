@php
    $doctorsWithLeaves = ($leaveSchedules ?? collect())->groupBy("doctor_code")
        ->filter(fn ($doctorSchedules) => $doctorSchedules->contains(fn ($schedule) => ! empty($schedule["leave_dates"])));
    $today = now("Asia/Jakarta")->toDateString();
@endphp
<section class="schedule-admin-panel schedule-leave-list" aria-labelledby="savedDoctorLeavesTitle">
    <div class="schedule-admin-panel-head">
        <div>
            <span><i class="bi bi-calendar2-x"></i> Tanggal libur tersimpan</span>
            <h2 id="savedDoctorLeavesTitle">Daftar Dokter Libur</h2>
            <p>Ubah tanggal melalui Edit atau aktifkan kembali jadwal melalui Batalkan.</p>
        </div>
        <span class="schedule-admin-result-count">{{ $doctorsWithLeaves->count() }} dokter</span>
    </div>
    @if ($doctorsWithLeaves->isEmpty())
        <div class="schedule-admin-empty">
            <span><i class="bi bi-calendar2-check"></i></span>
            <strong>Belum ada tanggal libur tersimpan</strong>
            <p>Tambahkan tanggal melalui tombol Libur Dokter.</p>
        </div>
    @else
        <div class="schedule-leave-list-grid">
            @foreach ($doctorsWithLeaves as $doctorCode => $doctorSchedules)
                @php($doctor = $doctorSchedules->first())
                <article class="schedule-leave-doctor-card">
                    <div class="schedule-admin-doctor">
                        <span><i class="bi bi-person-heart"></i></span>
                        <span><strong>{{ $doctor["doctor_name"] }}</strong><small>{{ $doctorCode }}</small></span>
                    </div>
                    @foreach ($doctorSchedules as $schedule)
                        @foreach ($schedule["leave_dates"] ?? [] as $leaveDate)
                            <div class="schedule-leave-date-row">
                                <span>
                                    <strong>
                                        <i class="bi bi-calendar3"></i>
                                        {{ \Illuminate\Support\Carbon::parse($leaveDate)->format("d/m/Y") }}
                                        <em class="schedule-leave-date-state">{{ $leaveDate < $today ? "Selesai" : ($leaveDate === $today ? "Hari ini" : "Terjadwal") }}</em>
                                    </strong>
                                    <small>{{ $schedule["clinic_name"] }}, {{ $schedule["time_label"] }}</small>
                                </span>
                                <div class="schedule-leave-row-actions">
                                    <button type="button" class="schedule-leave-edit js-edit-doctor-leave"
                                        data-doctor-code="{{ $schedule["doctor_code"] }}"
                                        data-day="{{ $schedule["day"] }}"
                                        data-start-time="{{ $schedule["start_time"] }}"
                                        data-leave-date="{{ $leaveDate }}"
                                        aria-haspopup="dialog" aria-controls="scheduleLeaveModal"
                                        aria-label="Edit libur {{ $schedule["doctor_name"] }} tanggal {{ $leaveDate }}, {{ $schedule["clinic_name"] }}, {{ $schedule["time_label"] }}">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                    <form method="POST" action="{{ route("doctorScheduleSettings.updateLeave") }}" class="schedule-leave-form">
                                        @csrf
                                        @method("PATCH")
                                        <input type="hidden" name="original_doctor_code" value="{{ $schedule["doctor_code"] }}">
                                        <input type="hidden" name="original_day" value="{{ $schedule["day"] }}">
                                        <input type="hidden" name="original_start_time" value="{{ $schedule["start_time"] }}">
                                        <input type="hidden" name="is_on_leave" value="0">
                                        <input type="hidden" name="leave_date" value="{{ $leaveDate }}">
                                        <input type="hidden" name="filter_q" value="{{ $search }}">
                                        <input type="hidden" name="filter_day" value="{{ $day }}">
                                        <input type="hidden" name="filter_clinic" value="{{ $clinic_code }}">
                                        <input type="hidden" name="filter_page" value="{{ $schedules->currentPage() }}">
                                        <button type="submit" class="schedule-leave-cancel"
                                            aria-label="Batalkan libur {{ $schedule["doctor_name"] }} tanggal {{ $leaveDate }}, {{ $schedule["clinic_name"] }}, {{ $schedule["time_label"] }}">
                                            <i class="bi bi-x-lg"></i> <span>Batalkan</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </article>
            @endforeach
        </div>
    @endif
</section>
