@extends("template.epasien.appPasien")

@section("title", "Dashboard Admin | E-Pasien")

@push("style")
    <link href="{{ versioned_asset("epasien/assets/css/admin-dashboard.css") }}" rel="stylesheet">
@endpush

@section("content")
    @php
        $currentUser = auth()->user();
        $firstName = Str::of($currentUser->name)->trim()->explode(" ")->filter()->first() ?: "Admin";
        $roleLabel = $currentUser->getRoleNames()->implode(", ") ?: "Administrator";
        $clinicPeak = max(1, (int) $topClinics->max("total"));
    @endphp

    <div class="admin-dashboard-shell">
        <header class="admin-dashboard-hero" aria-labelledby="admin-dashboard-title">
            <div class="admin-dashboard-hero__copy">
                <span class="admin-dashboard-eyebrow">
                    <i class="bi bi-grid-1x2-fill" aria-hidden="true"></i>
                    Pusat kendali E-Pasien
                </span>
                <h1 id="admin-dashboard-title">Selamat datang, {{ $firstName }}</h1>
                <p>Pantau layanan digital rumah sakit dan tindak lanjuti pekerjaan penting dari satu tempat.</p>
                <div class="admin-dashboard-identity">
                    <span><i class="bi bi-shield-check"></i>{{ $roleLabel }}</span>
                    <span><i class="bi bi-calendar3"></i>{{ $todayLabel }}</span>
                </div>
            </div>

            <div class="admin-dashboard-hero__status" aria-label="Status operasional hari ini">
                <span class="admin-dashboard-live"><i></i> Sistem aktif</span>
                <strong>{{ str_pad((string) $summary["registrations_today"], 2, "0", STR_PAD_LEFT) }}</strong>
                <small>pendaftaran online hari ini</small>
                @can("EPASIEN.MENU.PENDAFTARAN_ONLINE")
                    <a href="{{ route("daftarOnline.index") }}">Buka pendaftaran <i class="bi bi-arrow-up-right"></i></a>
                @endcan
            </div>
        </header>

        <section class="admin-dashboard-stats" aria-label="Ringkasan operasional">
            <article class="is-indigo">
                <span class="admin-dashboard-stat__icon"><i class="bi bi-person-heart"></i></span>
                <div>
                    <small>Pasien terdaftar</small>
                    <strong>{{ number_format($summary["patient_users"], 0, ",", ".") }}</strong>
                    <em>{{ number_format($system["new_accounts_today"], 0, ",", ".") }} akun baru hari ini</em>
                </div>
                @can("EPASIEN.SETTINGS")
                    <a href="{{ route("users.users") }}" aria-label="Buka data pengguna"><i class="bi bi-arrow-up-right"></i></a>
                @endcan
            </article>

            <article class="is-blue">
                <span class="admin-dashboard-stat__icon"><i class="bi bi-calendar2-check"></i></span>
                <div>
                    <small>Pendaftaran hari ini</small>
                    <strong>{{ number_format($summary["registrations_today"], 0, ",", ".") }}</strong>
                    <em>{{ number_format($trendTotals["registrations"], 0, ",", ".") }} dalam 7 hari</em>
                </div>
                @can("EPASIEN.MENU.PENDAFTARAN_ONLINE")
                    <a href="{{ route("daftarOnline.index") }}" aria-label="Buka pendaftaran online"><i class="bi bi-arrow-up-right"></i></a>
                @endcan
            </article>

            <article class="is-emerald">
                <span class="admin-dashboard-stat__icon"><i class="bi bi-activity"></i></span>
                <div>
                    <small>Pengguna aktif hari ini</small>
                    <strong>{{ number_format($summary["active_users_today"], 0, ",", ".") }}</strong>
                    <em>{{ number_format($trendTotals["active_users"], 0, ",", ".") }} pengguna unik / 7 hari</em>
                </div>
                @can("EPASIEN.SETTINGS.USAGE_MONITORING")
                    <a href="{{ route("userAccessMonitoring.index") }}" aria-label="Buka pemantauan penggunaan"><i class="bi bi-arrow-up-right"></i></a>
                @endcan
            </article>

            <article class="is-orange">
                <span class="admin-dashboard-stat__icon"><i class="bi bi-chat-heart"></i></span>
                <div>
                    <small>Perlu ditanggapi</small>
                    <strong>{{ number_format($summary["waiting_tickets"], 0, ",", ".") }}</strong>
                    <em>Tiket Pasien Service menunggu admin</em>
                </div>
                @can("EPASIEN.MENU.PASIEN_SERVICE.KELOLA")
                    <a href="{{ route("patientService.index") }}" aria-label="Buka Pasien Service"><i class="bi bi-arrow-up-right"></i></a>
                @endcan
            </article>
        </section>

        <div class="admin-dashboard-main-grid">
            <section class="admin-dashboard-panel admin-dashboard-trend" aria-labelledby="admin-trend-title">
                <header class="admin-dashboard-panel__header">
                    <div>
                        <span class="admin-dashboard-kicker"><i class="bi bi-bar-chart-line"></i> Tren 7 hari</span>
                        <h2 id="admin-trend-title">Aktivitas layanan digital</h2>
                        <p>Perbandingan pendaftaran online dan pengguna aktif harian.</p>
                    </div>
                    <div class="admin-dashboard-legend" aria-hidden="true">
                        <span><i class="is-registration"></i>Pendaftaran</span>
                        <span><i class="is-active"></i>Pengguna aktif</span>
                    </div>
                </header>

                <div class="admin-dashboard-chart" role="img" aria-label="Grafik pendaftaran online dan pengguna aktif selama tujuh hari">
                    @foreach ($trend as $day)
                        @php
                            $registrationHeight = $day["registrations"] > 0
                                ? max(7, round(($day["registrations"] / $registrationPeak) * 100))
                                : 0;
                            $activeHeight = $day["active_users"] > 0
                                ? max(7, round(($day["active_users"] / $activeUserPeak) * 100))
                                : 0;
                        @endphp
                        <div class="admin-dashboard-chart__day" title="{{ $day["date_label"] }}: {{ $day["registrations"] }} pendaftaran, {{ $day["active_users"] }} pengguna aktif">
                            <div class="admin-dashboard-chart__bars">
                                <i class="is-registration" style="--bar-height: {{ $registrationHeight }}%"><span>{{ $day["registrations"] }}</span></i>
                                <i class="is-active" style="--bar-height: {{ $activeHeight }}%"><span>{{ $day["active_users"] }}</span></i>
                            </div>
                            <strong>{{ $day["label"] }}</strong>
                            <small>{{ $day["date_label"] }}</small>
                        </div>
                    @endforeach
                </div>
            </section>

            <aside class="admin-dashboard-panel admin-dashboard-health" aria-labelledby="admin-health-title">
                <header class="admin-dashboard-panel__header">
                    <div>
                        <span class="admin-dashboard-kicker"><i class="bi bi-cpu"></i> Ringkasan sistem</span>
                        <h2 id="admin-health-title">Kondisi layanan</h2>
                        <p>Indikator utama E-Pasien saat ini.</p>
                    </div>
                </header>
                <div class="admin-dashboard-health__list">
                    <div>
                        <span class="is-teal"><i class="bi bi-people"></i></span>
                        <p><strong>{{ number_format($system["active_accounts"], 0, ",", ".") }}</strong><small>Akun aktif</small></p>
                        <i class="bi bi-check-circle-fill is-ok" aria-label="Normal"></i>
                    </div>
                    <div>
                        <span class="is-violet"><i class="bi bi-phone"></i></span>
                        <p><strong>{{ number_format($system["pwa_installations"], 0, ",", ".") }}</strong><small>Instalasi PWA terdeteksi</small></p>
                        <i class="bi bi-check-circle-fill is-ok" aria-label="Normal"></i>
                    </div>
                    <div>
                        <span class="is-blue"><i class="bi bi-megaphone"></i></span>
                        <p><strong>{{ number_format($system["active_promotions"], 0, ",", ".") }}</strong><small>Konten sedang tayang</small></p>
                        <i class="bi bi-check-circle-fill is-ok" aria-label="Normal"></i>
                    </div>
                    <div>
                        <span class="{{ $system["bpjs_errors_today"] > 0 ? "is-red" : "is-green" }}"><i class="bi bi-hdd-network"></i></span>
                        <p><strong>{{ number_format($system["bpjs_errors_today"], 0, ",", ".") }}</strong><small>Gangguan BPJS hari ini</small></p>
                        <i class="bi {{ $system["bpjs_errors_today"] > 0 ? "bi-exclamation-circle-fill is-alert" : "bi-check-circle-fill is-ok" }}" aria-label="{{ $system["bpjs_errors_today"] > 0 ? "Perlu diperiksa" : "Normal" }}"></i>
                    </div>
                </div>
            </aside>
        </div>

        <div class="admin-dashboard-secondary-grid">
            <section class="admin-dashboard-panel admin-dashboard-registrations" aria-labelledby="admin-registration-title">
                <header class="admin-dashboard-panel__header">
                    <div>
                        <span class="admin-dashboard-kicker"><i class="bi bi-clock-history"></i> Aktivitas terbaru</span>
                        <h2 id="admin-registration-title">Pendaftaran online terbaru</h2>
                        <p>Kunjungan pasien yang terakhir dibuat melalui E-Pasien.</p>
                    </div>
                    @can("EPASIEN.MENU.PENDAFTARAN_ONLINE")
                        <a class="admin-dashboard-header-link" href="{{ route("daftarOnline.index") }}">Lihat pendaftaran <i class="bi bi-arrow-right"></i></a>
                    @endcan
                </header>

                <div class="admin-dashboard-table-wrap">
                    <table>
                        <thead>
                            <tr><th>Pasien</th><th>Tujuan poli</th><th>Jadwal</th><th>No. antrean</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($recentRegistrations as $registration)
                                <tr>
                                    <td>
                                        <span class="admin-dashboard-patient">
                                            <i>{{ Str::of($registration->patient_name)->trim()->explode(" ")->filter()->take(2)->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))->implode("") }}</i>
                                            <span><strong>{{ $registration->patient_name }}</strong><small>RM {{ $registration->patient_medical_record_number }}</small></span>
                                        </span>
                                    </td>
                                    <td><strong>{{ $registration->clinic_name }}</strong><small>{{ $registration->doctor_name }}</small></td>
                                    <td><strong>{{ $registration->registration_date->locale("id")->translatedFormat("d M Y") }}</strong><small>{{ $registration->registration_time ? Str::substr($registration->registration_time, 0, 5)." WIB" : "Jam belum tersedia" }}</small></td>
                                    <td><span class="admin-dashboard-queue-number">{{ $registration->no_reg }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><div class="admin-dashboard-empty"><i class="bi bi-calendar2-check"></i><strong>Belum ada pendaftaran online</strong><span>Data pendaftaran terbaru akan tampil di sini.</span></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="admin-dashboard-panel admin-dashboard-clinics" aria-labelledby="admin-clinic-title">
                <header class="admin-dashboard-panel__header">
                    <div>
                        <span class="admin-dashboard-kicker"><i class="bi bi-hospital"></i> Hari ini</span>
                        <h2 id="admin-clinic-title">Poli terpadat</h2>
                        <p>Tujuan pendaftaran online terbanyak.</p>
                    </div>
                </header>
                <div class="admin-dashboard-clinic-list">
                    @forelse ($topClinics as $clinic)
                        <div>
                            <span><strong>{{ $clinic->clinic_name }}</strong><em>{{ number_format($clinic->total, 0, ",", ".") }} pasien</em></span>
                            <i><span style="width: {{ max(7, round(($clinic->total / $clinicPeak) * 100)) }}%"></span></i>
                        </div>
                    @empty
                        <div class="admin-dashboard-empty is-compact"><i class="bi bi-hospital"></i><strong>Belum ada data poli</strong><span>Pendaftaran hari ini belum tersedia.</span></div>
                    @endforelse
                </div>
            </aside>
        </div>

        <div class="admin-dashboard-bottom-grid">
            <section class="admin-dashboard-panel admin-dashboard-service" aria-labelledby="admin-service-title">
                <header class="admin-dashboard-panel__header">
                    <div>
                        <span class="admin-dashboard-kicker"><i class="bi bi-chat-square-heart"></i> Pasien Service</span>
                        <h2 id="admin-service-title">Percakapan prioritas</h2>
                        <p>Dahulukan pesan yang masih menunggu tanggapan tim.</p>
                    </div>
                    @can("EPASIEN.MENU.PASIEN_SERVICE.KELOLA")
                        <a class="admin-dashboard-header-link" href="{{ route("patientService.index") }}">Buka inbox <i class="bi bi-arrow-right"></i></a>
                    @endcan
                </header>
                <div class="admin-dashboard-service__summary">
                    <span><strong>{{ $service["waiting_admin"] }}</strong>Menunggu admin</span>
                    <span><strong>{{ $service["waiting_patient"] }}</strong>Menunggu pasien</span>
                    <span><strong>{{ $service["closed_today"] }}</strong>Selesai hari ini</span>
                </div>
                <div class="admin-dashboard-service__list">
                    @forelse ($recentConversations as $conversation)
                        @php
                            $ticketTone = match ($conversation->status) {
                                \App\Models\PatientServiceConversation::STATUS_WAITING_ADMIN => "urgent",
                                \App\Models\PatientServiceConversation::STATUS_WAITING_PATIENT => "pending",
                                default => "resolved",
                            };
                        @endphp
                        <article>
                            <img src="{{ $conversation->patient?->profile_photo_url ?? asset("epasien/assets/images/avatars/avatar-patient-default.webp") }}" alt="" loading="lazy" decoding="async">
                            <div><strong>{{ $conversation->patient?->name ?? "Pasien" }}</strong><span>{{ $conversation->subject }}</span><small>{{ $conversation->category_label }} · {{ $conversation->last_message_at?->timezone("Asia/Jakarta")->locale("id")->diffForHumans() ?? "Belum ada pesan" }}</small></div>
                            <em class="is-{{ $ticketTone }}">{{ $conversation->status_label }}</em>
                        </article>
                    @empty
                        <div class="admin-dashboard-empty is-compact"><i class="bi bi-chat-heart"></i><strong>Inbox Pasien Service kosong</strong><span>Belum ada percakapan yang perlu ditampilkan.</span></div>
                    @endforelse
                </div>
            </section>

            <section class="admin-dashboard-panel admin-dashboard-users" aria-labelledby="admin-user-title">
                <header class="admin-dashboard-panel__header">
                    <div>
                        <span class="admin-dashboard-kicker"><i class="bi bi-person-plus"></i> Pengguna</span>
                        <h2 id="admin-user-title">Akun terbaru</h2>
                        <p>Pengguna yang baru bergabung di E-Pasien.</p>
                    </div>
                    @can("EPASIEN.SETTINGS")
                        <a class="admin-dashboard-header-link" href="{{ route("users.users") }}">Kelola pengguna <i class="bi bi-arrow-right"></i></a>
                    @endcan
                </header>
                <div class="admin-dashboard-user-list">
                    @forelse ($recentUsers as $user)
                        <article>
                            <img src="{{ $user->profile_photo_url }}" alt="" loading="lazy" decoding="async">
                            <div><strong>{{ $user->name }}</strong><span>{{ $user->username ?: $user->email }}</span></div>
                            <p><em>{{ $user->roles->pluck("name")->implode(", ") ?: "Belum ada peran" }}</em><small>{{ $user->created_at->timezone("Asia/Jakarta")->locale("id")->diffForHumans() }}</small></p>
                        </article>
                    @empty
                        <div class="admin-dashboard-empty is-compact"><i class="bi bi-people"></i><strong>Belum ada akun</strong><span>Akun terbaru akan muncul di sini.</span></div>
                    @endforelse
                </div>
            </section>
        </div>

        @canany(["EPASIEN.SETTINGS", "EPASIEN.MENU.PENDAFTARAN_ONLINE", "EPASIEN.MENU.PASIEN_SERVICE.KELOLA", "EPASIEN.MENU.PROMOSI.KELOLA"])
            <section class="admin-dashboard-panel admin-dashboard-actions" aria-labelledby="admin-actions-title">
                <header class="admin-dashboard-panel__header">
                    <div>
                        <span class="admin-dashboard-kicker"><i class="bi bi-lightning-charge"></i> Akses cepat</span>
                        <h2 id="admin-actions-title">Mulai pekerjaan admin</h2>
                    </div>
                </header>
                <div class="admin-dashboard-actions__grid">
                    @can("EPASIEN.SETTINGS")
                        <a href="{{ route("users.users") }}"><span class="is-indigo"><i class="bi bi-people"></i></span><strong>Kelola pengguna</strong><small>Akun, status, dan peran</small><i class="bi bi-arrow-right"></i></a>
                    @endcan
                    @can("EPASIEN.MENU.PENDAFTARAN_ONLINE")
                        <a href="{{ route("daftarOnline.index") }}"><span class="is-blue"><i class="bi bi-calendar2-plus"></i></span><strong>Pendaftaran online</strong><small>Daftarkan kunjungan pasien</small><i class="bi bi-arrow-right"></i></a>
                    @endcan
                    @can("EPASIEN.MENU.PASIEN_SERVICE.KELOLA")
                        <a href="{{ route("patientService.index") }}"><span class="is-orange"><i class="bi bi-chat-heart"></i></span><strong>Pasien Service</strong><small>Tanggapi pertanyaan pasien</small><i class="bi bi-arrow-right"></i></a>
                    @endcan
                    @can("EPASIEN.MENU.PROMOSI.KELOLA")
                        <a href="{{ route("promotions.create") }}"><span class="is-emerald"><i class="bi bi-megaphone"></i></span><strong>Buat informasi</strong><small>Terbitkan konten terbaru</small><i class="bi bi-arrow-right"></i></a>
                    @endcan
                </div>
            </section>
        @endcanany
    </div>
@endsection
