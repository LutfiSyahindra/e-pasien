<div class="modal fade schedule-editor-modal" id="scheduleCreateModal" tabindex="-1"
    aria-labelledby="scheduleCreateTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('doctorScheduleSettings.store') }}" id="scheduleCreateForm"
                data-reopen="{{ $errors->any() && old('_action') === 'create' ? 'true' : 'false' }}">
                @csrf
                <input type="hidden" name="_action" value="create">
                <div class="modal-header">
                    <div class="schedule-editor-title">
                        <span><i class="bi bi-calendar-plus"></i></span>
                        <div><small>Jadwal baru</small><h2 class="modal-title" id="scheduleCreateTitle">Tambah Jadwal Dokter</h2></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="schedule-create-hint">Pilih dokter yang belum memiliki jadwal, lalu isi poliklinik dan waktu praktiknya.</p>
                    <div class="schedule-editor-grid">
                        <label class="schedule-editor-field full" for="createDoctorCode">
                            <span>Dokter</span>
                            <span class="schedule-editor-control select">
                                <select name="doctor_code" id="createDoctorCode" required>
                                    <option value="">Pilih dokter</option>
                                    @foreach (($availableDoctors ?? collect()) as $doctor)
                                        <option value="{{ $doctor->kd_dokter }}" @selected(old('doctor_code') === $doctor->kd_dokter)>
                                            {{ $doctor->nm_dokter }} ({{ $doctor->kd_dokter }})
                                        </option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down"></i>
                            </span>
                            @error('doctor_code')<small class="schedule-editor-feedback">{{ $message }}</small>@enderror
                        </label>
                        <label class="schedule-editor-field full" for="createClinicCode">
                            <span>Poliklinik</span>
                            <span class="schedule-editor-control select">
                                <select name="clinic_code" id="createClinicCode" required>
                                    <option value="">Pilih poliklinik</option>
                                    @foreach (($availableClinics ?? collect()) as $clinic)
                                        <option value="{{ $clinic->kd_poli }}" @selected(old('clinic_code') === $clinic->kd_poli)>
                                            {{ $clinic->nm_poli }}
                                        </option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down"></i>
                            </span>
                            @error('clinic_code')<small class="schedule-editor-feedback">{{ $message }}</small>@enderror
                        </label>
                        <label class="schedule-editor-field full" for="createDay">
                            <span>Hari kerja</span>
                            <span class="schedule-editor-control select">
                                <select name="day" id="createDay" required>
                                    @foreach ($days as $dayCode => $dayLabel)
                                        <option value="{{ $dayCode }}" @selected(old('day') === $dayCode)>{{ $dayLabel }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down"></i>
                            </span>
                            @error('day')<small class="schedule-editor-feedback">{{ $message }}</small>@enderror
                        </label>
                        <label class="schedule-editor-field" for="createStartTime">
                            <span>Jam mulai</span>
                            <span class="schedule-editor-control"><input type="time" name="start_time" id="createStartTime" value="{{ old('start_time') }}" required></span>
                            @error('start_time')<small class="schedule-editor-feedback">{{ $message }}</small>@enderror
                        </label>
                        <label class="schedule-editor-field" for="createEndTime">
                            <span>Jam selesai</span>
                            <span class="schedule-editor-control"><input type="time" name="end_time" id="createEndTime" value="{{ old('end_time') }}" required></span>
                            @error('end_time')<small class="schedule-editor-feedback">{{ $message }}</small>@enderror
                        </label>
                        <label class="schedule-editor-field full" for="createQuota">
                            <span>Kuota pasien</span>
                            <span class="schedule-editor-control quota"><input type="number" name="quota" id="createQuota" value="{{ old('quota', 0) }}" min="0" max="9999" step="1" required><em>pasien</em></span>
                            @error('quota')<small class="schedule-editor-feedback">{{ $message }}</small>@enderror
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="schedule-editor-cancel" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="schedule-editor-save"><i class="bi bi-plus-lg"></i><span>Tambah Jadwal</span></button>
                </div>
            </form>
        </div>
    </div>
</div>
