@extends("template.epasien.appPasien")

@php($editing = $promotion->exists)
@section("title", ($editing ? "Edit" : "Buat") . " Konten Promosi & Informasi - E-Pasien")

@push("style")
    <link href="{{ asset("epasien/assets/css/promotion-premium.css") }}" rel="stylesheet">
@endpush

@section("content")
    <div class="promo-shell promo-form-shell">
        <nav class="promo-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route("promotions.index") }}"><i class="bi bi-arrow-left"></i> Kembali ke Promosi &amp; Informasi</a>
        </nav>

        <header class="promo-form-header">
            <span class="promo-eyebrow"><i class="bi bi-stars"></i> Pusat publikasi</span>
            <h1>{{ $editing ? "Sempurnakan konten" : "Buat konten baru" }}</h1>
            <p>{{ $editing ? "Perbarui isi dan periode tayang sesuai kebutuhan." : "Terbitkan promosi atau informasi yang bermanfaat bagi pasien." }}</p>
        </header>

        @if ($errors->any())
            <div class="promo-form-errors" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <div><strong>Periksa kembali formulir Anda</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            </div>
        @endif

        <form class="promo-editor" method="POST" enctype="multipart/form-data"
            action="{{ $editing ? route("promotions.update", $promotion) : route("promotions.store") }}">
            @csrf
            @if ($editing) @method("PUT") @endif

            <section class="promo-editor__main">
                <div class="promo-editor-card">
                    <div class="promo-editor-card__heading"><span><i class="bi bi-tags"></i></span><div><h2>Kategori konten</h2><p>Pilih jenis konten agar pasien dapat membedakan promosi dan informasi.</p></div></div>
                    <div class="promo-category-options">
                        <label class="promo-radio-option"><input type="radio" name="category" value="promotion" required @checked(old("category", $promotion->category ?: "promotion") === "promotion")><span><i class="bi bi-megaphone-fill"></i><span><strong>Promosi</strong><small>Penawaran, paket layanan, atau program khusus</small></span></span></label>
                        <label class="promo-radio-option"><input type="radio" name="category" value="information" required @checked(old("category", $promotion->category ?: "promotion") === "information")><span><i class="bi bi-info-circle-fill"></i><span><strong>Informasi</strong><small>Pengumuman, edukasi, jadwal, atau kabar rumah sakit</small></span></span></label>
                    </div>
                </div>

                <div class="promo-editor-card">
                    <div class="promo-editor-card__heading"><span><i class="bi bi-image"></i></span><div><h2>Visual konten</h2><p>Gambar rasio 4:3 atau 16:9 memberi hasil terbaik.</p></div></div>
                    <label class="promo-image-uploader" for="promo-image">
                        <img data-image-preview src="{{ $editing ? $promotion->image_url : "" }}" alt="Pratinjau gambar" @if (!$editing) hidden @endif>
                        <span data-upload-placeholder @if ($editing) hidden @endif>
                            <i class="bi bi-cloud-arrow-up"></i><strong>Tarik gambar atau pilih dari perangkat</strong><small>JPG, PNG, WebP • Maksimal 5 MB</small>
                        </span>
                        <span class="promo-image-uploader__change"><i class="bi bi-camera"></i> Ganti gambar</span>
                        <input id="promo-image" name="image" type="file" accept="image/jpeg,image/png,image/webp" {{ $editing ? "" : "required" }}>
                    </label>
                </div>

                <div class="promo-editor-card">
                    <div class="promo-editor-card__heading"><span><i class="bi bi-card-text"></i></span><div><h2>Isi konten</h2><p>Tulis pesan singkat, jelas, dan mudah dipahami.</p></div></div>
                    <label class="promo-field"><span>Judul konten <b>*</b></span><input name="title" type="text" maxlength="120" required value="{{ old("title", $promotion->title) }}" placeholder="Contoh: Jadwal Layanan atau Paket Medical Check-Up"><small><span data-count-for="title">0</span>/120 karakter</small></label>
                    <label class="promo-field"><span>Caption <b>*</b></span><textarea name="caption" rows="7" maxlength="2000" required placeholder="Tuliskan informasi, manfaat, syarat, atau detail penting lainnya...">{{ old("caption", $promotion->caption) }}</textarea><small><span data-count-for="caption">0</span>/2000 karakter</small></label>
                </div>
            </section>

            <aside class="promo-editor__side">
                <div class="promo-editor-card promo-schedule-card">
                    <div class="promo-editor-card__heading"><span><i class="bi bi-calendar2-week"></i></span><div><h2>Jadwal tayang</h2><p>Atur kapan pasien melihat konten.</p></div></div>
                    <label class="promo-field"><span>Mulai tayang <b>*</b></span><input name="starts_at" type="datetime-local" required value="{{ old("starts_at", optional($promotion->starts_at)->format("Y-m-d\TH:i")) }}"></label>
                    <div class="promo-duration-fields">
                        <label class="promo-field"><span>Durasi <b>*</b></span><input name="duration_value" type="number" min="1" max="9999" required value="{{ old("duration_value", $promotion->duration_value) }}"></label>
                        <label class="promo-field"><span>Satuan <b>*</b></span><select name="duration_unit" required>@foreach (["hour" => "Jam", "day" => "Hari", "month" => "Bulan", "year" => "Tahun"] as $value => $label)<option value="{{ $value }}" @selected(old("duration_unit", $promotion->duration_unit) === $value)>{{ $label }}</option>@endforeach</select></label>
                    </div>
                    <div class="promo-schedule-preview"><i class="bi bi-info-circle"></i><span>Konten otomatis berhenti ditampilkan setelah durasinya berakhir.</span></div>
                </div>

                <div class="promo-editor-card">
                    <div class="promo-editor-card__heading"><span><i class="bi bi-send-check"></i></span><div><h2>Status publikasi</h2><p>Pilih simpan draf atau langsung terbitkan.</p></div></div>
                    <label class="promo-radio-option"><input type="radio" name="status" value="draft" @checked(old("status", $promotion->status) === "draft")><span><i class="bi bi-pencil-square"></i><span><strong>Simpan sebagai draf</strong><small>Belum terlihat oleh pasien</small></span></span></label>
                    <label class="promo-radio-option"><input type="radio" name="status" value="published" @checked(old("status", $promotion->status) === "published")><span><i class="bi bi-broadcast"></i><span><strong>Terbitkan</strong><small>Tayang sesuai jadwal</small></span></span></label>
                    <p class="promo-notify-note"><i class="bi bi-bell-fill"></i> Pasien akan menerima notifikasi saat konten mulai tayang.</p>
                </div>

                <div class="promo-form-actions">
                    <a href="{{ route("promotions.index") }}">Batal</a>
                    <button type="submit"><i class="bi bi-check2-circle"></i>{{ $editing ? "Simpan perubahan" : "Simpan konten" }}</button>
                </div>
            </aside>
        </form>
    </div>
@endsection

@push("script")
    <script src="{{ asset("epasien/assets/js/promotion-page.js") }}"></script>
@endpush
