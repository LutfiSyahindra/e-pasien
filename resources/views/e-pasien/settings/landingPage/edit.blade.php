@extends('template.epasien.appPasien')

@section('title', 'Informasi Landing Page | E-Pasien')

@push('style')
    <link href="{{ versioned_asset('epasien/assets/css/landing-page-settings.css') }}" rel="stylesheet">
@endpush

@section('content')
    @php($content = old('content', $landingContent))
    <div class="landing-editor">
        <nav class="landing-editor-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i> Dashboard</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i><span>Pengaturan</span>
            <i class="bi bi-chevron-right" aria-hidden="true"></i><span>Landing Page &amp; Data</span>
        </nav>

        <header class="landing-editor-hero">
            <div>
                <span class="landing-editor-eyebrow"><i class="bi bi-window-stack"></i> Halaman publik rumah sakit</span>
                <h1>Informasi Landing Page</h1>
                <p>Perbarui informasi yang dilihat pengunjung, dari layanan hingga kontak rumah sakit.</p>
                <small><i class="bi bi-clock-history"></i>
                    {{ $landingUpdatedAt ? 'Terakhir disimpan '.$landingUpdatedAt->timezone('Asia/Jakarta')->format('d/m/Y H:i').' WIB' : 'Menggunakan informasi bawaan rumah sakit' }}
                </small>
            </div>
            <a href="{{ route('landingPage.index') }}" target="_blank" rel="noopener noreferrer" class="landing-editor-preview">
                <i class="bi bi-box-arrow-up-right"></i> Lihat landing page
            </a>
        </header>

        @if (session('status'))
            <div class="landing-editor-alert is-success" role="status"><i class="bi bi-check-circle"></i> {{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="landing-editor-alert is-danger" role="alert">
                <strong>Perubahan belum tersimpan.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="landing-editor-layout">
            <aside class="landing-editor-navigation" aria-label="Bagian yang dapat diedit">
                <strong>Bagian halaman</strong>
                @foreach (config('landing-page.sections') as $key => $section)
                    <a href="#editor-{{ $key }}">{{ $section['title'] }}</a>
                @endforeach
                <a href="#editor-images">Logo &amp; gambar</a>
                @foreach (config('landing-page.collections') as $key => $collection)
                    <a href="#editor-{{ $key }}">{{ $collection['title'] }}</a>
                @endforeach
                <div class="landing-editor-note">
                    <i class="bi bi-lightning-charge"></i>
                    <p>Informasi langsung tampil setelah Anda menekan Simpan perubahan.</p>
                </div>
                @can('EPASIEN.SETTINGS.JADWAL_DOKTER')
                    <a href="{{ route('doctorScheduleSettings.index') }}"><i class="bi bi-calendar2-check"></i> Atur jadwal dokter</a>
                    <a href="{{ route('doctorPhotoSettings.index') }}"><i class="bi bi-person-bounding-box"></i> Kelola foto dokter</a>
                @endcan
            </aside>

            <form method="POST" action="{{ route('landingPageSettings.update') }}" enctype="multipart/form-data"
                id="landingPageEditor" class="landing-editor-form">
                @csrf
                @method('PUT')
                @foreach (config('landing-page.sections') as $key => $section)
                    <details id="editor-{{ $key }}" class="landing-editor-panel" @if ($loop->index < 2 || collect($errors->keys())->contains(fn ($error) => str_starts_with($error, 'content.'.$key.'.'))) open @endif>
                        <summary><span>{{ $section['title'] }}</span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
                        <div class="landing-editor-fields">
                            @foreach ($section['fields'] as $fieldKey => $field)
                                @include('e-pasien.settings.landingPage.field', [
                                    'name' => "content[{$key}][{$fieldKey}]",
                                    'id' => "editor-{$key}-{$fieldKey}",
                                    'errorKey' => "content.{$key}.{$fieldKey}",
                                    'value' => $content[$key][$fieldKey] ?? '',
                                ])
                            @endforeach
                        </div>
                        @if ($key === 'contact')
                            <p class="landing-editor-help">Nomor telepon di bagian kontak dan footer mengikuti informasi di sini. Gunakan awalan 0 atau kode negara, misalnya +62.</p>
                        @elseif ($key === 'location')
                            <p class="landing-editor-help">Masukkan URL peta dari Google Maps, tanpa kode iframe. URL embed harus dimulai dengan https://www.google.com/maps.</p>
                        @endif
                    </details>
                @endforeach

                <details id="editor-images" class="landing-editor-panel" @if (collect($errors->keys())->contains(fn ($error) => str_starts_with($error, 'images.'))) open @endif>
                    <summary><span>Logo &amp; gambar</span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
                    <div class="landing-editor-images">
                        @foreach (config('landing-page.images') as $key => $image)
                            <div class="landing-editor-image" data-image-editor>
                                <img src="{{ $landingImages[$key] }}" alt="{{ $image['label'] }}" data-image-preview
                                    data-current-src="{{ $landingImages[$key] }}" data-default-src="{{ asset($image['default']) }}">
                                <label for="editor-image-{{ $key }}">{{ $image['label'] }}</label>
                                <input id="editor-image-{{ $key }}" type="file" name="images[{{ $key }}]" accept="image/jpeg,image/png,image/webp" data-image-input>
                                <label class="landing-editor-checkbox"><input type="checkbox" name="reset_images[{{ $key }}]" value="1" data-image-reset @checked(old('reset_images.'.$key))> Gunakan gambar bawaan</label>
                                @error('images.'.$key)<small class="landing-editor-error">{{ $message }}</small>@enderror
                            </div>
                        @endforeach
                    </div>
                    <p class="landing-editor-help">JPG, PNG, atau WEBP, maksimal 4 MB dan 6000 × 6000 piksel per gambar. Banner disarankan menggunakan foto horizontal. Pilih ulang file jika formulir menampilkan kesalahan.</p>
                </details>

                @foreach (config('landing-page.collections') as $key => $collection)
                    <details id="editor-{{ $key }}" class="landing-editor-panel" data-editor-collection="{{ $key }}" data-min="{{ $collection['min'] }}" @if (collect($errors->keys())->contains(fn ($error) => str_starts_with($error, 'content.'.$key))) open @endif>
                        <summary><span>{{ $collection['title'] }} <em data-collection-count>{{ count($content[$key] ?? []) }}</em></span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
                        <div class="landing-editor-collection-body">
                            <p class="landing-editor-help">Edit, tambah, hapus, atau ubah urutan item. Maksimal 30 item. {{ $collection['min'] ? 'Sisakan minimal satu item.' : 'Daftar ini boleh dikosongkan.' }}</p>
                            <div data-editor-rows>
                                @foreach ($content[$key] ?? [] as $index => $row)
                                    @include('e-pasien.settings.landingPage.row')
                                @endforeach
                            </div>
                            <button type="button" class="landing-editor-add" data-row-add><i class="bi bi-plus-lg"></i> Tambah item</button>
                            <template data-row-template>
                                @include('e-pasien.settings.landingPage.row', ['index' => '__INDEX__', 'row' => []])
                            </template>
                        </div>
                    </details>
                @endforeach

                <div class="landing-editor-save">
                    <div><strong>Simpan informasi halaman</strong><small>Perubahan berlaku untuk seluruh pengunjung.</small></div>
                    <button type="submit"><i class="bi bi-check2-circle"></i> Simpan perubahan</button>
                </div>
                <p class="landing-editor-help" data-editor-status role="status" aria-live="polite"></p>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ versioned_asset('epasien/assets/js/landing-page-settings.js') }}" defer></script>
@endpush
