@extends("template.epasien.appPasien")

@section("title", "Foto Dokter | E-Pasien")

@push("style")
    <link href="{{ asset("epasien/assets/css/doctor-photo-settings.css") }}" rel="stylesheet" />
@endpush

@section("content")
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
            <div class="doctor-photo-heading">
                <span class="doctor-photo-heading-icon"><i class="bi bi-person-bounding-box"></i></span>
                <div>
                    <span class="doctor-photo-eyebrow">Data visual landing page</span>
                    <h1>Foto Dokter</h1>
                    <p>Atur foto dokter yang ditampilkan pada landing page dan kartu Jadwal Dokter pasien.</p>
                </div>
            </div>
            <div class="doctor-photo-hero-status">
                <span><i class="bi bi-crop"></i> Crop persegi</span>
                <small>JPG, PNG, atau WEBP · Maksimal 2 MB</small>
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

        <section class="doctor-photo-toolbar" aria-label="Pencarian dokter">
            <form action="{{ route("doctorPhotoSettings.index") }}" method="GET">
                <label for="doctorPhotoSearch">Cari dokter</label>
                <div class="doctor-photo-search-field">
                    <i class="bi bi-search"></i>
                    <input id="doctorPhotoSearch" type="search" name="q" value="{{ $search }}"
                        maxlength="80" placeholder="Nama atau kode dokter..." autocomplete="off">
                    @if ($search !== "")
                        <a href="{{ route("doctorPhotoSettings.index") }}" aria-label="Hapus pencarian">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
                <button type="submit"><i class="bi bi-search"></i><span>Cari</span></button>
            </form>
            <div class="doctor-photo-summary">
                <span><i class="bi bi-people"></i></span>
                <div>
                    <strong>{{ number_format($doctors->total(), 0, ",", ".") }}</strong>
                    <small>{{ $search !== "" ? "dokter ditemukan" : "dokter aktif" }}</small>
                </div>
                <div>
                    <strong>{{ number_format($configuredPhotos, 0, ",", ".") }}</strong>
                    <small>foto di halaman ini</small>
                </div>
            </div>
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
                    <article class="doctor-photo-card">
                        <div class="doctor-photo-portrait">
                            <span>{{ $doctor["doctor_initials"] }}</span>
                            @if ($doctor["photo_url"])
                                <img src="{{ $doctor["photo_url"] }}" alt="Foto {{ $doctor["doctor_name"] }}"
                                    width="320" height="320" loading="lazy" decoding="async" onerror="this.remove()">
                            @endif
                            <em class="{{ $doctor["photo_url"] ? "is-set" : "" }}">
                                <i class="bi {{ $doctor["photo_url"] ? "bi-check-circle-fill" : "bi-image" }}"></i>
                                {{ $doctor["photo_url"] ? "Foto aktif" : "Belum ada foto" }}
                            </em>
                        </div>
                        <div class="doctor-photo-card-body">
                            <span class="doctor-photo-code">{{ $doctor["doctor_code"] }}</span>
                            <h2>{{ $doctor["doctor_name"] }}</h2>
                            <p><i class="bi bi-person"></i> {{ $doctor["gender"] }}</p>

                            <form class="doctor-photo-upload-form" method="POST"
                                action="{{ route("doctorPhotoSettings.update") }}" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="doctor_code" value="{{ $doctor["doctor_code"] }}">
                                <input type="hidden" name="filter_q" value="{{ $search }}">
                                <input type="hidden" name="filter_page" value="{{ $doctors->currentPage() }}">
                                <input class="doctor-photo-cropped-input" type="hidden" name="doctor_photo_cropped">
                                <input class="doctor-photo-file-input" type="file" name="doctor_photo"
                                    accept="image/png,image/jpeg,image/webp" tabindex="-1">
                                <button class="doctor-photo-upload-button" type="button" data-photo-picker>
                                    <i class="bi {{ $doctor["photo_url"] ? "bi-arrow-repeat" : "bi-cloud-arrow-up" }}"></i>
                                    {{ $doctor["photo_url"] ? "Ganti & crop foto" : "Upload & crop foto" }}
                                </button>
                            </form>

                            @if ($doctor["photo_url"])
                                <form method="POST" action="{{ route("doctorPhotoSettings.destroy", $doctor["doctor_code"]) }}"
                                    data-photo-delete-form>
                                    @csrf
                                    @method("DELETE")
                                    <button class="doctor-photo-delete-button" type="submit">
                                        <i class="bi bi-trash3"></i> Hapus foto
                                    </button>
                                </form>
                            @endif
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
            <i class="bi bi-info-circle-fill"></i>
            <p><strong>Tips foto yang baik</strong> Gunakan foto menghadap depan dengan pencahayaan cukup. Area crop berbentuk persegi dan hasilnya otomatis dioptimalkan.</p>
        </aside>

        <div class="modal fade doctor-photo-crop-modal" id="doctorPhotoCropModal" tabindex="-1"
            aria-labelledby="doctorPhotoCropTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <span>Foto dokter</span>
                            <h2 class="modal-title" id="doctorPhotoCropTitle">Atur posisi foto</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="doctor-photo-crop-layout">
                            <div class="doctor-photo-crop-stage" data-crop-stage>
                                <img data-crop-image alt="Preview foto yang akan dipotong">
                                <span aria-hidden="true"><i class="bi bi-arrows-move"></i> Geser foto</span>
                            </div>
                            <div class="doctor-photo-crop-side">
                                <div class="doctor-photo-crop-preview">
                                    <canvas data-crop-preview width="180" height="180"></canvas>
                                    <span>Preview hasil</span>
                                </div>
                                <label for="doctorPhotoCropZoom">Zoom</label>
                                <div class="doctor-photo-zoom-control">
                                    <i class="bi bi-dash-circle"></i>
                                    <input id="doctorPhotoCropZoom" type="range" min="1" max="3" step="0.01"
                                        value="1" data-crop-zoom>
                                    <i class="bi bi-plus-circle"></i>
                                </div>
                                <button type="button" class="doctor-photo-crop-reset" data-crop-reset>
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset posisi
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="doctor-photo-crop-cancel" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="doctor-photo-crop-save" data-crop-save disabled>
                            <i class="bi bi-check2-circle"></i> Gunakan foto
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
