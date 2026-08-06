@extends("template.epasien.appPasien")

@section("title", "Dashboard Pasien | E-Pasien")

@push("style")
    <link href="{{ asset("epasien/assets/css/patient-dashboard.css") }}" rel="stylesheet">
@endpush

@section("content")
    @php
        $firstName = Str::of(auth()->user()->name)->trim()->explode(" ")->filter()->first() ?: "Sahabat";
        $featuredPromotion = $promotions->first();
        $otherPromotions = $promotions->skip(1);
        $registrationTone = in_array($upcomingRegistration["status_tone"] ?? "neutral", ["warning", "success", "danger", "info"], true)
            ? $upcomingRegistration["status_tone"]
            : "neutral";
    @endphp

    <div class="patient-dashboard-shell">
        <header class="patient-dashboard-welcome" aria-labelledby="dashboard-title">
            <div class="patient-dashboard-welcome__copy">
                <span class="patient-dashboard-eyebrow">
                    <i class="bi bi-heart-pulse-fill" aria-hidden="true"></i>
                    Beranda kesehatan Anda
                </span>
                <h1 id="dashboard-title">Halo, {{ $firstName }}!</h1>
                <p>Temukan kabar terbaru dan siapkan kunjungan Anda dengan lebih mudah.</p>
            </div>
            <div class="patient-dashboard-today" aria-label="Tanggal hari ini">
                <span class="patient-dashboard-today__icon"><i class="bi bi-calendar2-heart"></i></span>
                <span><small>Hari ini</small><strong>{{ $todayLabel }}</strong></span>
            </div>
        </header>

        <nav class="patient-dashboard-mobile-index" aria-label="Navigasi bagian dashboard">
            <a href="#dashboard-promotion-title"><i class="bi bi-megaphone-fill"></i><span>Info terbaru</span></a>
            <a href="#dashboard-visit-title"><i class="bi bi-calendar2-check-fill"></i><span>Antrean</span></a>
            <a href="#dashboard-schedule-title"><i class="bi bi-person-badge-fill"></i><span>Dokter hari ini</span></a>
        </nav>

        <div class="patient-dashboard-primary-grid">
            <section class="patient-dashboard-panel patient-dashboard-promotions" aria-labelledby="dashboard-promotion-title">
                <div class="patient-dashboard-heading">
                    <div>
                        <span class="patient-dashboard-heading__kicker">Pilihan untuk Anda</span>
                        <h2 id="dashboard-promotion-title">Promosi &amp; informasi terbaru</h2>
                    </div>
                    @can("EPASIEN.MENU.PROMOSI")
                        <a href="{{ route("promotions.index") }}">Lihat semua <i class="bi bi-arrow-right"></i></a>
                    @endcan
                </div>

                @if ($featuredPromotion)
                    <div class="patient-dashboard-promo-layout {{ $otherPromotions->isEmpty() ? "is-single" : "" }}">
                        @can("EPASIEN.MENU.PROMOSI")
                            <a class="patient-dashboard-promo-feature" href="{{ route("promotions.show", $featuredPromotion) }}">
                        @else
                            <article class="patient-dashboard-promo-feature">
                        @endcan
                                <img src="{{ $featuredPromotion->image_url }}" alt="{{ $featuredPromotion->title }}" decoding="async">
                                <span class="patient-dashboard-promo-feature__shade" aria-hidden="true"></span>
                                <div class="patient-dashboard-promo-feature__body">
                                    <span class="patient-dashboard-promo-category is-{{ $featuredPromotion->category }}">
                                        <i class="bi {{ $featuredPromotion->category_icon }}"></i>
                                        {{ $featuredPromotion->category_label }}
                                    </span>
                                    <h3>{{ $featuredPromotion->title }}</h3>
                                    <p>{{ Str::limit($featuredPromotion->caption, 120) }}</p>
                                    <span class="patient-dashboard-promo-feature__action">Baca selengkapnya <i class="bi bi-arrow-up-right"></i></span>
                                </div>
                        @can("EPASIEN.MENU.PROMOSI")
                            </a>
                        @else
                            </article>
                        @endcan

                        @if ($otherPromotions->isNotEmpty())
                            <span class="patient-dashboard-mobile-scroll-hint">
                                <i class="bi bi-arrow-left-right"></i> Geser untuk kabar lainnya
                            </span>
                            <div class="patient-dashboard-promo-list" aria-label="Informasi terbaru lainnya">
                                @foreach ($otherPromotions as $promotion)
                                    @can("EPASIEN.MENU.PROMOSI")
                                        <a class="patient-dashboard-promo-mini" href="{{ route("promotions.show", $promotion) }}">
                                    @else
                                        <article class="patient-dashboard-promo-mini">
                                    @endcan
                                            <img src="{{ $promotion->image_url }}" alt="" loading="lazy" decoding="async">
                                            <span class="patient-dashboard-promo-mini__body">
                                                <small><i class="bi {{ $promotion->category_icon }}"></i>{{ $promotion->category_label }}</small>
                                                <strong>{{ $promotion->title }}</strong>
                                                <span>{{ Str::limit($promotion->caption, 62) }}</span>
                                            </span>
                                            <i class="bi bi-chevron-right patient-dashboard-promo-mini__arrow" aria-hidden="true"></i>
                                    @can("EPASIEN.MENU.PROMOSI")
                                        </a>
                                    @else
                                        </article>
                                    @endcan
                                @endforeach
                            </div>
                        @endif
                    </div>
                @elseif ($sectionErrors["promotions"] ?? false)
                    <div class="patient-dashboard-state is-soft" role="status">
                        <span><i class="bi bi-cloud-slash"></i></span>
                        <div><h3>Informasi sedang diperbarui</h3><p>Silakan kembali beberapa saat lagi.</p></div>
                    </div>
                @else
                    <div class="patient-dashboard-state is-soft">
                        <span><i class="bi bi-megaphone"></i></span>
                        <div><h3>Belum ada kabar terbaru</h3><p>Promosi dan informasi rumah sakit akan hadir di sini.</p></div>
                    </div>
                @endif
            </section>

            <section class="patient-dashboard-panel patient-dashboard-visit" aria-labelledby="dashboard-visit-title">
                <div class="patient-dashboard-heading patient-dashboard-heading--compact">
                    <div>
                        <span class="patient-dashboard-heading__kicker">Agenda Anda</span>
                        <h2 id="dashboard-visit-title">Kunjungan terdekat</h2>
                    </div>
                    <span class="patient-dashboard-heading__icon"><i class="bi bi-calendar2-check-fill"></i></span>
                </div>

                @if ($upcomingRegistration)
                    <div class="patient-dashboard-visit__status is-{{ $registrationTone }}">
                        <i class="bi bi-circle-fill"></i>{{ $upcomingRegistration["status"] }}
                    </div>
                    <div class="patient-dashboard-visit__date">
                        <span class="patient-dashboard-date-card">
                            <small>{{ $upcomingRegistration["hari_short"] }}</small>
                            <strong>{{ $upcomingRegistration["tanggal_angka"] }}</strong>
                            <em>{{ $upcomingRegistration["bulan_short"] }}</em>
                        </span>
                        <span>
                            <small>Jadwal kunjungan</small>
                            <strong>{{ $upcomingRegistration["tanggal_lengkap"] }}</strong>
                            <em><i class="bi bi-clock"></i>{{ $upcomingRegistration["jam"] }} WIB</em>
                        </span>
                    </div>
                    <div class="patient-dashboard-visit__clinic">
                        <span><i class="bi bi-hospital"></i></span>
                        <div><small>Poliklinik</small><strong>{{ $upcomingRegistration["poli"] }}</strong></div>
                    </div>
                    <dl class="patient-dashboard-visit__details">
                        <div><dt>Dokter</dt><dd>{{ $upcomingRegistration["dokter"] }}</dd></div>
                        <div><dt>No. antrean</dt><dd class="is-queue">{{ $upcomingRegistration["no_reg"] }}</dd></div>
                        <div><dt>Penjamin</dt><dd>{{ $upcomingRegistration["penjamin"] }}</dd></div>
                    </dl>
                    @can("EPASIEN.MENU.PENDAFTARAN_ONLINE")
                        <a class="patient-dashboard-primary-button" href="{{ route("daftarOnline.history") }}">
                            Lihat detail kunjungan <i class="bi bi-arrow-right"></i>
                        </a>
                    @endcan
                @elseif ($sectionErrors["registration"] ?? false)
                    <div class="patient-dashboard-visit-empty">
                        <span><i class="bi bi-cloud-slash"></i></span>
                        <h3>Agenda belum dapat dimuat</h3>
                        <p>Data kunjungan sedang diperbarui. Silakan coba kembali nanti.</p>
                    </div>
                @else
                    <div class="patient-dashboard-visit-empty">
                        <span><i class="bi bi-calendar2-plus"></i></span>
                        <h3>Belum ada kunjungan</h3>
                        <p>Anda belum memiliki antrean atau jadwal kunjungan yang akan datang.</p>
                        @can("EPASIEN.MENU.PENDAFTARAN_ONLINE")
                            <a class="patient-dashboard-primary-button" href="{{ route("daftarOnline.index") }}">
                                <i class="bi bi-plus-lg"></i> Daftar online
                            </a>
                        @endcan
                    </div>
                @endif
            </section>
        </div>

        <section class="patient-dashboard-panel patient-dashboard-schedules" aria-labelledby="dashboard-schedule-title">
            <div class="patient-dashboard-heading">
                <div>
                    <span class="patient-dashboard-heading__kicker">Praktik hari ini</span>
                    <h2 id="dashboard-schedule-title">Jadwal dokter hari ini</h2>
                    <p>{{ $todayLabel }} · Jadwal dapat berubah sesuai kondisi pelayanan.</p>
                </div>
                @can("EPASIEN.MENU.JADWAL_DOKTER")
                    <a href="{{ route("jadwalDokter.index") }}">Jadwal lengkap <i class="bi bi-arrow-right"></i></a>
                @endcan
            </div>

            @if ($doctorSchedules->isNotEmpty())
                <span class="patient-dashboard-mobile-scroll-hint">
                    <i class="bi bi-arrow-left-right"></i> Geser untuk melihat dokter lainnya
                </span>
                <div class="patient-dashboard-doctor-grid">
                    @foreach ($doctorSchedules as $schedule)
                        <article class="patient-dashboard-doctor-card">
                            <div class="patient-dashboard-doctor-avatar">
                                @if ($schedule["doctor_photo_url"])
                                    <img src="{{ $schedule["doctor_photo_url"] }}" alt="Foto {{ $schedule["doctor_name"] }}" loading="lazy" decoding="async">
                                @else
                                    <span>{{ $schedule["doctor_initials"] }}</span>
                                @endif
                                <i class="bi bi-check-circle-fill" aria-label="Dokter aktif"></i>
                            </div>
                            <div class="patient-dashboard-doctor-card__body">
                                <span class="patient-dashboard-doctor-clinic">{{ $schedule["clinic_name"] }}</span>
                                <h3>{{ $schedule["doctor_name"] }}</h3>
                                <div class="patient-dashboard-doctor-time"><i class="bi bi-clock-fill"></i>{{ $schedule["time_label"] }}</div>
                                <div class="patient-dashboard-doctor-quota"><i class="bi bi-people"></i>Kuota {{ $schedule["quota_label"] }}</div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @elseif ($sectionErrors["schedules"] ?? false)
                <div class="patient-dashboard-state" role="status">
                    <span><i class="bi bi-cloud-slash"></i></span>
                    <div><h3>Jadwal belum dapat dimuat</h3><p>Koneksi jadwal dokter sedang diperbarui. Silakan coba kembali nanti.</p></div>
                </div>
            @else
                <div class="patient-dashboard-state">
                    <span><i class="bi bi-calendar2-x"></i></span>
                    <div><h3>Tidak ada jadwal praktik hari ini</h3><p>Lihat jadwal hari lain untuk menemukan dokter yang Anda butuhkan.</p></div>
                    @can("EPASIEN.MENU.JADWAL_DOKTER")
                        <a href="{{ route("jadwalDokter.index") }}">Lihat hari lainnya</a>
                    @endcan
                </div>
            @endif
        </section>

        <div class="patient-dashboard-secondary-grid">
            @canany(["EPASIEN.MENU.PENDAFTARAN_ONLINE", "EPASIEN.MENU.RIWAYAT_PEMERIKSAAN", "EPASIEN.MENU.SURAT", "EPASIEN.MENU.PASIEN_SERVICE"])
                <section class="patient-dashboard-panel patient-dashboard-shortcuts" aria-labelledby="dashboard-shortcut-title">
                    <div class="patient-dashboard-heading patient-dashboard-heading--compact">
                        <div><span class="patient-dashboard-heading__kicker">Tanpa antre lama</span><h2 id="dashboard-shortcut-title">Akses cepat</h2></div>
                    </div>
                    <div class="patient-dashboard-shortcut-grid">
                        @can("EPASIEN.MENU.PENDAFTARAN_ONLINE")
                            <a href="{{ route("daftarOnline.index") }}"><span class="is-teal"><i class="bi bi-calendar2-plus"></i></span><strong>Daftar online</strong><small>Buat kunjungan baru</small><i class="bi bi-arrow-right"></i></a>
                        @endcan
                        @can("EPASIEN.MENU.RIWAYAT_PEMERIKSAAN")
                            <a href="{{ route("riwayatPemeriksaan.index") }}"><span class="is-blue"><i class="bi bi-clipboard2-pulse"></i></span><strong>Riwayat pemeriksaan</strong><small>Lihat kunjungan Anda</small><i class="bi bi-arrow-right"></i></a>
                        @endcan
                        @can("EPASIEN.MENU.SURAT")
                            <a href="{{ route("suratKontrol.index") }}"><span class="is-amber"><i class="bi bi-file-earmark-medical"></i></span><strong>Surat kontrol</strong><small>Cek dokumen kontrol</small><i class="bi bi-arrow-right"></i></a>
                        @endcan
                        @can("EPASIEN.MENU.PASIEN_SERVICE")
                            <a href="{{ route("patientService.index") }}"><span class="is-violet"><i class="bi bi-chat-heart"></i></span><strong>Pasien Service</strong><small>Tanyakan kebutuhan Anda</small><i class="bi bi-arrow-right"></i></a>
                        @endcan
                    </div>
                </section>
            @endcanany

            <aside class="patient-dashboard-help" aria-labelledby="dashboard-help-title">
                <span class="patient-dashboard-help__icon"><i class="bi bi-headset"></i></span>
                <span class="patient-dashboard-heading__kicker">Kami siap membantu</span>
                <h2 id="dashboard-help-title">Ada yang ingin ditanyakan?</h2>
                <p>Tim kami siap membantu Anda memahami layanan dan persiapan kunjungan.</p>
                @can("EPASIEN.MENU.PASIEN_SERVICE")
                    <a href="{{ route("patientService.index") }}">Hubungi Pasien Service <i class="bi bi-arrow-right"></i></a>
                @else
                    <a href="{{ route("profile.edit") }}">Periksa data akun <i class="bi bi-arrow-right"></i></a>
                @endcan
            </aside>
        </div>
    </div>
@endsection
