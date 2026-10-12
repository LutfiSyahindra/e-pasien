<div class="modal fade schedule-editor-modal" id="scheduleLeaveModal" tabindex="-1"
    aria-labelledby="scheduleLeaveTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="schedule-editor-title">
                    <span><i class="bi bi-calendar2-x"></i></span>
                    <div>
                        <small>Libur berdasarkan tanggal</small>
                        <h2 class="modal-title" id="scheduleLeaveTitle">Atur Libur Dokter</h2>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="schedule-editor-context">
                    <span><i class="bi bi-person-heart"></i></span>
                    <div>
                        <strong id="leaveFormHeading">Pilih dokter dan tanggal libur</strong>
                        <small>Libur hanya berlaku pada jam dan poliklinik yang dipilih.</small>
                    </div>
                </div>
                <form method="POST" action="{{ route("doctorScheduleSettings.updateLeave") }}"
                    id="scheduleLeaveForm" class="schedule-leave-form"
                    data-reopen="{{ $errors->any() && old("_method") === "PATCH" && old("is_on_leave") == "1" ? "true" : "false" }}">
                    @csrf
                    @method("PATCH")
                    <input type="hidden" name="original_doctor_code" value="{{ old("original_doctor_code") }}">
                    <input type="hidden" name="original_leave_date" value="{{ old("original_leave_date") }}">
                    <input type="hidden" name="original_day" value="{{ old("original_day") }}">
                    <input type="hidden" name="original_start_time" value="{{ old("original_start_time") }}">
                    <input type="hidden" name="is_on_leave" value="1">
                    <input type="hidden" name="filter_q" value="{{ $search }}">
                    <input type="hidden" name="filter_day" value="{{ $day }}">
                    <input type="hidden" name="filter_clinic" value="{{ $clinic_code }}">
                    <input type="hidden" name="filter_page" value="{{ $schedules->currentPage() }}">
                    <label class="schedule-editor-field">
                        <span>Dokter</span>
                        <span class="schedule-editor-control schedule-leave-doctor-control">
                            <i class="bi bi-person-heart"></i>
                            <select name="doctor_code" id="leaveDoctorCode" required
                                @class(["is-invalid" => $errors->has("original_doctor_code")])>
                                <option value="">Pilih dokter</option>
                                @foreach (($leaveSchedules ?? collect())->unique("doctor_code") as $leaveSchedule)
                                    <option value="{{ $leaveSchedule["doctor_code"] }}"
                                        @selected(old("original_doctor_code") === $leaveSchedule["doctor_code"])>
                                        {{ $leaveSchedule["doctor_name"] }} ({{ $leaveSchedule["doctor_code"] }})
                                    </option>
                                @endforeach
                            </select>
                            <i class="bi bi-chevron-down"></i>
                        </span>
                        <small class="schedule-create-hint">Ketik nama atau kode dokter untuk mencari.</small>
                        @error("original_doctor_code")
                            <small class="schedule-editor-feedback">{{ $message }}</small>
                        @enderror
                    </label>
                    <label class="schedule-editor-field">
                        <span>Tanggal libur</span>
                        <span class="schedule-editor-control">
                            <i class="bi bi-calendar3"></i>
                            <input type="date" name="leave_date" value="{{ old("leave_date") }}"
                                min="{{ now("Asia/Jakarta")->toDateString() }}" required
                                @class(["is-invalid" => $errors->has("leave_date")])>
                        </span>
                        @error("leave_date")
                            <small class="schedule-editor-feedback">{{ $message }}</small>
                        @enderror
                    </label>
                    <label class="schedule-editor-field" id="leaveScheduleFields" hidden>
                        <span>Jam dan poliklinik</span>
                        <span class="schedule-editor-control">
                            <i class="bi bi-hospital"></i>
                            <select id="leaveScheduleSelection" disabled>
                                <option value="">Pilih jadwal praktik</option>
                            </select>
                            <i class="bi bi-chevron-down"></i>
                        </span>
                    </label>
                    <p class="schedule-create-hint" id="leaveScheduleHint" aria-live="polite">Pilih dokter dan tanggal untuk melihat jadwal praktiknya.</p>
                    @error("original_leave_date")
                        <small class="schedule-editor-feedback">{{ $message }}</small>
                    @enderror
                    @error("schedule")
                        <small class="schedule-editor-feedback">{{ $message }}</small>
                    @enderror
                </form>
            </div>
            <div class="modal-footer">
                <span class="schedule-editor-change-state" id="leaveFooterHint">Lengkapi dokter dan tanggal libur.</span>
                <button type="button" class="schedule-editor-cancel" data-bs-dismiss="modal">Tutup</button>
                <button type="submit" form="scheduleLeaveForm" id="leaveSaveButton" class="schedule-editor-save schedule-leave-save" disabled>
                    <i class="bi bi-check2-circle"></i> <span>Simpan Tanggal Libur</span>
                </button>
            </div>
        </div>
    </div>
</div>
<script type="application/json" id="doctorLeaveSchedulesData">@json(($leaveSchedules ?? collect())->values()->all())</script>
