@extends("template.epasien.appPasien")

@section("title", "Pengaturan Jadwal Dokter | E-Pasien")

@push("style")
    <link href="{{ asset("epasien/assets/css/doctor-schedule-settings.css") }}"
        rel="stylesheet" />
@endpush

@section("content")
    @php
        $hasFilters = $search !== "" || $day !== "SEMUA" || $clinic_code !== "";
        $reopenEditor = $errors->any() && old("original_doctor_code");
    @endphp

    <main class="schedule-admin-page">
        <nav class="schedule-admin-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route("dashboard") }}" aria-label="Kembali ke Dashboard">
                <i class="bi bi-house-door"></i>
            </a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span>Landing Page &amp; Data</span>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span class="active">Jadwal Dokter</span>
        </nav>

        <section class="schedule-admin-hero">
            <div class="schedule-admin-heading">
                <span class="schedule-admin-icon">
                    <i class="bi bi-calendar2-check"></i>
                </span>
                <div>
                    <span class="schedule-admin-eyebrow">Operasional layanan</span>
                    <h1>Pengaturan Jadwal Dokter</h1>
                    <p>Atur hari praktik, jam layanan, dan kuota dokter pada data jadwal rumah sakit.</p>
                </div>
            </div>
            <div class="schedule-admin-live">
                <span>
                    <i class="bi bi-arrow-repeat"></i>
                    Sinkron langsung
                </span>
                <small>Perubahan tampil pada menu Jadwal Dokter</small>
                @can("EPASIEN.MENU.JADWAL_DOKTER")
                    <a href="{{ route("jadwalDokter.index", ["hari" => "SEMUA"]) }}" target="_blank"
                        rel="noopener">
                        Lihat tampilan pasien
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                @endcan
            </div>
        </section>

        @if (session("status"))
            <div class="schedule-admin-alert success" role="status">
                <span><i class="bi bi-check2-circle"></i></span>
                <div>
                    <strong>Perubahan tersimpan</strong>
                    <p>{{ session("status") }}</p>
                </div>
            </div>
        @endif

        @if ($connectionError)
            <div class="schedule-admin-alert danger" role="alert">
                <span><i class="bi bi-cloud-slash"></i></span>
                <div>
                    <strong>Data belum dapat ditampilkan</strong>
                    <p>{{ $connectionError }}</p>
                </div>
            </div>
        @endif

        @error("schedule")
            <div class="schedule-admin-alert danger" role="alert">
                <span><i class="bi bi-exclamation-triangle"></i></span>
                <div>
                    <strong>Jadwal tidak dapat disimpan</strong>
                    <p>{{ $message }}</p>
                </div>
            </div>
        @enderror

        <section class="schedule-admin-stats" aria-label="Ringkasan jadwal aktif">
            <article>
                <span class="purple"><i class="bi bi-calendar2-week"></i></span>
                <div>
                    <small>Jadwal aktif</small>
                    <strong>{{ number_format($summary["schedules"], 0, ",", ".") }}</strong>
                    <em>baris jadwal</em>
                </div>
            </article>
            <article>
                <span class="green"><i class="bi bi-person-heart"></i></span>
                <div>
                    <small>Dokter</small>
                    <strong>{{ number_format($summary["doctors"], 0, ",", ".") }}</strong>
                    <em>tenaga medis aktif</em>
                </div>
            </article>
            <article>
                <span class="cyan"><i class="bi bi-hospital"></i></span>
                <div>
                    <small>Poliklinik</small>
                    <strong>{{ number_format($summary["clinics"], 0, ",", ".") }}</strong>
                    <em>unit layanan aktif</em>
                </div>
            </article>
        </section>

        <section class="schedule-admin-panel">
            <div class="schedule-admin-panel-head">
                <div>
                    <span><i class="bi bi-sliders"></i> Data jadwal spesialis</span>
                    <h2>Daftar Jadwal Dokter</h2>
                    <p>Cari jadwal, lalu gunakan tombol edit untuk memperbarui layanan.</p>
                </div>
                <span class="schedule-admin-result-count">
                    {{ number_format($schedules->total(), 0, ",", ".") }} jadwal ditemukan
                </span>
            </div>

            <form action="{{ route("doctorScheduleSettings.index") }}" method="GET"
                class="schedule-admin-filters">
                <label class="schedule-admin-search">
                    <span>Cari dokter atau poliklinik</span>
                    <span class="schedule-admin-control">
                        <i class="bi bi-search"></i>
                        <input type="search" name="q" value="{{ $search }}"
                            placeholder="Nama atau kode dokter..." maxlength="80"
                            autocomplete="off" @disabled($connectionError)>
                    </span>
                </label>

                <label>
                    <span>Hari kerja</span>
                    <span class="schedule-admin-control select">
                        <i class="bi bi-calendar3"></i>
                        <select name="hari" @disabled($connectionError)>
                            <option value="SEMUA">Semua hari</option>
                            @foreach ($days as $dayCode => $dayLabel)
                                <option value="{{ $dayCode }}" @selected($day === $dayCode)>
                                    {{ $dayLabel }}
                                </option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down"></i>
                    </span>
                </label>

                <label>
                    <span>Poliklinik</span>
                    <span class="schedule-admin-control select">
                        <i class="bi bi-hospital"></i>
                        <select name="poli" @disabled($connectionError)>
                            <option value="">Semua poliklinik</option>
                            @foreach ($clinics as $clinic)
                                <option value="{{ $clinic["code"] }}"
                                    @selected($clinic_code === $clinic["code"])>
                                    {{ $clinic["name"] }}
                                </option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down"></i>
                    </span>
                </label>

                <button type="submit" class="schedule-admin-filter-submit"
                    @disabled($connectionError)>
                    <i class="bi bi-search"></i>
                    <span>Terapkan</span>
                </button>

                @if ($hasFilters)
                    <a href="{{ route("doctorScheduleSettings.index") }}"
                        class="schedule-admin-filter-reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset</span>
                    </a>
                @endif
            </form>

            @if (! $connectionError && $schedules->count() === 0)
                <div class="schedule-admin-empty">
                    <span><i class="bi bi-calendar2-x"></i></span>
                    <strong>Jadwal tidak ditemukan</strong>
                    <p>Coba ubah kata kunci atau filter hari dan poliklinik.</p>
                    @if ($hasFilters)
                        <a href="{{ route("doctorScheduleSettings.index") }}">Tampilkan semua jadwal</a>
                    @endif
                </div>
            @elseif (! $connectionError)
                <div class="schedule-admin-table-wrap">
                    <table class="schedule-admin-table">
                        <thead>
                            <tr>
                                <th>Dokter</th>
                                <th>Poliklinik</th>
                                <th>Hari</th>
                                <th>Jam praktik</th>
                                <th>Kuota</th>
                                <th><span class="visually-hidden">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($schedules as $schedule)
                                <tr>
                                    <td>
                                        <span class="schedule-admin-doctor">
                                            <span><i class="bi bi-person"></i></span>
                                            <span>
                                                <strong>{{ $schedule["doctor_name"] }}</strong>
                                                <small>{{ $schedule["doctor_code"] }}</small>
                                            </span>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="schedule-admin-clinic">{{ $schedule["clinic_name"] }}</strong>
                                        <small class="schedule-admin-code">{{ $schedule["clinic_code"] }}</small>
                                    </td>
                                    <td>
                                        <span class="schedule-admin-day">{{ $schedule["day_label"] }}</span>
                                    </td>
                                    <td>
                                        <span class="schedule-admin-time">
                                            <i class="bi bi-clock"></i>
                                            {{ $schedule["time_label"] }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="schedule-admin-quota">
                                            {{ number_format($schedule["quota"], 0, ",", ".") }}
                                            <small>pasien</small>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="schedule-admin-edit js-edit-schedule"
                                            data-doctor-code="{{ $schedule["doctor_code"] }}"
                                            data-doctor-name="{{ $schedule["doctor_name"] }}"
                                            data-clinic-code="{{ $schedule["clinic_code"] }}"
                                            data-clinic-name="{{ $schedule["clinic_name"] }}"
                                            data-day="{{ $schedule["day"] }}"
                                            data-start-time="{{ $schedule["start_time"] }}"
                                            data-end-time="{{ $schedule["end_time"] }}"
                                            data-quota="{{ $schedule["quota"] }}"
                                            aria-label="Edit jadwal {{ $schedule["doctor_name"] }}">
                                            <i class="bi bi-pencil-square"></i>
                                            <span>Edit</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="schedule-admin-mobile-list">
                    @foreach ($schedules as $schedule)
                        <article>
                            <div class="schedule-admin-mobile-top">
                                <span class="schedule-admin-day">{{ $schedule["day_label"] }}</span>
                                <span class="schedule-admin-code">{{ $schedule["clinic_code"] }}</span>
                            </div>
                            <span class="schedule-admin-doctor">
                                <span><i class="bi bi-person"></i></span>
                                <span>
                                    <strong>{{ $schedule["doctor_name"] }}</strong>
                                    <small>{{ $schedule["clinic_name"] }}</small>
                                </span>
                            </span>
                            <div class="schedule-admin-mobile-details">
                                <span>
                                    <small>Jam praktik</small>
                                    <strong>{{ $schedule["time_label"] }}</strong>
                                </span>
                                <span>
                                    <small>Kuota</small>
                                    <strong>{{ $schedule["quota_label"] }}</strong>
                                </span>
                            </div>
                            <button type="button" class="schedule-admin-edit js-edit-schedule"
                                data-doctor-code="{{ $schedule["doctor_code"] }}"
                                data-doctor-name="{{ $schedule["doctor_name"] }}"
                                data-clinic-code="{{ $schedule["clinic_code"] }}"
                                data-clinic-name="{{ $schedule["clinic_name"] }}"
                                data-day="{{ $schedule["day"] }}"
                                data-start-time="{{ $schedule["start_time"] }}"
                                data-end-time="{{ $schedule["end_time"] }}"
                                data-quota="{{ $schedule["quota"] }}">
                                <i class="bi bi-pencil-square"></i>
                                <span>Ubah jadwal</span>
                            </button>
                        </article>
                    @endforeach
                </div>

                @if ($schedules->hasPages())
                    <div class="schedule-admin-pagination">
                        {{ $schedules->onEachSide(1)->links() }}
                    </div>
                @endif
            @endif
        </section>

        <aside class="schedule-admin-note">
            <i class="bi bi-info-circle-fill"></i>
            <p>
                <strong>Sumber data bersama</strong>
                Menu ini dan menu Jadwal Dokter pasien memakai tabel
                <code>jadwal_spesialis</code> yang sama. Tidak diperlukan proses sinkronisasi manual.
            </p>
        </aside>
    </main>

    <div class="modal fade schedule-editor-modal" id="scheduleEditorModal" tabindex="-1"
        aria-labelledby="scheduleEditorTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route("doctorScheduleSettings.update") }}"
                    id="scheduleEditorForm"
                    data-reopen="{{ $reopenEditor ? "true" : "false" }}">
                    @csrf
                    @method("PUT")

                    <input type="hidden" name="original_doctor_code" id="originalDoctorCode"
                        value="{{ old("original_doctor_code") }}">
                    <input type="hidden" name="original_day" id="originalDay"
                        value="{{ old("original_day") }}">
                    <input type="hidden" name="original_start_time" id="originalStartTime"
                        value="{{ old("original_start_time") }}">
                    <input type="hidden" name="filter_q" value="{{ $search }}">
                    <input type="hidden" name="filter_day" value="{{ $day }}">
                    <input type="hidden" name="filter_clinic" value="{{ $clinic_code }}">
                    <input type="hidden" name="filter_page" value="{{ $schedules->currentPage() }}">

                    <div class="modal-header">
                        <div class="schedule-editor-title">
                            <span><i class="bi bi-calendar2-check"></i></span>
                            <div>
                                <small>Perbarui layanan</small>
                                <h2 class="modal-title" id="scheduleEditorTitle">Edit Jadwal Dokter</h2>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">
                        <div class="schedule-editor-context">
                            <span><i class="bi bi-person-heart"></i></span>
                            <div>
                                <strong id="editorDoctorName">Dokter</strong>
                                <small id="editorClinicName">Poliklinik</small>
                            </div>
                        </div>

                        @error("schedule")
                            <div class="schedule-editor-error">
                                <i class="bi bi-exclamation-triangle"></i>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror

                        <div class="schedule-editor-grid">
                            <label class="schedule-editor-field full">
                                <span>Hari kerja</span>
                                <span class="schedule-editor-control select">
                                    <i class="bi bi-calendar3"></i>
                                    <select name="day" id="editorDay"
                                        @class(["is-invalid" => $errors->has("day")]) required>
                                        @foreach ($days as $dayCode => $dayLabel)
                                            <option value="{{ $dayCode }}"
                                                @selected(old("day") === $dayCode)>
                                                {{ $dayLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <i class="bi bi-chevron-down"></i>
                                </span>
                                @error("day")
                                    <small class="schedule-editor-feedback">{{ $message }}</small>
                                @enderror
                            </label>

                            <label class="schedule-editor-field">
                                <span>Jam mulai</span>
                                <span class="schedule-editor-control">
                                    <i class="bi bi-clock"></i>
                                    <input type="time" name="start_time" id="editorStartTime"
                                        value="{{ old("start_time") }}"
                                        @class(["is-invalid" => $errors->has("start_time")]) required>
                                </span>
                                @error("start_time")
                                    <small class="schedule-editor-feedback">{{ $message }}</small>
                                @enderror
                            </label>

                            <label class="schedule-editor-field">
                                <span>Jam selesai</span>
                                <span class="schedule-editor-control">
                                    <i class="bi bi-clock-history"></i>
                                    <input type="time" name="end_time" id="editorEndTime"
                                        value="{{ old("end_time") }}"
                                        @class(["is-invalid" => $errors->has("end_time")]) required>
                                </span>
                                @error("end_time")
                                    <small class="schedule-editor-feedback">{{ $message }}</small>
                                @enderror
                            </label>

                            <label class="schedule-editor-field full">
                                <span>Kuota pasien</span>
                                <span class="schedule-editor-control quota">
                                    <i class="bi bi-people"></i>
                                    <input type="number" name="quota" id="editorQuota"
                                        value="{{ old("quota") }}" min="0" max="9999" step="1"
                                        @class(["is-invalid" => $errors->has("quota")]) required>
                                    <em>pasien</em>
                                </span>
                                @error("quota")
                                    <small class="schedule-editor-feedback">{{ $message }}</small>
                                @enderror
                            </label>
                        </div>

                        <div class="schedule-editor-sync-note">
                            <i class="bi bi-broadcast"></i>
                            <span>
                                <strong>Perubahan berlaku langsung</strong>
                                Data yang disimpan otomatis digunakan pada menu Jadwal Dokter pasien.
                            </span>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <span class="schedule-editor-change-state" id="editorChangeState">
                            <i class="bi bi-check-circle"></i>
                            Belum ada perubahan
                        </span>
                        <button type="button" class="schedule-editor-cancel" data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="schedule-editor-save" id="editorSaveButton">
                            <i class="bi bi-check2-circle"></i>
                            <span>Simpan Perubahan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push("script")
    <script>
        (() => {
            const form = document.getElementById("scheduleEditorForm");
            const modalElement = document.getElementById("scheduleEditorModal");

            if (!form || !modalElement || typeof bootstrap === "undefined") {
                return;
            }

            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            const doctorName = document.getElementById("editorDoctorName");
            const clinicName = document.getElementById("editorClinicName");
            const originalDoctor = document.getElementById("originalDoctorCode");
            const originalDay = document.getElementById("originalDay");
            const originalStart = document.getElementById("originalStartTime");
            const day = document.getElementById("editorDay");
            const start = document.getElementById("editorStartTime");
            const end = document.getElementById("editorEndTime");
            const quota = document.getElementById("editorQuota");
            const saveButton = document.getElementById("editorSaveButton");
            const saveLabel = saveButton.querySelector("span");
            const changeState = document.getElementById("editorChangeState");
            const fields = [day, start, end, quota];
            let initialState = "";

            const state = () => fields.map((field) => field.value).join("|");

            const refreshState = () => {
                end.min = start.value;
                const changed = state() !== initialState;

                saveButton.disabled = !changed;
                changeState.classList.toggle("changed", changed);
                changeState.innerHTML = changed
                    ? '<i class="bi bi-exclamation-circle"></i> Perubahan siap disimpan'
                    : '<i class="bi bi-check-circle"></i> Belum ada perubahan';
            };

            const clearServerErrors = () => {
                form.querySelectorAll(".is-invalid").forEach((field) => {
                    field.classList.remove("is-invalid");
                });
                form.querySelectorAll(".schedule-editor-feedback, .schedule-editor-error")
                    .forEach((feedback) => feedback.remove());
            };

            const openEditor = (button, preserveValues = false) => {
                const data = button.dataset;

                doctorName.textContent = data.doctorName;
                clinicName.textContent = `${data.clinicName} · ${data.clinicCode}`;

                if (!preserveValues) {
                    clearServerErrors();
                    originalDoctor.value = data.doctorCode;
                    originalDay.value = data.day;
                    originalStart.value = data.startTime;
                    day.value = data.day;
                    start.value = data.startTime;
                    end.value = data.endTime;
                    quota.value = data.quota;
                }

                initialState = [
                    data.day,
                    data.startTime,
                    data.endTime,
                    data.quota,
                ].join("|");
                refreshState();

                if (preserveValues) {
                    saveButton.disabled = false;
                }

                modal.show();
            };

            document.querySelectorAll(".js-edit-schedule").forEach((button) => {
                button.addEventListener("click", () => openEditor(button));
            });

            fields.forEach((field) => {
                field.addEventListener("input", refreshState);
                field.addEventListener("change", refreshState);
            });

            form.addEventListener("submit", () => {
                saveButton.disabled = true;
                saveButton.classList.add("is-loading");
                saveLabel.textContent = "Menyimpan...";
            });

            if (form.dataset.reopen === "true") {
                const matchingButton = [...document.querySelectorAll(".js-edit-schedule")]
                    .find((button) => (
                        button.dataset.doctorCode === originalDoctor.value
                        && button.dataset.day === originalDay.value
                        && button.dataset.startTime === originalStart.value
                    ));

                if (matchingButton) {
                    openEditor(matchingButton, true);
                }
            }
        })();
    </script>
@endpush
