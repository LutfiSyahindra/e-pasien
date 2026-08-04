@extends("template.epasien.appPasien")

@section("title", "Konfigurasi Promosi & Informasi - E-Pasien")

@push("style")
    <link href="{{ asset("epasien/assets/css/promotion-premium.css") }}" rel="stylesheet">
@endpush

@section("content")
    <div class="promo-shell promo-form-shell">
        <nav class="promo-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route("promotions.index") }}"><i class="bi bi-arrow-left"></i> Kembali ke Promosi &amp; Informasi</a>
        </nav>

        <header class="promo-form-header">
            <span class="promo-eyebrow"><i class="bi bi-sliders"></i> Pengaturan konten</span>
            <h1>Konfigurasi Promosi &amp; Informasi</h1>
            <p>Atur durasi bawaan dan kapan konten kedaluwarsa dibersihkan dari database serta penyimpanan gambar.</p>
        </header>

        @if (session("success"))
            <div class="promo-alert" role="status">
                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                <span>{{ session("success") }}</span>
                <button type="button" data-bs-dismiss="alert" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="promo-form-errors" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><strong>Periksa kembali konfigurasi berikut:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            </div>
        @endif

        <form class="promo-editor promo-config-editor" method="POST" action="{{ route("promotions.configuration.update") }}">
            @csrf
            @method("PUT")

            <section class="promo-editor__main">
                <div class="promo-editor-card">
                    <div class="promo-editor-card__heading">
                        <span><i class="bi bi-calendar2-week"></i></span>
                        <div><h2>Durasi bawaan konten</h2><p>Nilai awal saat pengelola membuat konten baru. Durasi tetap dapat diubah pada masing-masing konten.</p></div>
                    </div>
                    <div class="promo-duration-fields">
                        <label class="promo-field">
                            <span>Durasi <b>*</b></span>
                            <input name="default_duration_value" type="number" min="1" max="9999" required value="{{ old("default_duration_value", $configuration->default_duration_value) }}">
                        </label>
                        <label class="promo-field">
                            <span>Satuan <b>*</b></span>
                            <select name="default_duration_unit" required>
                                @foreach (["hour" => "Jam", "day" => "Hari", "month" => "Bulan", "year" => "Tahun"] as $value => $label)
                                    <option value="{{ $value }}" @selected(old("default_duration_unit", $configuration->default_duration_unit) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </div>

                <div class="promo-editor-card">
                    <div class="promo-editor-card__heading">
                        <span><i class="bi bi-trash3"></i></span>
                        <div><h2>Pembersihan otomatis</h2><p>Konten yang sudah diterbitkan atau diarsipkan akan dihapus permanen setelah melewati waktu berakhir dan masa tenggang.</p></div>
                    </div>

                    <label class="promo-radio-option">
                        <input type="radio" name="auto_delete_enabled" value="1" @checked((string) old("auto_delete_enabled", (int) $configuration->auto_delete_enabled) === "1")>
                        <span><i class="bi bi-lightning-charge-fill"></i><span><strong>Aktifkan penghapusan otomatis</strong><small>Data konten dan file gambarnya dibersihkan oleh sistem setiap menit.</small></span></span>
                    </label>
                    <label class="promo-radio-option">
                        <input type="radio" name="auto_delete_enabled" value="0" @checked((string) old("auto_delete_enabled", (int) $configuration->auto_delete_enabled) === "0")>
                        <span><i class="bi bi-pause-circle"></i><span><strong>Nonaktifkan penghapusan otomatis</strong><small>Konten kedaluwarsa tetap tersimpan sampai dihapus manual.</small></span></span>
                    </label>

                    <div class="promo-config-divider"></div>
                    <div class="promo-duration-fields">
                        <label class="promo-field">
                            <span>Masa tenggang <b>*</b></span>
                            <input name="delete_grace_value" type="number" min="0" max="9999" required value="{{ old("delete_grace_value", $configuration->delete_grace_value) }}">
                            <small>Gunakan 0 agar konten dihapus segera setelah berakhir.</small>
                        </label>
                        <label class="promo-field">
                            <span>Satuan <b>*</b></span>
                            <select name="delete_grace_unit" required>
                                @foreach (["hour" => "Jam", "day" => "Hari", "month" => "Bulan", "year" => "Tahun"] as $value => $label)
                                    <option value="{{ $value }}" @selected(old("delete_grace_unit", $configuration->delete_grace_unit) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <p class="promo-warning-note"><i class="bi bi-exclamation-triangle-fill"></i><span>Penghapusan bersifat permanen. Draf tidak ikut dibersihkan agar pekerjaan yang belum diterbitkan tetap aman.</span></p>
                </div>
            </section>

            <aside class="promo-editor__side">
                <div class="promo-editor-card promo-config-summary">
                    <div class="promo-editor-card__heading">
                        <span><i class="bi bi-database-check"></i></span>
                        <div><h2>Yang dibersihkan</h2><p>Sistem menjaga data konten tetap ringkas.</p></div>
                    </div>
                    <ul>
                        <li><i class="bi bi-check2-circle"></i><span>Data konten dari database</span></li>
                        <li><i class="bi bi-check2-circle"></i><span>File gambar pada penyimpanan publik</span></li>
                        <li><i class="bi bi-shield-check"></i><span>Draf konten tetap dipertahankan</span></li>
                    </ul>
                </div>
                <div class="promo-form-actions">
                    <a href="{{ route("promotions.index") }}">Batal</a>
                    <button type="submit"><i class="bi bi-check2-circle"></i>Simpan konfigurasi</button>
                </div>
            </aside>
        </form>
    </div>
@endsection
