@extends("template.landing.appLanding")

@section("content")
    <!-- banner area start -->
    <div class="banner-area-start">
        <div class="container-full-header">
            <div class="row">
                <div class="col-lg-12">
                    <div class="banner-area-one premium-hero rts-section-gap bg_image">
                        <div class="banner-content-area premium-hero__content">
                            <div class="pre-title premium-hero__eyebrow wow fadeInUp" data-wow-delay=".0s"
                                data-wow-duration=".8s">
                                <span class="premium-hero__eyebrow-icon" aria-hidden="true">
                                    <i class="fa-regular fa-hospital"></i>
                                </span>
                                <span>RS ABDURRAHMAN SYAMSURI</span>
                            </div>
                            <h1 class="title wow fadeInUp" data-wow-delay=".2s" data-wow-duration=".8s">
                                Kesehatan Anda,<br>
                                <span>Prioritas Kami.</span>
                            </h1>
                            <p class="disc wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".8s">
                                Pelayanan kesehatan yang Islami, bermanfaat, akurat, dan nyaman dengan tenaga medis
                                profesional yang siap mendampingi Anda dan keluarga.
                            </p>

                            <div class="premium-hero__actions wow fadeInUp" data-wow-delay=".55s"
                                data-wow-duration=".8s">
                                <a href="{{ route("login") }}" class="rts-btn btn-primary premium-hero__primary">
                                    Masuk ke E-Pasien
                                    <img src="{{ asset("landing/assets/images/banner/icons/arrow--up-right.svg") }}"
                                        alt="" aria-hidden="true">
                                </a>
                                <a href="{{ route("jadwalDokter.index") }}"
                                    class="rts-btn premium-hero__secondary">
                                    <i class="fa-regular fa-calendar-days" aria-hidden="true"></i>
                                    Jadwal Dokter
                                </a>
                            </div>

                            <div class="premium-hero__facts wow fadeInUp" data-wow-delay=".7s"
                                data-wow-duration=".8s" aria-label="Keunggulan layanan rumah sakit">
                                <div class="premium-hero__fact">
                                    <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                    <div>
                                        <strong>24 Jam</strong>
                                        <span>Layanan IGD</span>
                                    </div>
                                </div>
                                <div class="premium-hero__fact">
                                    <i class="fa-regular fa-user-doctor" aria-hidden="true"></i>
                                    <div>
                                        <strong>Profesional</strong>
                                        <span>Tenaga medis</span>
                                    </div>
                                </div>
                                <div class="premium-hero__fact">
                                    <i class="fa-regular fa-heart" aria-hidden="true"></i>
                                    <div>
                                        <strong>Nyaman</strong>
                                        <span>Sepenuh hati</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="premium-hero__emergency wow fadeInRight" data-wow-delay=".65s"
                            data-wow-duration=".8s">
                            <span class="premium-hero__emergency-icon" aria-hidden="true">
                                <i class="fa-solid fa-plus"></i>
                            </span>
                            <div>
                                <span>Siap melayani Anda</span>
                                <strong>IGD 24 Jam</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- banner area end -->

    <!-- PWA application area start -->
    <section id="aplikasi" class="landing-pwa landing-pwa--integrated" data-pwa-section
        aria-labelledby="landing-pwa-title">
        <div class="container landing-pwa__container">
            <div class="landing-pwa__surface">
                <div class="landing-pwa__layout">
                    <div class="landing-pwa__copy wow fadeInLeft" data-wow-duration=".8s">
                        <span class="landing-pwa__eyebrow">
                            <i class="fa-regular fa-mobile-screen-button" aria-hidden="true"></i>
                            Aplikasi pasien RS ARSY
                        </span>
                        <h2 id="landing-pwa-title">RS ARSY kini lebih dekat, <span>langsung dari layar utama.</span></h2>
                        <p>Pasang E-Pasien di ponsel atau komputer untuk membuka jadwal dokter, pendaftaran, antrean,
                            dan informasi layanan dengan lebih cepat.</p>

                        <ul class="landing-pwa__benefits" aria-label="Keunggulan aplikasi E-Pasien">
                            <li><i class="fa-solid fa-bolt" aria-hidden="true"></i><span><strong>Cepat</strong>Tanpa ketik alamat</span></li>
                            <li><i class="fa-solid fa-shield-check" aria-hidden="true"></i><span><strong>Aman</strong>Portal resmi RS ARSY</span></li>
                            <li><i class="fa-solid fa-download" aria-hidden="true"></i><span><strong>Praktis</strong>Tanpa Play Store</span></li>
                        </ul>

                        <div class="landing-pwa__actions">
                            <button type="button" class="landing-pwa__install" data-pwa-install>
                                <span class="landing-pwa__install-icon" aria-hidden="true">
                                    <i class="fa-solid fa-download"></i>
                                </span>
                                <span class="landing-pwa__install-copy">
                                    <small>Gratis &amp; ringan</small>
                                    <strong data-pwa-install-label>Install Aplikasi</strong>
                                </span>
                                <i class="fa-solid fa-arrow-right landing-pwa__install-arrow" aria-hidden="true"></i>
                            </button>
                            <p class="landing-pwa__status" data-pwa-status role="status" aria-live="polite">
                                Install langsung dari browser, tanpa perlu membuka Play Store.
                            </p>
                        </div>
                    </div>

                    <div class="landing-pwa__visual wow fadeInRight" data-wow-duration=".8s" aria-hidden="true">
                        <span class="landing-pwa__visual-orbit landing-pwa__visual-orbit--one"></span>
                        <span class="landing-pwa__visual-orbit landing-pwa__visual-orbit--two"></span>
                        <div class="landing-pwa__phone">
                            <div class="landing-pwa__phone-bar">
                                <span>09:41</span>
                                <span><i class="fa-solid fa-signal"></i><i class="fa-solid fa-wifi"></i><i class="fa-solid fa-battery-full"></i></span>
                            </div>
                            <div class="landing-pwa-mobile">
                                <header class="landing-pwa-mobile__header">
                                    <div class="landing-pwa-mobile__brand">
                                        <span><img src="{{ asset("epasien/assets/images/pwa-icon-192.png") }}" alt=""
                                                width="42" height="42"></span>
                                        <div><small>RS ARSY</small><strong>E-Pasien</strong></div>
                                    </div>
                                    <span class="landing-pwa-mobile__bell"><i class="fa-regular fa-bell"></i><em></em></span>
                                </header>

                                <div class="landing-pwa-mobile__welcome">
                                    <small>Selamat datang kembali</small>
                                    <strong>Halo, Sahabat Sehat!</strong>
                                    <span>Kelola kebutuhan kesehatan Anda dengan mudah.</span>
                                </div>

                                <section class="landing-pwa-mobile__visit">
                                    <div class="landing-pwa-mobile__visit-head">
                                        <span><small>Agenda Anda</small><strong>Kunjungan terdekat</strong></span>
                                        <em><i class="fa-solid fa-circle"></i> Terdaftar</em>
                                    </div>
                                    <div class="landing-pwa-mobile__visit-body">
                                        <span class="landing-pwa-mobile__date"><small>SEL</small><strong>24</strong><em>SEP</em></span>
                                        <span class="landing-pwa-mobile__clinic">
                                            <small>POLIKLINIK</small>
                                            <strong>Poli Penyakit Dalam</strong>
                                            <em><i class="fa-regular fa-clock"></i> 08.00 WIB</em>
                                        </span>
                                        <span class="landing-pwa-mobile__queue"><small>ANTREAN</small><strong>A-08</strong></span>
                                    </div>
                                </section>

                                <div class="landing-pwa-mobile__quick">
                                    <div><strong>Akses cepat</strong><small>Layanan favorit Anda</small></div>
                                    <ul>
                                        <li><span><i class="fa-regular fa-calendar-plus"></i></span><small>Daftar</small></li>
                                        <li><span><i class="fa-regular fa-user-doctor"></i></span><small>Dokter</small></li>
                                        <li><span><i class="fa-regular fa-file-medical"></i></span><small>Riwayat</small></li>
                                        <li><span><i class="fa-regular fa-comments"></i></span><small>Bantuan</small></li>
                                    </ul>
                                </div>

                                <nav class="landing-pwa-mobile__nav">
                                    <span class="is-active"><i class="fa-solid fa-house"></i><small>Beranda</small></span>
                                    <span><i class="fa-regular fa-calendar-check"></i><small>Jadwal</small></span>
                                    <span><i class="fa-regular fa-bell"></i><small>Notifikasi</small></span>
                                    <span><i class="fa-regular fa-user"></i><small>Profil</small></span>
                                </nav>
                            </div>
                        </div>
                        <div class="landing-pwa__badge">
                            <span><i class="fa-solid fa-check"></i></span>
                            <div>
                                <small>Cocok untuk perangkat Anda</small>
                                <strong>Android, iPhone &amp; komputer</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="landing-pwa__steps" data-pwa-manual-help tabindex="-1"
                    aria-label="Cara meng-install aplikasi E-Pasien">
                    <div class="landing-pwa__steps-heading">
                        <small>Cara memasang</small>
                        <strong>3 langkah mudah</strong>
                    </div>
                    <div class="landing-pwa__step">
                        <span>01</span>
                        <div><strong>Tekan tombol install</strong><small>Mulai dari tombol di atas atau toast.</small></div>
                    </div>
                    <div class="landing-pwa__step">
                        <span>02</span>
                        <div><strong>Ikuti petunjuk browser</strong><small>Di iPhone, pilih Tambahkan ke Layar Utama.</small></div>
                    </div>
                    <div class="landing-pwa__step">
                        <span>03</span>
                        <div><strong>Buka seperti aplikasi</strong><small>Ikon E-Pasien tampil di perangkat Anda.</small></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- PWA application area end -->

    <!-- patient login call-to-action start -->
    <section id="akses-epasien" class="patient-login-cta" aria-labelledby="patient-login-cta-title">
        <div class="container patient-login-cta__container">
            <div class="patient-login-cta__surface">
                <span class="patient-login-cta__glow patient-login-cta__glow--one" aria-hidden="true"></span>
                <span class="patient-login-cta__glow patient-login-cta__glow--two" aria-hidden="true"></span>

                <div class="patient-login-cta__content wow fadeInLeft" data-wow-duration=".8s">
                    <span class="patient-login-cta__eyebrow">
                        <i class="fa-solid fa-sparkles" aria-hidden="true"></i>
                        Layanan digital khusus pasien
                    </span>
                    <h2 id="patient-login-cta-title">
                        Seluruh layanan E-Pasien, <span>cukup satu kali login.</span>
                    </h2>
                    <p>
                        Masuk dengan akun pasien Anda untuk mengurus kebutuhan kesehatan dengan lebih cepat,
                        aman, dan praktis dari mana saja.
                    </p>

                    <ul class="patient-login-cta__services" aria-label="Layanan yang tersedia setelah login">
                        <li>
                            <span><i class="fa-regular fa-calendar-check" aria-hidden="true"></i></span>
                            <div><strong>Daftar online</strong><small>Buat kunjungan tanpa antre lama</small></div>
                        </li>
                        <li>
                            <span><i class="fa-regular fa-file-medical" aria-hidden="true"></i></span>
                            <div><strong>Riwayat kesehatan</strong><small>Lihat pemeriksaan dan hasil penunjang</small></div>
                        </li>
                        <li>
                            <span><i class="fa-regular fa-pills" aria-hidden="true"></i></span>
                            <div><strong>Resep &amp; surat</strong><small>Akses dokumen layanan pasien</small></div>
                        </li>
                    </ul>
                </div>

                <aside class="patient-login-cta__action wow fadeInRight" data-wow-duration=".8s"
                    aria-label="Akses akun E-Pasien">
                    <div class="patient-login-cta__action-top">
                        <span class="patient-login-cta__action-icon" aria-hidden="true">
                            <i class="fa-solid fa-user-shield"></i>
                        </span>
                        <span class="patient-login-cta__status"><i class="fa-solid fa-circle"></i> Portal pasien aman</span>
                    </div>

                    @auth
                        <span class="patient-login-cta__action-label">Akun Anda sudah aktif</span>
                        <h3>Lanjutkan ke layanan E-Pasien</h3>
                        <p>Buka dashboard untuk mengakses layanan dan informasi kesehatan Anda.</p>
                        <a href="{{ route("dashboard") }}" class="patient-login-cta__button">
                            <span>Buka E-Pasien</span>
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    @else
                        <span class="patient-login-cta__action-label">Sudah menjadi pasien RS ARSY?</span>
                        <h3>Masuk dan nikmati semua layanan</h3>
                        <p>Siapkan email atau nomor rekam medis Anda untuk melanjutkan.</p>
                        <a href="{{ route("login") }}" class="patient-login-cta__button">
                            <span>Masuk ke E-Pasien</span>
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    @endauth

                    <small class="patient-login-cta__privacy">
                        <i class="fa-solid fa-shield-check" aria-hidden="true"></i>
                        Akses pribadi untuk menjaga keamanan data kesehatan Anda.
                    </small>
                </aside>
            </div>
        </div>
    </section>
    <!-- patient login call-to-action end -->

    <!-- hospital services area start -->
    @php
        $hospitalServices = [
            ["name" => "Instalasi Gawat Darurat (IGD)", "category" => "Layanan 24 Jam", "icon" => "fa-truck-medical"],
            ["name" => "Kamar Operasi", "category" => "Tindakan Medis", "icon" => "fa-kit-medical"],
            ["name" => "Rawat Inap", "category" => "Perawatan", "icon" => "fa-bed-pulse"],
            ["name" => "Kamar Bersalin", "category" => "Ibu & Anak", "icon" => "fa-heart"],
            ["name" => "Rawat Jalan / Poli Spesialis", "category" => "Poli Spesialis", "icon" => "fa-user-doctor"],
            ["name" => "Radiologi", "category" => "Penunjang Medis", "icon" => "fa-x-ray"],
            ["name" => "Laboratorium", "category" => "Penunjang Medis", "icon" => "fa-flask"],
            ["name" => "Farmasi", "category" => "Obat & Konsultasi", "icon" => "fa-capsules"],
            ["name" => "Ambulance", "category" => "Layanan 24 Jam", "icon" => "fa-truck-medical"],
            ["name" => "Instalasi Gizi", "category" => "Nutrisi", "icon" => "fa-utensils"],
            ["name" => "Rehabilitasi Medik / Fisioterapi", "category" => "Pemulihan", "icon" => "fa-person-walking"],
            ["name" => "Persalinan Metode ERACS", "category" => "Ibu & Anak", "icon" => "fa-baby"],
        ];

        $otherServices = [
            [
                "name" => "Medical Check-Up",
                "description" => "Pemeriksaan kesehatan menyeluruh meliputi pemeriksaan umum, rontgen, dan laboratorium untuk individu, perusahaan, dan instansi.",
                "icon" => "fa-clipboard-check",
            ],
            [
                "name" => "Mitra Event Kesehatan",
                "description" => "Dukungan tenaga medis untuk berbagai event kesehatan seperti seminar, bakti sosial, pengobatan gratis, khitan massal, dan lainnya.",
                "icon" => "fa-users",
            ],
            [
                "name" => "Vaksinasi",
                "description" => "Layanan imunisasi anak dan dewasa sesuai kebutuhan.",
                "icon" => "fa-syringe",
            ],
            [
                "name" => "Nacehat",
                "description" => "Layanan antar obat langsung ke rumah dengan mudah dan cepat.",
                "icon" => "fa-motorcycle",
            ],
            [
                "name" => "Home Care & On Site",
                "description" => "Pelayanan medis di rumah maupun di lokasi sesuai kebutuhan pasien.",
                "icon" => "fa-house",
            ],
            [
                "name" => "USG 4D",
                "description" => "Visualisasi janin yang lebih nyata untuk membantu memantau tumbuh kembangnya.",
                "icon" => "fa-baby",
            ],
        ];

        $specialistClinics = [
            [
                "name" => "Poli Anak",
                "focus" => "Kesehatan Anak",
                "description" => "Pendampingan kesehatan, pertumbuhan, dan perkembangan anak sejak dini.",
                "icon" => "fa-child-reaching",
                "custom_icon" => "child",
                "group" => "family",
            ],
            [
                "name" => "Poli Bedah",
                "focus" => "Konsultasi Bedah",
                "description" => "Pemeriksaan dan konsultasi untuk kondisi yang membutuhkan tindakan bedah.",
                "icon" => "fa-bandage",
                "group" => "surgery",
            ],
            [
                "name" => "Poli Kandungan",
                "focus" => "Ibu & Kehamilan",
                "description" => "Layanan kesehatan reproduksi, kehamilan, dan persiapan persalinan.",
                "icon" => "fa-person-pregnant",
                "custom_icon" => "maternity",
                "group" => "family",
            ],
            [
                "name" => "Poli Kulit & Kelamin",
                "focus" => "Kulit & Kelamin",
                "description" => "Diagnosis dan perawatan berbagai keluhan kesehatan kulit dan kelamin.",
                "icon" => "fa-hand-dots",
                "group" => "sensory",
            ],
            [
                "name" => "Poli Mata",
                "focus" => "Kesehatan Mata",
                "description" => "Pemeriksaan penglihatan dan penanganan gangguan kesehatan mata.",
                "icon" => "fa-eye",
                "group" => "sensory",
            ],
            [
                "name" => "Poli Orthopedi",
                "focus" => "Tulang & Sendi",
                "description" => "Penanganan keluhan pada tulang, sendi, otot, dan sistem gerak.",
                "icon" => "fa-bone",
                "group" => "surgery",
            ],
            [
                "name" => "Poli Paru",
                "focus" => "Pernapasan",
                "description" => "Pemeriksaan dan perawatan gangguan paru serta sistem pernapasan.",
                "icon" => "fa-lungs",
                "group" => "internal",
            ],
            [
                "name" => "Poli Penyakit Dalam",
                "focus" => "Penyakit Dalam",
                "description" => "Konsultasi menyeluruh untuk berbagai keluhan kesehatan organ dalam.",
                "icon" => "fa-stethoscope",
                "group" => "internal",
            ],
            [
                "name" => "Poli Rehab Medis",
                "focus" => "Pemulihan Fungsi",
                "description" => "Program pemulihan untuk membantu fungsi gerak dan aktivitas sehari-hari.",
                "icon" => "fa-person-walking",
                "group" => "surgery",
            ],
            [
                "name" => "Poli Syaraf",
                "focus" => "Sistem Saraf",
                "description" => "Penanganan keluhan yang berkaitan dengan otak, saraf, dan tulang belakang.",
                "icon" => "fa-brain",
                "group" => "sensory",
            ],
            [
                "name" => "Poli THT",
                "focus" => "Telinga, Hidung & Tenggorokan",
                "description" => "Pemeriksaan kesehatan telinga, hidung, tenggorokan, kepala, dan leher.",
                "icon" => "fa-ear-listen",
                "group" => "sensory",
            ],
            [
                "name" => "Poli Urologi",
                "focus" => "Saluran Kemih",
                "description" => "Konsultasi gangguan saluran kemih serta sistem reproduksi pria.",
                "icon" => "fa-droplet",
                "group" => "internal",
            ],
        ];
    @endphp

    <section id="layanan" class="hospital-services" aria-labelledby="hospital-services-title">
        <span class="hospital-services__decoration hospital-services__decoration--one" aria-hidden="true"></span>
        <span class="hospital-services__decoration hospital-services__decoration--two" aria-hidden="true"></span>

        <div class="container hospital-services__container">
            <div class="hospital-services__intro">
                <header class="hospital-services__header wow fadeInLeft" data-wow-duration=".8s">
                    <span class="hospital-services__eyebrow">
                        <i class="fa-solid fa-heart-pulse" aria-hidden="true"></i>
                        Layanan Kami
                    </span>
                    <h2 id="hospital-services-title">Pelayanan lengkap, <span>untuk setiap langkah.</span></h2>
                    <p>Dari kondisi darurat hingga pemulihan, tim kami siap mendampingi Anda dan keluarga melalui
                        pelayanan kesehatan yang terintegrasi.</p>

                    <div class="hospital-services__intro-footer">
                        <div class="hospital-services__summary" aria-label="Ringkasan layanan">
                            <span><strong>{{ count($hospitalServices) }}</strong> layanan rumah sakit</span>
                            <span><strong>{{ count($otherServices) }}</strong> layanan lainnya</span>
                        </div>
                        <a href="#daftar-layanan" class="hospital-services__explore">
                            Jelajahi layanan
                            <i class="fa-solid fa-arrow-down" aria-hidden="true"></i>
                        </a>
                    </div>
                </header>

                <div class="hospital-services__visual wow fadeInRight" data-wow-duration=".8s">
                    <img src="{{ asset("landing/assets/imagesArsy/rs.jpeg") }}"
                        alt="Gedung RS Abdurrahman Syamsuri dengan layanan IGD 24 jam">
                    <span class="hospital-services__visual-shade" aria-hidden="true"></span>

                    <div class="hospital-services__visual-brand">
                        <span class="hospital-services__visual-brand-icon" aria-hidden="true">
                            <i class="fa-solid fa-hospital"></i>
                        </span>
                        <div>
                            <small>Pelayanan terpadu</small>
                            <strong>RS ARSY</strong>
                        </div>
                    </div>

                    <div class="hospital-services__visual-status">
                        <span class="hospital-services__visual-pulse" aria-hidden="true"></span>
                        <div>
                            <small>Siap melayani</small>
                            <strong>IGD 24 Jam</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div id="daftar-layanan" class="hospital-services__list-heading wow fadeInUp" data-wow-duration=".8s">
                <div>
                    <span>Pelayanan utama</span>
                    <h2>Semua kebutuhan kesehatan dalam satu tempat</h2>
                </div>
                <p><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Ditangani oleh tenaga medis
                    profesional dan berpengalaman.</p>
            </div>

            <ul class="hospital-services__grid" aria-label="Daftar pelayanan rumah sakit">
                @foreach ($hospitalServices as $index => $service)
                    <li class="hospital-service-card wow fadeInUp" data-wow-duration=".8s">
                        <span class="hospital-service-card__number">{{ str_pad($index + 1, 2, "0", STR_PAD_LEFT) }}</span>
                        <span class="hospital-service-card__icon" aria-hidden="true">
                            <i class="fa-solid {{ $service["icon"] }}"></i>
                        </span>
                        <span class="hospital-service-card__category">{{ $service["category"] }}</span>
                        <h3>{{ $service["name"] }}</h3>
                        <span class="hospital-service-card__plus" aria-hidden="true">
                            <i class="fa-solid fa-plus"></i>
                        </span>
                    </li>
                @endforeach
            </ul>

            <div class="hospital-services__other">
                <div class="hospital-services__other-heading wow fadeInUp" data-wow-duration=".8s">
                    <span>Layanan Lain</span>
                    <h2>Lebih dekat, lebih lengkap</h2>
                    <p>Solusi kesehatan tambahan yang dapat disesuaikan dengan kebutuhan personal, keluarga,
                        perusahaan, maupun instansi.</p>
                </div>

                <div class="hospital-services__other-grid">
                    @foreach ($otherServices as $index => $service)
                        <article class="other-service-card wow fadeInUp" data-wow-duration=".8s">
                            <span class="other-service-card__icon" aria-hidden="true">
                                <i class="fa-solid {{ $service["icon"] }}"></i>
                            </span>
                            <div class="other-service-card__content">
                                <span class="other-service-card__number">0{{ $index + 1 }}</span>
                                <h3>{{ $service["name"] }}</h3>
                                <p>{{ $service["description"] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="hospital-services__cta wow fadeInUp" data-wow-duration=".8s">
                    <div class="hospital-services__cta-copy">
                        <span class="hospital-services__cta-icon" aria-hidden="true">
                            <i class="fa-solid fa-headset"></i>
                        </span>
                        <div>
                            <small>Akses pelayanan lebih mudah</small>
                            <h3>Siap merencanakan kunjungan Anda?</h3>
                        </div>
                    </div>
                    <div class="hospital-services__cta-actions">
                        <a href="{{ route("jadwalDokter.index") }}" class="hospital-services__cta-secondary">
                            <i class="fa-regular fa-calendar-days" aria-hidden="true"></i>
                            Jadwal Dokter
                        </a>
                        <a href="/login" class="hospital-services__cta-primary">
                            Buat Janji Temu
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- hospital services area end -->

    <!-- specialist clinics area start -->
    <section id="poli-spesialis" class="specialist-clinics" aria-labelledby="specialist-clinics-title">
        <span class="specialist-clinics__orb specialist-clinics__orb--one" aria-hidden="true"></span>
        <span class="specialist-clinics__orb specialist-clinics__orb--two" aria-hidden="true"></span>

        <div class="container specialist-clinics__container">
            <header class="specialist-clinics__header">
                <div class="specialist-clinics__heading wow fadeInLeft" data-wow-duration=".8s">
                    <span class="specialist-clinics__eyebrow">
                        <i class="fa-solid fa-user-doctor" aria-hidden="true"></i>
                        Poli Spesialis RS ARSY
                    </span>
                    <h2 id="specialist-clinics-title">Perawatan tepat bersama <span>dokter yang tepat.</span></h2>
                </div>

                <div class="specialist-clinics__lead wow fadeInRight" data-wow-duration=".8s">
                    <p>Pilih layanan spesialis sesuai kebutuhan Anda. Setiap poli didukung tenaga medis profesional
                        untuk memberikan pemeriksaan dan rencana perawatan yang menyeluruh.</p>
                    <div class="specialist-clinics__meta" aria-label="Ringkasan poli spesialis">
                        <span class="specialist-clinics__count">{{ count($specialistClinics) }}</span>
                        <span>pilihan poli<br>spesialis</span>
                    </div>
                </div>
            </header>

            <div class="specialist-clinics__assurance wow fadeInUp" data-wow-duration=".8s"
                aria-label="Kemudahan pelayanan poli spesialis">
                <div>
                    <span class="specialist-clinics__assurance-icon" aria-hidden="true">
                        <i class="fa-solid fa-user-doctor"></i>
                    </span>
                    <span><strong>Dokter spesialis</strong>Berpengalaman di bidangnya</span>
                </div>
                <div>
                    <span class="specialist-clinics__assurance-icon" aria-hidden="true">
                        <i class="fa-regular fa-calendar-check"></i>
                    </span>
                    <span><strong>Jadwal terintegrasi</strong>Mudah dilihat secara online</span>
                </div>
                <div>
                    <span class="specialist-clinics__assurance-icon" aria-hidden="true">
                        <i class="fa-solid fa-mobile-screen-button"></i>
                    </span>
                    <span><strong>Pendaftaran praktis</strong>Daftar kunjungan dari mana saja</span>
                </div>
            </div>

            <div class="specialist-clinics__toolbar wow fadeInUp" data-wow-duration=".8s">
                <div class="specialist-clinics__filters" aria-label="Filter kategori poli spesialis">
                    <button type="button" class="specialist-clinics__filter is-active" data-specialist-filter="all"
                        aria-pressed="true">
                        Semua Poli <span>12</span>
                    </button>
                    <button type="button" class="specialist-clinics__filter" data-specialist-filter="family"
                        aria-pressed="false">
                        Ibu & Anak <span>2</span>
                    </button>
                    <button type="button" class="specialist-clinics__filter" data-specialist-filter="surgery"
                        aria-pressed="false">
                        Bedah & Rehabilitasi <span>3</span>
                    </button>
                    <button type="button" class="specialist-clinics__filter" data-specialist-filter="internal"
                        aria-pressed="false">
                        Organ Dalam <span>3</span>
                    </button>
                    <button type="button" class="specialist-clinics__filter" data-specialist-filter="sensory"
                        aria-pressed="false">
                        Indra & Saraf <span>4</span>
                    </button>
                </div>

                <label class="specialist-clinics__search" for="specialist-clinic-search">
                    <i class="fa-regular fa-magnifying-glass" aria-hidden="true"></i>
                    <span class="visually-hidden">Cari poli spesialis</span>
                    <input id="specialist-clinic-search" type="search" placeholder="Cari nama poli..."
                        autocomplete="off" data-specialist-search>
                    <button type="button" class="specialist-clinics__search-clear" data-specialist-search-clear
                        aria-label="Hapus pencarian" hidden>
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </label>
            </div>

            <p class="specialist-clinics__result" data-specialist-result aria-live="polite">
                Menampilkan seluruh {{ count($specialistClinics) }} poli spesialis
            </p>

            <div class="specialist-clinics__grid" aria-label="Daftar poli spesialis">
                @foreach ($specialistClinics as $index => $clinic)
                    <a href="{{ route("jadwalDokter.index") }}" class="specialist-clinic-card wow fadeInUp"
                        data-wow-delay="{{ number_format(($index % 4) * 0.08, 2) }}s" data-wow-duration=".8s"
                        data-specialist-card data-group="{{ $clinic["group"] }}"
                        data-search="{{ Str::lower($clinic["name"] . " " . $clinic["focus"] . " " . $clinic["description"]) }}"
                        aria-label="Lihat jadwal {{ $clinic["name"] }}">
                        <span class="specialist-clinic-card__number" aria-hidden="true">
                            {{ str_pad($index + 1, 2, "0", STR_PAD_LEFT) }}
                        </span>
                        <span class="specialist-clinic-card__icon" aria-hidden="true">
                            @if (($clinic["custom_icon"] ?? null) === "child")
                                <svg class="specialist-clinic-card__svg" viewBox="0 0 64 64" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M27 15c0-5 4-8 9-8 4 0 7 2 8 5-1 4-4 7-8 7"
                                        stroke="currentColor" stroke-width="3.5" stroke-linecap="round" />
                                    <path d="M16 32c0-10 7-17 16-17s16 7 16 17v4c0 10-7 18-16 18s-16-8-16-18v-4Z"
                                        stroke="currentColor" stroke-width="3.5" />
                                    <path d="M16 31c-4 0-6 2-6 5s2 6 6 6m32-11c4 0 6 2 6 5s-2 6-6 6"
                                        stroke="currentColor" stroke-width="3.5" stroke-linecap="round" />
                                    <circle cx="26" cy="34" r="2" fill="currentColor" />
                                    <circle cx="38" cy="34" r="2" fill="currentColor" />
                                    <path d="M26 43c2 2 4 3 6 3s4-1 6-3" stroke="currentColor" stroke-width="3"
                                        stroke-linecap="round" />
                                </svg>
                            @elseif (($clinic["custom_icon"] ?? null) === "maternity")
                                <svg class="specialist-clinic-card__svg" viewBox="0 0 64 64" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="25" cy="12" r="6" stroke="currentColor" stroke-width="3.5" />
                                    <path d="M25 19v11m0 0c10 0 17 6 17 16H24c-5-5-6-13-3-21l2-5"
                                        stroke="currentColor" stroke-width="3.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <path d="M25 46l-2 11m14-11 3 11M19 29h12" stroke="currentColor"
                                        stroke-width="3.5" stroke-linecap="round" />
                                    <path d="M35 34c2 1 3 3 3 5" stroke="currentColor" stroke-width="3"
                                        stroke-linecap="round" />
                                </svg>
                            @else
                                <i class="fa-solid {{ $clinic["icon"] }}"></i>
                            @endif
                        </span>
                        <span class="specialist-clinic-card__focus">{{ $clinic["focus"] }}</span>
                        <h3>{{ $clinic["name"] }}</h3>
                        <p>{{ $clinic["description"] }}</p>
                        <span class="specialist-clinic-card__link">
                            Lihat jadwal
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </span>
                    </a>
                @endforeach
            </div>

            <p class="specialist-clinics__mobile-hint" aria-hidden="true">
                <i class="fa-regular fa-arrows-left-right"></i>
                Geser untuk melihat poli spesialis lainnya
            </p>

            <div class="specialist-clinics__empty" data-specialist-empty hidden>
                <span aria-hidden="true"><i class="fa-regular fa-magnifying-glass"></i></span>
                <h3>Poli belum ditemukan</h3>
                <p>Coba gunakan kata kunci lain atau pilih kategori “Semua Poli”.</p>
                <button type="button" data-specialist-reset>Tampilkan Semua Poli</button>
            </div>

            <div class="specialist-clinics__cta wow fadeInUp" data-wow-duration=".8s">
                <div class="specialist-clinics__cta-copy">
                    <span class="specialist-clinics__cta-icon" aria-hidden="true">
                        <i class="fa-solid fa-heart-pulse"></i>
                    </span>
                    <div>
                        <small>RS Abdurrahman Syamsuri</small>
                        <h3>Belum yakin harus memilih poli yang mana?</h3>
                        <p>Lihat jadwal dokter atau mulai pendaftaran untuk merencanakan kunjungan Anda.</p>
                    </div>
                </div>
                <div class="specialist-clinics__cta-actions">
                    <a href="{{ route("jadwalDokter.index") }}" class="specialist-clinics__schedule">
                        <i class="fa-regular fa-calendar-days" aria-hidden="true"></i>
                        Jadwal Dokter
                    </a>
                    <a href="/login" class="specialist-clinics__appointment">
                        Daftar Sekarang
                        <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>
    <!-- specialist clinics area end -->

    @push("script")
        <script>
            (() => {
                const section = document.querySelector(".specialist-clinics");

                if (!section) return;

                const cards = [...section.querySelectorAll("[data-specialist-card]")];
                const filters = [...section.querySelectorAll("[data-specialist-filter]")];
                const search = section.querySelector("[data-specialist-search]");
                const clearSearch = section.querySelector("[data-specialist-search-clear]");
                const result = section.querySelector("[data-specialist-result]");
                const empty = section.querySelector("[data-specialist-empty]");
                const reset = section.querySelector("[data-specialist-reset]");
                const grid = section.querySelector(".specialist-clinics__grid");
                let activeFilter = "all";

                const normalize = (value) => value.toLocaleLowerCase("id-ID").trim();

                const updateCards = () => {
                    const keyword = normalize(search.value);
                    let visibleCount = 0;

                    cards.forEach((card) => {
                        const matchesFilter = activeFilter === "all" || card.dataset.group === activeFilter;
                        const matchesSearch = !keyword || normalize(card.dataset.search).includes(keyword);
                        const isVisible = matchesFilter && matchesSearch;

                        card.hidden = !isVisible;
                        card.setAttribute("aria-hidden", String(!isVisible));
                        if (isVisible) visibleCount += 1;
                    });

                    clearSearch.hidden = keyword.length === 0;
                    empty.hidden = visibleCount !== 0;
                    result.textContent = visibleCount === cards.length && !keyword
                        ? `Menampilkan seluruh ${cards.length} poli spesialis`
                        : `${visibleCount} poli ditemukan`;
                };

                filters.forEach((filter) => {
                    filter.addEventListener("click", () => {
                        activeFilter = filter.dataset.specialistFilter;
                        filters.forEach((item) => {
                            const isActive = item === filter;
                            item.classList.toggle("is-active", isActive);
                            item.setAttribute("aria-pressed", String(isActive));
                        });
                        updateCards();
                        if (window.matchMedia("(max-width: 575px)").matches) grid.scrollLeft = 0;
                    });
                });

                search.addEventListener("input", updateCards);
                search.addEventListener("keydown", (event) => {
                    if (event.key === "Escape" && search.value) {
                        search.value = "";
                        updateCards();
                    }
                });

                clearSearch.addEventListener("click", () => {
                    search.value = "";
                    search.focus();
                    updateCards();
                });

                reset.addEventListener("click", () => {
                    activeFilter = "all";
                    search.value = "";
                    filters.forEach((item, index) => {
                        item.classList.toggle("is-active", index === 0);
                        item.setAttribute("aria-pressed", String(index === 0));
                    });
                    updateCards();
                    search.focus();
                });
            })();
        </script>
    @endpush

    <!-- doctor schedule area start -->
    <section id="jadwal-praktik" class="landing-doctor-schedule" aria-labelledby="landing-doctor-schedule-title">
        <span class="landing-doctor-schedule__shape landing-doctor-schedule__shape--one" aria-hidden="true"></span>
        <span class="landing-doctor-schedule__shape landing-doctor-schedule__shape--two" aria-hidden="true"></span>

        <div class="container landing-doctor-schedule__container">
            <header class="landing-doctor-schedule__header">
                <div class="landing-doctor-schedule__heading wow fadeInLeft" data-wow-duration=".8s">
                    <span class="landing-doctor-schedule__eyebrow">
                        <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
                        Jadwal Praktik
                    </span>
                    <h2 id="landing-doctor-schedule-title">Temukan dokter yang <span>praktik hari ini.</span></h2>
                    <p>Lihat jadwal dokter dan jam pelayanan sebelum merencanakan kunjungan Anda ke RS Abdurrahman
                        Syamsuri.</p>
                </div>

                <div class="landing-doctor-schedule__summary wow fadeInRight" data-wow-duration=".8s">
                    <span class="landing-doctor-schedule__calendar" aria-hidden="true">
                        <i class="fa-regular fa-calendar-days"></i>
                    </span>
                    <div>
                        <small>Jadwal hari ini</small>
                        <strong>{{ $landingScheduleDate }}</strong>
                        <span>{{ $landingSchedules->count() }} jadwal ditampilkan</span>
                    </div>
                </div>
            </header>

            <nav class="landing-doctor-schedule__week wow fadeInUp" data-wow-duration=".8s"
                aria-label="Pilih hari jadwal praktik">
                <div class="landing-doctor-schedule__week-label">
                    <span aria-hidden="true"><i class="fa-regular fa-calendar-week"></i></span>
                    <div>
                        <small>{{ $landingScheduleMonth }}</small>
                        <strong>Pilih hari kunjungan</strong>
                    </div>
                </div>
                <div class="landing-doctor-schedule__days">
                    @foreach ($landingScheduleDays as $scheduleDay)
                        <a href="{{ route("jadwalDokter.index", ["hari" => $scheduleDay["key"]]) }}"
                            class="landing-doctor-schedule__day {{ $scheduleDay["is_today"] ? "is-today" : "" }}"
                            aria-label="Lihat jadwal hari {{ $scheduleDay["full_label"] }}"
                            @if ($scheduleDay["is_today"]) aria-current="date" @endif>
                            <small>{{ $scheduleDay["label"] }}</small>
                            <strong>{{ $scheduleDay["date"] }}</strong>
                            <span>{{ $scheduleDay["is_today"] ? "Hari ini" : $scheduleDay["month"] }}</span>
                        </a>
                    @endforeach
                </div>
            </nav>

            <div class="landing-doctor-schedule__mobile-hint" aria-hidden="true">
                <i class="fa-regular fa-arrows-left-right"></i>
                Geser untuk memilih hari lainnya
            </div>

            @if ($landingSchedules->isNotEmpty())
                @php
                    $featuredSchedule = $landingSchedules->first();
                    $otherSchedules = $landingSchedules->skip(1)->values();
                @endphp

                <div class="landing-doctor-schedule__showcase">
                    <article class="landing-schedule-feature wow fadeInLeft" data-wow-duration=".8s">
                        <span class="landing-schedule-feature__glow" aria-hidden="true"></span>
                        <span class="landing-schedule-feature__line" aria-hidden="true"></span>

                        <div class="landing-schedule-feature__top">
                            <span class="landing-schedule-feature__badge">
                                <i class="fa-solid fa-sparkles" aria-hidden="true"></i>
                                Sorotan jadwal hari ini
                            </span>
                            <span class="landing-schedule-feature__number" aria-hidden="true">01</span>
                        </div>

                        <div class="landing-schedule-feature__profile">
                            <div class="landing-schedule-feature__avatar">
                                <span class="landing-schedule-feature__orbit" aria-hidden="true"></span>
                                @if ($featuredSchedule["doctor_photo_url"])
                                    <img src="{{ $featuredSchedule["doctor_photo_url"] }}"
                                        alt="Foto {{ $featuredSchedule["doctor_name"] }}" width="144" height="144"
                                        loading="lazy" decoding="async">
                                @else
                                    <strong>{{ $featuredSchedule["doctor_initials"] }}</strong>
                                @endif
                                <i class="fa-solid fa-circle-check" aria-label="Dokter aktif"></i>
                            </div>
                            <span class="landing-schedule-feature__clinic">{{ $featuredSchedule["clinic_name"] }}</span>
                            <small>Dokter spesialis</small>
                            <h3>{{ $featuredSchedule["doctor_name"] }}</h3>
                        </div>

                        <div class="landing-schedule-feature__details">
                            <div>
                                <span aria-hidden="true"><i class="fa-regular fa-clock"></i></span>
                                <div>
                                    <small>Jam praktik</small>
                                    <strong>{{ $featuredSchedule["time_label"] }}</strong>
                                </div>
                            </div>
                            <div>
                                <span aria-hidden="true"><i class="fa-regular fa-users-medical"></i></span>
                                <div>
                                    <small>Kuota layanan</small>
                                    <strong>{{ $featuredSchedule["quota_label"] }}</strong>
                                </div>
                            </div>
                        </div>

                        <a href="{{ route("jadwalDokter.index", ["hari" => $featuredSchedule["day"], "q" => $featuredSchedule["doctor_name"]]) }}"
                            class="landing-schedule-feature__action"
                            aria-label="Lihat jadwal {{ $featuredSchedule["doctor_name"] }}">
                            <span>
                                <small>Rencanakan kunjungan</small>
                                Lihat detail jadwal
                            </span>
                            <i class="fa-solid fa-arrow-up-right" aria-hidden="true"></i>
                        </a>
                    </article>

                    <div class="landing-doctor-schedule__list wow fadeInRight" data-wow-duration=".8s">
                        <div class="landing-doctor-schedule__list-heading">
                            <div>
                                <span>Dokter tersedia</span>
                                <h3>Jadwal praktik lainnya</h3>
                            </div>
                            <strong>{{ str_pad($otherSchedules->count(), 2, "0", STR_PAD_LEFT) }}</strong>
                        </div>

                        <div class="landing-doctor-schedule__list-grid">
                            @foreach ($otherSchedules as $index => $schedule)
                                <article class="landing-schedule-mini is-tone-{{ ($index % 4) + 1 }}">
                                    <span class="landing-schedule-mini__number" aria-hidden="true">
                                        {{ str_pad($index + 2, 2, "0", STR_PAD_LEFT) }}
                                    </span>
                                    <div class="landing-schedule-mini__doctor">
                                        <div class="landing-schedule-mini__avatar">
                                            @if ($schedule["doctor_photo_url"])
                                                <img src="{{ $schedule["doctor_photo_url"] }}"
                                                    alt="Foto {{ $schedule["doctor_name"] }}" width="64" height="64"
                                                    loading="lazy" decoding="async">
                                            @else
                                                <span>{{ $schedule["doctor_initials"] }}</span>
                                            @endif
                                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                        </div>
                                        <div>
                                            <span class="landing-schedule-mini__clinic">{{ $schedule["clinic_name"] }}</span>
                                            <h4>{{ $schedule["doctor_name"] }}</h4>
                                        </div>
                                    </div>

                                    <div class="landing-schedule-mini__meta">
                                        <div>
                                            <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                            <span><small>Jam praktik</small>{{ $schedule["time_label"] }}</span>
                                        </div>
                                        <div>
                                            <i class="fa-regular fa-users" aria-hidden="true"></i>
                                            <span><small>Kuota</small>{{ $schedule["quota_label"] }}</span>
                                        </div>
                                    </div>

                                    <a href="{{ route("jadwalDokter.index", ["hari" => $schedule["day"], "q" => $schedule["doctor_name"]]) }}"
                                        class="landing-schedule-mini__action"
                                        aria-label="Lihat jadwal {{ $schedule["doctor_name"] }}">
                                        <span>Lihat jadwal</span>
                                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                    </a>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="landing-doctor-schedule__empty wow fadeInUp" data-wow-duration=".8s" role="status">
                    <span aria-hidden="true">
                        <i class="fa-regular {{ $landingScheduleError ? "fa-cloud-slash" : "fa-calendar-xmark" }}"></i>
                    </span>
                    <div>
                        <h3>{{ $landingScheduleError ? "Jadwal belum dapat dimuat" : "Belum ada jadwal praktik hari ini" }}</h3>
                        <p>{{ $landingScheduleError ? "Koneksi data rumah sakit sedang diperbarui. Silakan coba kembali beberapa saat lagi." : "Lihat jadwal hari lainnya untuk menemukan dokter yang Anda butuhkan." }}</p>
                    </div>
                </div>
            @endif

            <footer class="landing-doctor-schedule__footer wow fadeInUp" data-wow-duration=".8s">
                <div>
                    <span aria-hidden="true"><i class="fa-solid fa-circle-info"></i></span>
                    <p><strong>Perlu diperhatikan</strong> Jadwal dapat berubah mengikuti kondisi pelayanan dokter dan
                        rumah sakit.</p>
                </div>
                <a href="{{ route("jadwalDokter.index") }}">
                    Lihat semua jadwal
                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                </a>
            </footer>
        </div>
    </section>
    <!-- doctor schedule area end -->

    <!-- information and contact area start -->
    @php
        $hospitalLocation = [
            "name" => "RS KH. Abdurrahman Syamsuri (RS ARSY)",
            "address" => "Jl. Raya Deandles KM 74, Paciran, Kabupaten Lamongan, Jawa Timur 62264",
            "embed_url" => "https://www.google.com/maps?q=RS%20KH.%20Abdurrahman%20Syamsuri%20RS%20ARSY%20Paciran%20Lamongan&z=17&output=embed",
            "directions_url" => "https://www.google.com/maps/dir/?api=1&destination=RS%20KH.%20Abdurrahman%20Syamsuri%20RS%20ARSY%2C%20Paciran%2C%20Lamongan&destination_place_id=ChIJyevd-PLBdy4R2DwQysLmRDM&travelmode=driving",
        ];

        $hospitalContacts = [
            [
                "label" => "Call Centre",
                "number" => "081232870119",
                "href" => "tel:+6281232870119",
                "icon" => "fa-headset",
                "tone" => "primary",
            ],
            [
                "label" => "IGD",
                "number" => "081358292709",
                "href" => "tel:+6281358292709",
                "icon" => "fa-truck-medical",
                "tone" => "emergency",
            ],
            [
                "label" => "Pendaftaran",
                "number" => "088297178737",
                "href" => "tel:+6288297178737",
                "icon" => "fa-calendar-check",
                "tone" => "registration",
            ],
            [
                "label" => "Ruang Bersalin",
                "number" => "081359666323",
                "href" => "tel:+6281359666323",
                "icon" => "fa-baby-carriage",
                "tone" => "maternity",
            ],
            [
                "label" => "Marketing",
                "number" => "081232310793",
                "href" => "tel:+6281232310793",
                "icon" => "fa-bullhorn",
                "tone" => "marketing",
            ],
        ];
    @endphp

    <section id="informasi-kontak" class="landing-contact" aria-labelledby="landing-contact-title">
        <span class="landing-contact__shape landing-contact__shape--one" aria-hidden="true"></span>
        <span class="landing-contact__shape landing-contact__shape--two" aria-hidden="true"></span>

        <div class="container landing-contact__container">
            <div class="landing-contact__layout">
                <header class="landing-contact__intro wow fadeInLeft" data-wow-duration=".8s">
                    <span class="landing-contact__eyebrow">
                        <i class="fa-regular fa-comments" aria-hidden="true"></i>
                        Informasi &amp; Kontak
                    </span>
                    <h2 id="landing-contact-title">Kami selalu <span>dekat dengan Anda.</span></h2>
                    <p>Hubungi unit layanan yang Anda butuhkan. Tim RS Abdurrahman Syamsuri siap memberikan informasi
                        dan membantu kebutuhan Anda.</p>

                    <div class="landing-contact__urgent">
                        <span class="landing-contact__urgent-icon" aria-hidden="true">
                            <i class="fa-solid fa-star-of-life"></i>
                        </span>
                        <div>
                            <small>Kondisi darurat?</small>
                            <strong>IGD siap melayani 24 jam</strong>
                            <a href="tel:+6281358292709" aria-label="Hubungi IGD di 081358292709">
                                081358292709
                                <i class="fa-solid fa-arrow-up-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>

                    <div class="landing-contact__availability" aria-label="Layanan informasi tersedia setiap hari">
                        <span aria-hidden="true"><i class="fa-regular fa-clock"></i></span>
                        <div>
                            <strong>Informasi setiap hari</strong>
                            <small>Pilih kontak sesuai kebutuhan layanan Anda.</small>
                        </div>
                    </div>
                </header>

                <div class="landing-contact__directory wow fadeInRight" data-wow-duration=".8s">
                    <div class="landing-contact__directory-heading">
                        <div>
                            <span>Direktori layanan</span>
                            <h3>Terhubung dengan cepat</h3>
                        </div>
                        <span class="landing-contact__directory-count">06</span>
                    </div>

                    <div class="landing-contact__grid">
                        @foreach ($hospitalContacts as $index => $contact)
                            <a href="{{ $contact["href"] }}"
                                class="landing-contact-card is-{{ $contact["tone"] }}"
                                aria-label="Hubungi {{ $contact["label"] }} di {{ $contact["number"] }}">
                                <span class="landing-contact-card__top">
                                    <span class="landing-contact-card__icon" aria-hidden="true">
                                        <i class="fa-solid {{ $contact["icon"] }}"></i>
                                    </span>
                                    <small>{{ str_pad($index + 1, 2, "0", STR_PAD_LEFT) }}</small>
                                </span>
                                <span class="landing-contact-card__body">
                                    <small>Layanan</small>
                                    <strong>{{ $contact["label"] }}</strong>
                                </span>
                                <span class="landing-contact-card__action">
                                    <strong>{{ $contact["number"] }}</strong>
                                    <span aria-hidden="true"><i class="fa-solid fa-phone"></i></span>
                                </span>
                            </a>
                        @endforeach

                        <a href="https://www.instagram.com/rsarsy_official/" target="_blank" rel="noopener noreferrer"
                            class="landing-contact-card is-instagram"
                            aria-label="Kunjungi Instagram RS Arsy di rsarsy_official">
                            <span class="landing-contact-card__top">
                                <span class="landing-contact-card__icon" aria-hidden="true">
                                    <i class="fa-brands fa-instagram"></i>
                                </span>
                                <small>06</small>
                            </span>
                            <span class="landing-contact-card__body">
                                <small>Media sosial</small>
                                <strong>Instagram</strong>
                            </span>
                            <span class="landing-contact-card__action">
                                <strong>@rsarsy_official</strong>
                                <span aria-hidden="true"><i class="fa-solid fa-arrow-up-right"></i></span>
                            </span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="landing-contact__location wow fadeInUp" data-wow-duration=".8s"
                aria-labelledby="hospital-location-title">
                <div class="landing-contact__location-copy">
                    <span class="landing-contact__location-eyebrow">
                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                        Lokasi RS ARSY
                    </span>
                    <h3 id="hospital-location-title">Datang dengan rute yang <span>lebih mudah.</span></h3>
                    <p>Temukan lokasi rumah sakit dan mulai navigasi dari posisi Anda melalui Google Maps.</p>

                    <address class="landing-contact__address">
                        <span aria-hidden="true"><i class="fa-solid fa-hospital"></i></span>
                        <div>
                            <strong>{{ $hospitalLocation["name"] }}</strong>
                            <small>{{ $hospitalLocation["address"] }}</small>
                        </div>
                    </address>

                    <div class="landing-contact__location-meta" aria-label="Informasi lokasi">
                        <span><i class="fa-solid fa-road" aria-hidden="true"></i> Akses Jalan Raya</span>
                        <span><i class="fa-regular fa-clock" aria-hidden="true"></i> IGD 24 Jam</span>
                    </div>

                    <a href="{{ $hospitalLocation["directions_url"] }}" target="_blank" rel="noopener noreferrer"
                        class="landing-contact__directions"
                        aria-label="Buka navigasi menuju RS ARSY di Google Maps">
                        <span aria-hidden="true"><i class="fa-solid fa-diamond-turn-right"></i></span>
                        <strong>
                            Navigasi ke RS ARSY
                            <small>Buka rute di Google Maps</small>
                        </strong>
                        <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                    </a>
                </div>

                <div class="landing-contact__map">
                    <iframe src="{{ $hospitalLocation["embed_url"] }}"
                        title="Peta lokasi RS KH. Abdurrahman Syamsuri"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen></iframe>
                    <span class="landing-contact__map-pin" aria-hidden="true">
                        <i class="fa-solid fa-location-dot"></i>
                        <strong>RS ARSY</strong>
                    </span>
                </div>
            </div>
        </div>
    </section>
    <!-- information and contact area end -->

    <!-- specialist doctors area start -->
    @php
        $displayDoctors = collect($landingDoctors)
            ->map(function (array $doctor): array {
                $doctorName = trim($doctor["doctor_name"] ?? "");
                $nameParts = collect(preg_split('/\s+/u', preg_replace('/[^\pL\s]/u', ' ', $doctorName)))
                    ->reject(fn (string $part): bool => in_array(mb_strtolower($part), ["dr", "drg", "prof"], true))
                    ->filter()
                    ->take(2);
                $credential = str_contains($doctorName, ",")
                    ? trim(strstr($doctorName, ","), ", ")
                    : "";

                return [
                    "doctor_code" => $doctor["doctor_code"] ?? "",
                    "doctor_name" => $doctorName,
                    "doctor_initials" => $nameParts
                        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
                        ->implode("") ?: "DR",
                    "photo_url" => $doctor["photo_url"] ?? null,
                    "doctor_label" => $credential !== "" ? $credential : "Dokter Spesialis",
                ];
            });

        if ($displayDoctors->isEmpty()) {
            $displayDoctors = $landingSchedules
                ->unique("doctor_code")
                ->map(fn (array $schedule): array => [
                    "doctor_code" => $schedule["doctor_code"],
                    "doctor_name" => $schedule["doctor_name"],
                    "doctor_initials" => $schedule["doctor_initials"],
                    "photo_url" => $schedule["doctor_photo_url"],
                    "doctor_label" => $schedule["clinic_name"],
                ])
                ->values();
        }
    @endphp

    <section id="dokter-spesialis" class="landing-specialists" aria-labelledby="landing-specialists-title">
        <span class="landing-specialists__shape landing-specialists__shape--one" aria-hidden="true"></span>
        <span class="landing-specialists__shape landing-specialists__shape--two" aria-hidden="true"></span>

        <div class="container landing-specialists__container">
            <header class="landing-specialists__header">
                <div class="landing-specialists__heading wow fadeInLeft" data-wow-duration=".8s">
                    <span class="landing-specialists__eyebrow">
                        <i class="fa-regular fa-user-doctor" aria-hidden="true"></i>
                        Dokter Spesialis RS ARSY
                    </span>
                    <h2 id="landing-specialists-title">Tenaga ahli untuk <span>setiap langkah pemulihan.</span></h2>
                </div>

                <div class="landing-specialists__intro wow fadeInRight" data-wow-duration=".8s">
                    <p>Kenali dokter spesialis kami dan temukan jadwal praktik yang paling sesuai dengan kebutuhan
                        kesehatan Anda dan keluarga.</p>
                    <div class="landing-specialists__intro-actions">
                        <a href="{{ route("jadwalDokter.index") }}" class="landing-specialists__all">
                            Lihat semua dokter
                            <i class="fa-solid fa-arrow-up-right" aria-hidden="true"></i>
                        </a>
                        @if ($displayDoctors->count() > 1)
                            <div class="landing-specialists__navigation" aria-label="Navigasi daftar dokter">
                                <button type="button" class="landing-specialists__prev"
                                    aria-label="Dokter sebelumnya">
                                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="landing-specialists__next"
                                    aria-label="Dokter berikutnya">
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </header>

            @if ($displayDoctors->isNotEmpty())
                <div class="landing-specialists__carousel wow fadeInUp" data-wow-duration=".8s">
                    <div class="swiper team-swiper-container-h1">
                        <div class="swiper-wrapper">
                            @foreach ($displayDoctors as $doctor)
                                @php
                                    $doctorScheduleUrl = route("jadwalDokter.index", [
                                        "q" => $doctor["doctor_name"],
                                    ]);
                                @endphp
                                <div class="swiper-slide">
                                    <article class="landing-specialist-card is-tone-{{ ($loop->index % 4) + 1 }}">
                                        <a href="{{ $doctorScheduleUrl }}" class="landing-specialist-card__portrait"
                                            aria-label="Lihat jadwal {{ $doctor["doctor_name"] }}">
                                            <span class="landing-specialist-card__initials" aria-hidden="true">
                                                {{ $doctor["doctor_initials"] }}
                                            </span>
                                            @if ($doctor["photo_url"])
                                                <img src="{{ $doctor["photo_url"] }}"
                                                    alt="Foto {{ $doctor["doctor_name"] }}" width="450" height="520"
                                                    loading="lazy" decoding="async" onerror="this.remove()">
                                            @endif
                                            <span class="landing-specialist-card__status">
                                                <i aria-hidden="true"></i>
                                                Siap melayani
                                            </span>
                                            <span class="landing-specialist-card__number" aria-hidden="true">
                                                {{ str_pad($loop->iteration, 2, "0", STR_PAD_LEFT) }}
                                            </span>
                                        </a>

                                        <div class="landing-specialist-card__body">
                                            <span class="landing-specialist-card__role">Dokter spesialis</span>
                                            <h3>
                                                <a href="{{ $doctorScheduleUrl }}">{{ $doctor["doctor_name"] }}</a>
                                            </h3>
                                            <div class="landing-specialist-card__expertise">
                                                <span aria-hidden="true"><i class="fa-regular fa-stethoscope"></i></span>
                                                <div>
                                                    <small>Bidang layanan</small>
                                                    <strong>{{ $doctor["doctor_label"] }}</strong>
                                                </div>
                                            </div>
                                            <a href="{{ $doctorScheduleUrl }}" class="landing-specialist-card__action">
                                                <span>
                                                    <small>Rencanakan kunjungan</small>
                                                    Lihat jadwal dokter
                                                </span>
                                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </article>
                                </div>
                            @endforeach
                        </div>
                        <div class="swiper-pagination" aria-label="Halaman daftar dokter"></div>
                    </div>
                </div>
                <p class="landing-specialists__mobile-hint" aria-hidden="true">
                    <i class="fa-regular fa-arrows-left-right"></i>
                    Geser untuk melihat dokter lainnya
                </p>
            @else
                <div class="landing-specialists__empty wow fadeInUp" data-wow-duration=".8s" role="status">
                    <span aria-hidden="true"><i class="fa-regular fa-user-doctor"></i></span>
                    <div>
                        <h3>Profil dokter sedang diperbarui</h3>
                        <p>Daftar lengkap beserta jadwal praktik tetap dapat dilihat pada halaman jadwal dokter.</p>
                    </div>
                    <a href="{{ route("jadwalDokter.index") }}">Buka jadwal dokter</a>
                </div>
            @endif
        </div>
    </section>
    <!-- specialist doctors area end -->

@endsection
