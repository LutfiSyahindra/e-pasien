@extends("template.epasien.appPasien")

@section("title", "Jadwal Dokter")

@push("style")
    <link href="{{ versioned_asset("epasien/assets/css/jadwal-dokter.css") }}"
        rel="stylesheet" />
@endpush

@section("content")
    @php
        $hasActiveFilters = request()->filled("q")
            || request()->filled("hari")
            || request()->filled("poli");
    @endphp

    <main class="doctor-schedule-page">
        <nav class="doctor-schedule-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route("dashboard") }}" aria-label="Kembali ke Dashboard">
                <i class="bi bi-house-door"></i>
            </a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span>Informasi</span>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span class="active">Jadwal Dokter</span>
        </nav>

        <section class="doctor-schedule-hero">
            <div class="doctor-schedule-hero-copy">
                <span class="doctor-schedule-eyebrow">
                    <i class="bi bi-calendar2-heart"></i>
                    Jadwal praktik dokter spesialis
                </span>
                <h1>Temukan Dokter yang Tepat</h1>
                <p>
                    Cek hari dan jam praktik dokter sebelum datang.
                    Pilih jadwal yang sesuai, lalu lanjutkan pendaftaran
                    secara online.
                </p>
                <div class="doctor-schedule-hero-actions">
                    <span>
                        <i class="bi bi-arrow-repeat"></i>
                        Data diperbarui saat halaman dibuka
                    </span>
                    <a href="{{ route("daftarOnline.index") }}">
                        <i class="bi bi-calendar2-plus"></i>
                        Daftar online
                    </a>
                </div>
            </div>

            <div class="doctor-schedule-hero-visual" aria-hidden="true">
                <span class="doctor-schedule-hero-orbit"></span>
                <span class="doctor-schedule-calendar">
                    <i class="bi bi-calendar2-week"></i>
                    <small>{{ $day_label }}</small>
                </span>
                <span class="doctor-schedule-hero-doctor">
                    <i class="bi bi-person-heart"></i>
                </span>
                <span class="doctor-schedule-hero-check">
                    <i class="bi bi-check-lg"></i>
                </span>
            </div>
        </section>

        @if ($connectionError)
            <div class="doctor-schedule-alert" role="alert">
                <i class="bi bi-cloud-slash"></i>
                <div>
                    <strong>Data belum dapat ditampilkan</strong>
                    <span>{{ $connectionError }}</span>
                </div>
            </div>
        @endif

        <section class="doctor-schedule-overview"
            aria-label="Ringkasan jadwal dokter">
            <div>
                <span><i class="bi bi-person-heart"></i></span>
                <div>
                    <small>Dokter spesialis</small>
                    <strong>{{ number_format($summary["doctors"], 0, ",", ".") }}</strong>
                </div>
            </div>
            <div>
                <span><i class="bi bi-hospital"></i></span>
                <div>
                    <small>Poliklinik</small>
                    <strong>{{ number_format($summary["clinics"], 0, ",", ".") }}</strong>
                </div>
            </div>
            <div>
                <span><i class="bi bi-calendar2-check"></i></span>
                <div>
                    <small>Jadwal praktik</small>
                    <strong>{{ number_format($summary["schedules"], 0, ",", ".") }}</strong>
                </div>
            </div>
        </section>

        <section class="doctor-schedule-content">
            <div class="doctor-schedule-content-heading">
                <div>
                    <span>Cari jadwal praktik</span>
                    <h2>Kapan Anda ingin berkunjung?</h2>
                </div>
                @if ($hasActiveFilters)
                    <a href="{{ route("jadwalDokter.index") }}">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset filter</span>
                    </a>
                @endif
            </div>

            <div class="doctor-schedule-day-section">
                <div class="doctor-schedule-day-heading">
                    <span><i class="bi bi-calendar3"></i> Pilih hari</span>
                    <small>Geser untuk melihat hari lainnya</small>
                </div>
                <div class="doctor-schedule-days" role="list"
                    aria-label="Filter berdasarkan hari praktik">
                    @foreach ($days as $dayCode => $dayOption)
                        <a href="{{ route("jadwalDokter.index", array_filter([
                            "q" => $search,
                            "hari" => $dayCode,
                            "poli" => $clinic_code,
                        ], fn ($value) => $value !== "")) }}"
                            @class(["active" => $day === $dayCode])
                            @if ($day === $dayCode) aria-current="true" @endif
                            role="listitem">
                            <span>{{ $dayOption["short"] }}</span>
                            <small>{{ $dayOption["label"] }}</small>
                        </a>
                    @endforeach
                </div>
            </div>

            <form action="{{ route("jadwalDokter.index") }}" method="GET"
                class="doctor-schedule-filters" id="doctorScheduleFilters">
                <input type="hidden" name="hari" value="{{ $day }}">

                <label class="doctor-schedule-search">
                    <span>Cari dokter atau poliklinik</span>
                    <span class="doctor-schedule-control">
                        <i class="bi bi-search"></i>
                        <input type="search" name="q"
                            value="{{ old("q", $search) }}"
                            placeholder="Contoh: dokter anak atau mata"
                            maxlength="80" autocomplete="off"
                            inputmode="search" enterkeyhint="search"
                            @disabled($connectionError)>
                    </span>
                    @error("q")
                        <small class="doctor-schedule-field-error">{{ $message }}</small>
                    @enderror
                </label>

                <label class="doctor-schedule-clinic">
                    <span>Poliklinik</span>
                    <span class="doctor-schedule-control">
                        <i class="bi bi-hospital"></i>
                        <select name="poli" id="doctorScheduleClinic"
                            @disabled($connectionError)>
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
                    @error("poli")
                        <small class="doctor-schedule-field-error">{{ $message }}</small>
                    @enderror
                </label>

                <button type="submit" @disabled($connectionError)>
                    <i class="bi bi-search"></i>
                    <span>Tampilkan jadwal</span>
                </button>
            </form>

            <div class="doctor-schedule-results-heading" aria-live="polite">
                <div>
                    <span>Jadwal {{ $day_label }}</span>
                    <h2>
                        {{ $search !== ""
                            ? "Hasil pencarian “".$search."”"
                            : "Dokter yang tersedia" }}
                    </h2>
                </div>
                <span>
                    {{ number_format($schedules->total(), 0, ",", ".") }} jadwal
                </span>
            </div>

            @if ($connectionError)
                <div class="doctor-schedule-empty">
                    <span><i class="bi bi-cloud-slash"></i></span>
                    <strong>Jadwal dokter belum tersedia</strong>
                    <p>Silakan coba muat ulang halaman beberapa saat lagi.</p>
                </div>
            @elseif ($schedules->count() === 0)
                <div class="doctor-schedule-empty">
                    <span><i class="bi bi-calendar2-x"></i></span>
                    <strong>
                        {{ $hasActiveFilters
                            ? "Jadwal tidak ditemukan"
                            : "Belum ada jadwal praktik" }}
                    </strong>
                    <p>
                        Coba pilih hari atau poliklinik lain, atau gunakan
                        kata kunci yang lebih singkat.
                    </p>
                    <a href="{{ route("jadwalDokter.index", ["hari" => "SEMUA"]) }}">
                        Lihat semua jadwal
                    </a>
                </div>
            @else
                <div class="doctor-schedule-grid">
                    @foreach ($schedules as $schedule)
                        <article class="doctor-schedule-card">
                            <div class="doctor-schedule-card-top">
                                <span @class([
                                    "doctor-schedule-day-badge",
                                    "is-today" => $schedule["is_today"],
                                ])>
                                    <i class="bi {{ $schedule["is_today"]
                                        ? "bi-circle-fill"
                                        : "bi-calendar3" }}"></i>
                                    {{ $schedule["day_label"] }}
                                    @if ($schedule["is_today"])
                                        <small>Hari ini</small>
                                    @endif
                                </span>
                                <span class="doctor-schedule-code">
                                    {{ $schedule["clinic_code"] }}
                                </span>
                            </div>

                            <div class="doctor-schedule-doctor">
                                @if (!empty($schedule["doctor_photo_url"] ?? null))
                                    <button type="button"
                                        class="doctor-schedule-avatar doctor-schedule-photo-trigger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#doctorPhotoModal"
                                        data-photo-url="{{ $schedule["doctor_photo_url"] }}"
                                        data-doctor-name="{{ $schedule["doctor_name"] }}"
                                        data-doctor-initials="{{ $schedule["doctor_initials"] }}"
                                        aria-label="Perbesar foto {{ $schedule["doctor_name"] }}">
                                        <span>{{ $schedule["doctor_initials"] }}</span>
                                        <img src="{{ $schedule["doctor_photo_url"] }}"
                                            alt="" width="60" height="60" loading="lazy"
                                            decoding="async"
                                            onerror="this.parentElement.disabled = true; this.remove()">
                                    </button>
                                @else
                                    <span class="doctor-schedule-avatar"
                                        aria-hidden="true">
                                        <span>{{ $schedule["doctor_initials"] }}</span>
                                    </span>
                                @endif
                                <div>
                                    <small>Dokter spesialis</small>
                                    <h3>{{ $schedule["doctor_name"] }}</h3>
                                    <span>
                                        <i class="bi bi-hospital"></i>
                                        {{ $schedule["clinic_name"] }}
                                    </span>
                                </div>
                            </div>

                            <div class="doctor-schedule-details">
                                <div>
                                    <span><i class="bi bi-clock"></i></span>
                                    <div>
                                        <small>Jam praktik</small>
                                        <strong>{{ $schedule["time_label"] }}</strong>
                                    </div>
                                </div>
                                <div>
                                    <span><i class="bi bi-people"></i></span>
                                    <div>
                                        <small>Kuota layanan</small>
                                        <strong>{{ $schedule["quota_label"] }}</strong>
                                    </div>
                                </div>
                            </div>

                            <a href="{{ route("daftarOnline.index") }}"
                                class="doctor-schedule-register">
                                <span>
                                    <small>Pilih jadwal ini?</small>
                                    Daftar secara online
                                </span>
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </article>
                    @endforeach
                </div>

                @if ($schedules->hasPages())
                    <nav class="doctor-schedule-pagination"
                        aria-label="Navigasi daftar jadwal dokter">
                        @if ($schedules->previousPageUrl())
                            <a href="{{ $schedules->previousPageUrl() }}" rel="prev">
                                <i class="bi bi-chevron-left"></i>
                                <span>Sebelumnya</span>
                            </a>
                        @else
                            <span class="disabled">
                                <i class="bi bi-chevron-left"></i>
                                <span>Sebelumnya</span>
                            </span>
                        @endif

                        <span class="doctor-schedule-page-count">
                            <small>Halaman</small>
                            <strong>
                                {{ $schedules->currentPage() }} / {{ $schedules->lastPage() }}
                            </strong>
                        </span>

                        @if ($schedules->nextPageUrl())
                            <a href="{{ $schedules->nextPageUrl() }}" rel="next">
                                <span>Berikutnya</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        @else
                            <span class="disabled">
                                <span>Berikutnya</span>
                                <i class="bi bi-chevron-right"></i>
                            </span>
                        @endif
                    </nav>
                @endif
            @endif
        </section>

        <aside class="doctor-schedule-disclaimer">
            <i class="bi bi-info-circle-fill"></i>
            <p>
                <strong>Perlu diketahui</strong>
                Jadwal dapat berubah sewaktu-waktu karena kondisi pelayanan.
                Lakukan pendaftaran online untuk memastikan ketersediaan
                antrean sebelum datang ke rumah sakit.
            </p>
        </aside>

        <div class="modal fade doctor-schedule-photo-modal" id="doctorPhotoModal"
            tabindex="-1" aria-labelledby="doctorPhotoModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <small>Foto dokter</small>
                            <h2 class="modal-title" id="doctorPhotoModalTitle">Dokter</h2>
                        </div>
                        <button type="button" class="btn-close"
                            data-bs-dismiss="modal" aria-label="Tutup foto"></button>
                    </div>
                    <div class="modal-body">
                        <div class="doctor-schedule-photo-preview">
                            <span id="doctorPhotoModalInitials" aria-hidden="true"></span>
                            <img id="doctorPhotoModalImage" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push("script")
    <script>
        (() => {
            const clinic = document.getElementById("doctorScheduleClinic");
            const photoModal = document.getElementById("doctorPhotoModal");
            const photoModalTitle = document.getElementById("doctorPhotoModalTitle");
            const photoModalInitials = document.getElementById("doctorPhotoModalInitials");
            const photoModalImage = document.getElementById("doctorPhotoModalImage");

            clinic?.addEventListener("change", () => {
                clinic.form?.requestSubmit();
            });

            photoModal?.addEventListener("show.bs.modal", (event) => {
                const trigger = event.relatedTarget;

                if (!(trigger instanceof HTMLElement)) {
                    return;
                }

                const doctorName = trigger.dataset.doctorName || "Dokter";

                photoModalTitle.textContent = doctorName;
                photoModalInitials.textContent = trigger.dataset.doctorInitials || "DR";
                photoModalImage.classList.remove("is-loaded");
                photoModalImage.src = trigger.dataset.photoUrl || "";
                photoModalImage.alt = `Foto ${doctorName}`;
            });

            photoModalImage?.addEventListener("load", () => {
                photoModalImage.classList.add("is-loaded");
            });

            photoModalImage?.addEventListener("error", () => {
                photoModalImage.classList.remove("is-loaded");
                photoModalImage.removeAttribute("src");
            });

            photoModal?.addEventListener("hidden.bs.modal", () => {
                photoModalImage.classList.remove("is-loaded");
                photoModalImage.removeAttribute("src");
                photoModalImage.alt = "";
            });
        })();
    </script>
@endpush
