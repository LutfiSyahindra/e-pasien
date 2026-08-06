@extends("template.epasien.appPasien")

@section("title", "Foto Dokter | E-Pasien")

@push("style")
    <link href="{{ asset("epasien/assets/css/doctor-photo-settings.css") }}" rel="stylesheet" />
@endpush

@section("content")
    @php
        $visibleDoctorCount = $doctors->count();
        $pendingPhotos = max(0, $visibleDoctorCount - $configuredPhotos);
        $photoCoverage = $visibleDoctorCount > 0
            ? (int) round(($configuredPhotos / $visibleDoctorCount) * 100)
            : 0;
    @endphp

    <main class="doctor-photo-page">
        <nav class="doctor-photo-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route("dashboard") }}" aria-label="Kembali ke Dashboard">
                <i class="bi bi-house-door"></i>
            </a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span>Landing Page &amp; Data</span>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span class="active">Foto Dokter</span>
        </nav>

        <section class="doctor-photo-hero">
            <span class="doctor-photo-hero-orbit orbit-one" aria-hidden="true"></span>
            <span class="doctor-photo-hero-orbit orbit-two" aria-hidden="true"></span>
            <div class="doctor-photo-heading">
                <span class="doctor-photo-heading-icon"><i class="bi bi-person-bounding-box"></i></span>
                <div>
                    <span class="doctor-photo-eyebrow"><i class="bi bi-stars"></i> Galeri tenaga medis</span>
                    <h1>Foto Dokter</h1>
                    <p>Kelola foto profesional dokter untuk landing page dan kartu Jadwal Dokter pasien.</p>
                </div>
            </div>
            <div class="doctor-photo-hero-status">
                <span><i class="bi bi-shield-check"></i> Siap tayang otomatis</span>
                <small><i class="bi bi-crop"></i> Crop 1:1 &nbsp;&bull;&nbsp; JPG, PNG, WEBP &nbsp;&bull;&nbsp; Maks. 2 MB</small>
            </div>
        </section>

        @if (session("status"))
            <div class="doctor-photo-alert success" role="status">
                <i class="bi bi-check2-circle"></i>
                <div><strong>Perubahan tersimpan</strong><span>{{ session("status") }}</span></div>
            </div>
        @endif

        @if ($errors->any())
            <div class="doctor-photo-alert danger" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <div>
                    <strong>Foto belum dapat disimpan</strong>
                    <span>{{ $errors->first() }}</span>
                </div>
            </div>
        @endif

        <div class="doctor-photo-alert danger doctor-photo-client-alert" data-photo-feedback role="alert" hidden>
            <i class="bi bi-exclamation-triangle"></i>
            <div>
                <strong>Foto belum dapat diproses</strong>
                <span data-photo-feedback-message></span>
            </div>
            <button type="button" data-photo-feedback-close aria-label="Tutup pemberitahuan">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <section class="doctor-photo-toolbar" aria-label="Pencarian dan ringkasan dokter">
            <div class="doctor-photo-toolbar-search">
                <div class="doctor-photo-toolbar-title">
                    <span>Koleksi dokter</span>
                    <h2>{{ $search !== "" ? "Hasil pencarian" : "Kelola foto dengan cepat" }}</h2>
                </div>

                <form action="{{ route("doctorPhotoSettings.index") }}" method="GET">
                    <label for="doctorPhotoSearch">Cari dokter berdasarkan nama atau kode</label>
                    <div class="doctor-photo-search-field">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input id="doctorPhotoSearch" type="search" name="q" value="{{ $search }}"
                            maxlength="80" placeholder="Cari nama atau kode dokter" autocomplete="off">
                        @if ($search !== "")
                            <a href="{{ route("doctorPhotoSettings.index") }}" aria-label="Hapus pencarian"
                                title="Hapus pencarian">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                    </div>
                    <button type="submit"><i class="bi bi-search"></i><span>Cari dokter</span></button>
                </form>
            </div>

            <dl class="doctor-photo-summary">
                <div>
                    <dt><span class="doctor-photo-summary-icon"><i class="bi bi-people"></i></span>Dokter</dt>
                    <dd>{{ number_format($doctors->total(), 0, ",", ".") }}</dd>
                    <small>{{ $search !== "" ? "hasil ditemukan" : "aktif tersedia" }}</small>
                </div>
                <div class="is-complete">
                    <dt><span class="doctor-photo-summary-icon"><i class="bi bi-patch-check"></i></span>Terpasang</dt>
                    <dd>{{ number_format($configuredPhotos, 0, ",", ".") }}</dd>
                    <small>di halaman ini</small>
                </div>
                <div class="{{ $pendingPhotos > 0 ? "is-pending" : "is-complete" }}">
                    <dt>
                        <span class="doctor-photo-summary-icon">
                            <i class="bi {{ $pendingPhotos > 0 ? "bi-hourglass-split" : "bi-check2-all" }}"></i>
                        </span>
                        {{ $pendingPhotos > 0 ? "Perlu foto" : "Lengkap" }}
                    </dt>
                    <dd>{{ $pendingPhotos > 0 ? number_format($pendingPhotos, 0, ",", ".") : $photoCoverage."%" }}</dd>
                    <small>di halaman ini</small>
                </div>
            </dl>
        </section>

        @if ($connectionError)
            <section class="doctor-photo-empty">
                <span><i class="bi bi-cloud-slash"></i></span>
                <h2>Data dokter belum tersedia</h2>
                <p>{{ $connectionError }}</p>
                <a href="{{ route("doctorPhotoSettings.index") }}"><i class="bi bi-arrow-clockwise"></i> Coba lagi</a>
            </section>
        @elseif ($doctors->count() === 0)
            <section class="doctor-photo-empty">
                <span><i class="bi bi-person-x"></i></span>
                <h2>Dokter tidak ditemukan</h2>
                <p>Coba gunakan nama atau kode dokter yang lebih singkat.</p>
                <a href="{{ route("doctorPhotoSettings.index") }}">Tampilkan semua dokter</a>
            </section>
        @else
            <section class="doctor-photo-grid" aria-label="Daftar foto dokter">
                @foreach ($doctors as $doctor)
                    <article class="doctor-photo-card {{ $doctor["photo_url"] ? "has-photo" : "needs-photo" }}">
                        <div class="doctor-photo-portrait">
                            <span class="doctor-photo-initials" aria-hidden="true">{{ $doctor["doctor_initials"] }}</span>
                            @if ($doctor["photo_url"])
                                <img src="{{ $doctor["photo_url"] }}" alt="Foto {{ $doctor["doctor_name"] }}"
                                    width="320" height="320" loading="lazy" decoding="async" onerror="this.remove()">
                            @endif
                            <span class="doctor-photo-code">{{ $doctor["doctor_code"] }}</span>
                            <em class="{{ $doctor["photo_url"] ? "is-set" : "" }}">
                                <i class="bi {{ $doctor["photo_url"] ? "bi-check-circle-fill" : "bi-image" }}"></i>
                                {{ $doctor["photo_url"] ? "Siap tayang" : "Perlu foto" }}
                            </em>
                        </div>
                        <div class="doctor-photo-card-body">
                            <div class="doctor-photo-identity">
                                <h2>{{ $doctor["doctor_name"] }}</h2>
                                <p><i class="bi bi-person" aria-hidden="true"></i> {{ $doctor["gender"] }}</p>
                            </div>

                            <div class="doctor-photo-card-actions">
                                <form class="doctor-photo-upload-form" method="POST"
                                    action="{{ route("doctorPhotoSettings.update") }}" enctype="multipart/form-data"
                                    data-doctor-name="{{ $doctor["doctor_name"] }}">
                                    @csrf
                                    <input type="hidden" name="doctor_code" value="{{ $doctor["doctor_code"] }}">
                                    <input type="hidden" name="filter_q" value="{{ $search }}">
                                    <input type="hidden" name="filter_page" value="{{ $doctors->currentPage() }}">
                                    <input class="doctor-photo-cropped-input" type="hidden" name="doctor_photo_cropped">
                                    <input class="doctor-photo-file-input" type="file" name="doctor_photo"
                                        accept="image/png,image/jpeg,image/webp" tabindex="-1">
                                    <button class="doctor-photo-upload-button" type="button" data-photo-picker>
                                        <i class="bi {{ $doctor["photo_url"] ? "bi-arrow-repeat" : "bi-cloud-arrow-up" }}"></i>
                                        <span>{{ $doctor["photo_url"] ? "Ganti foto" : "Pilih foto" }}</span>
                                        <i class="bi bi-arrow-right-short doctor-photo-button-arrow" aria-hidden="true"></i>
                                    </button>
                                </form>

                                @if ($doctor["photo_url"])
                                    <form method="POST"
                                        action="{{ route("doctorPhotoSettings.destroy", $doctor["doctor_code"]) }}"
                                        data-photo-delete-form data-doctor-name="{{ $doctor["doctor_name"] }}">
                                        @csrf
                                        @method("DELETE")
                                        <button class="doctor-photo-delete-button" type="submit"
                                            title="Hapus foto {{ $doctor["doctor_name"] }}">
                                            <i class="bi bi-trash3"></i> <span>Hapus foto</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>

            @if ($doctors->hasPages())
                <nav class="doctor-photo-pagination" aria-label="Navigasi daftar dokter">
                    @if ($doctors->previousPageUrl())
                        <a href="{{ $doctors->previousPageUrl() }}" rel="prev"><i class="bi bi-chevron-left"></i> Sebelumnya</a>
                    @else
                        <span class="disabled"><i class="bi bi-chevron-left"></i> Sebelumnya</span>
                    @endif
                    <span><small>Halaman</small><strong>{{ $doctors->currentPage() }} / {{ $doctors->lastPage() }}</strong></span>
                    @if ($doctors->nextPageUrl())
                        <a href="{{ $doctors->nextPageUrl() }}" rel="next">Berikutnya <i class="bi bi-chevron-right"></i></a>
                    @else
                        <span class="disabled">Berikutnya <i class="bi bi-chevron-right"></i></span>
                    @endif
                </nav>
            @endif
        @endif

        <aside class="doctor-photo-note">
            <span><i class="bi bi-lightbulb-fill"></i></span>
            <div>
                <strong>Hasil terbaik dimulai dari foto yang tepat</strong>
                <p>Gunakan foto menghadap depan, pencahayaan merata, dan latar yang bersih. Hasil crop persegi otomatis dioptimalkan untuk seluruh tampilan pasien.</p>
            </div>
        </aside>

        <div class="modal fade doctor-photo-crop-modal" id="doctorPhotoCropModal" tabindex="-1"
            aria-labelledby="doctorPhotoCropTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <span><i class="bi bi-stars"></i> Editor foto dokter</span>
                            <h2 class="modal-title" id="doctorPhotoCropTitle">Atur posisi foto</h2>
                            <p>Untuk <strong data-crop-doctor>dokter terpilih</strong></p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="doctor-photo-crop-layout">
                            <div class="doctor-photo-crop-stage" data-crop-stage>
                                <img data-crop-image alt="Preview foto yang akan dipotong">
                                <span aria-hidden="true"><i class="bi bi-arrows-move"></i> Geser untuk mengatur posisi</span>
                            </div>
                            <div class="doctor-photo-crop-side">
                                <div class="doctor-photo-crop-preview">
                                    <span class="doctor-photo-crop-preview-label">Preview hasil</span>
                                    <canvas data-crop-preview width="180" height="180"></canvas>
                                    <small>640 &times; 640 px</small>
                                </div>
                                <div class="doctor-photo-crop-tools">
                                    <div class="doctor-photo-crop-tool-heading">
                                        <label for="doctorPhotoCropZoom">Perbesar foto</label>
                                        <output for="doctorPhotoCropZoom" data-crop-zoom-output>100%</output>
                                    </div>
                                    <div class="doctor-photo-zoom-control">
                                        <i class="bi bi-image" aria-hidden="true"></i>
                                        <input id="doctorPhotoCropZoom" type="range" min="1" max="3" step="0.01"
                                            value="1" data-crop-zoom aria-label="Perbesar atau perkecil foto">
                                        <i class="bi bi-image-fill" aria-hidden="true"></i>
                                    </div>
                                    <button type="button" class="doctor-photo-crop-reset" data-crop-reset>
                                        <i class="bi bi-arrow-counterclockwise"></i> Reset posisi
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <small><i class="bi bi-shield-check"></i> Foto dioptimalkan sebelum disimpan</small>
                        <div>
                            <button type="button" class="doctor-photo-crop-cancel" data-bs-dismiss="modal">Batal</button>
                            <button type="button" class="doctor-photo-crop-save" data-crop-save disabled>
                                <i class="bi bi-check2-circle"></i> <span>Gunakan foto</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade doctor-photo-delete-modal" id="doctorPhotoDeleteModal" tabindex="-1"
            aria-labelledby="doctorPhotoDeleteTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="doctor-photo-delete-icon"><i class="bi bi-trash3"></i></div>
                    <h2 class="modal-title" id="doctorPhotoDeleteTitle">Hapus foto dokter?</h2>
                    <p>Foto <strong data-delete-doctor>dokter ini</strong> akan dihapus dan tampilan pasien kembali menggunakan inisial.</p>
                    <div class="doctor-photo-delete-actions">
                        <button type="button" class="doctor-photo-crop-cancel" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="doctor-photo-delete-confirm" data-delete-confirm>
                            <i class="bi bi-trash3"></i> <span>Ya, hapus foto</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push("script")
    <script src="{{ asset("epasien/assets/js/doctor-photo-crop.js") }}"></script>
@endpush
