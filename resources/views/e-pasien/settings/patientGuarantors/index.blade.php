@extends("template.epasien.appPasien")

@section("title", "Konfigurasi Penjamin Pasien | E-Pasien")

@push("style")
    <link href="{{ versioned_asset("epasien/assets/css/patient-guarantor-settings.css") }}"
        rel="stylesheet" />
@endpush

@section("content")
    @php
        $normalizedSelectedCodes = collect($selectedCodes)
            ->map(fn ($code) => strtoupper(trim((string) $code)))
            ->all();
        $selectedCount = collect($guarantors)
            ->filter(fn ($guarantor) => in_array(strtoupper($guarantor["kd_pj"]), $normalizedSelectedCodes, true))
            ->count();
        $blockedCount = max(count($guarantors) - $selectedCount, 0);
    @endphp

    <main class="guarantor-settings-page">
        <nav class="guarantor-settings-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route("dashboard") }}" aria-label="Kembali ke Dashboard">
                <i class="bi bi-house-door"></i>
            </a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span>Pengaturan</span>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span>Pendaftaran Online</span>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span class="active">Penjamin Pasien</span>
        </nav>

        <section class="guarantor-settings-hero">
            <div class="guarantor-settings-heading">
                <span class="guarantor-settings-icon"><i class="bi bi-shield-check"></i></span>
                <div>
                    <span class="guarantor-settings-eyebrow">Kontrol pendaftaran pasien</span>
                    <h1>Konfigurasi Penjamin Pasien</h1>
                    <p>Tentukan penjamin yang dapat dipilih pasien saat membuat pendaftaran online.</p>
                </div>
            </div>
            <div class="guarantor-settings-live">
                <span><i class="bi bi-lightning-charge-fill"></i> Berlaku langsung</span>
                <small>Petugas pendaftaran tetap dapat melihat seluruh penjamin.</small>
                @can("EPASIEN.MENU.PENDAFTARAN_ONLINE")
                    <a href="{{ route("daftarOnline.index") }}" target="_blank" rel="noopener">
                        Lihat halaman daftar <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                @endcan
            </div>
        </section>

        @if (session("status"))
            <div class="guarantor-settings-alert success" role="status">
                <span><i class="bi bi-check2-circle"></i></span>
                <div>
                    <strong>Konfigurasi tersimpan</strong>
                    <p>{{ session("status") }}</p>
                </div>
            </div>
        @endif

        @if ($connectionError)
            <div class="guarantor-settings-alert danger" role="alert">
                <span><i class="bi bi-cloud-slash"></i></span>
                <div>
                    <strong>Data belum dapat ditampilkan</strong>
                    <p>{{ $connectionError }}</p>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="guarantor-settings-alert danger" role="alert">
                <span><i class="bi bi-exclamation-triangle"></i></span>
                <div>
                    <strong>Konfigurasi belum dapat disimpan</strong>
                    <p>{{ $errors->first() }}</p>
                </div>
            </div>
        @endif

        <section class="guarantor-settings-stats" aria-label="Ringkasan konfigurasi penjamin">
            <article>
                <span class="purple"><i class="bi bi-database-check"></i></span>
                <div>
                    <small>Penjamin tersedia</small>
                    <strong id="guarantorTotalCount">{{ number_format(count($guarantors), 0, ",", ".") }}</strong>
                    <em>dari data rumah sakit</em>
                </div>
            </article>
            <article>
                <span class="green"><i class="bi bi-check2-circle"></i></span>
                <div>
                    <small>Dapat dipilih pasien</small>
                    <strong id="guarantorSelectedStat">{{ number_format($selectedCount, 0, ",", ".") }}</strong>
                    <em>penjamin diaktifkan</em>
                </div>
            </article>
            <article>
                <span class="orange"><i class="bi bi-shield-x"></i></span>
                <div>
                    <small>Tidak ditampilkan</small>
                    <strong id="guarantorBlockedStat">{{ number_format($blockedCount, 0, ",", ".") }}</strong>
                    <em>khusus akses petugas</em>
                </div>
            </article>
        </section>

        <form id="patientGuarantorConfigurationForm" method="POST"
            action="{{ route("patientGuarantorSettings.update") }}">
            @csrf
            @method("PUT")

            <section class="guarantor-settings-panel">
                <div class="guarantor-settings-panel-head">
                    <div>
                        <span><i class="bi bi-ui-checks-grid"></i> Whitelist penjamin</span>
                        <h2>Pilih Penjamin untuk Pasien</h2>
                        <p>Penjamin yang dicentang akan muncul pada kolom Penjamin di formulir pendaftaran pasien.</p>
                    </div>
                    <span class="guarantor-settings-result-count">
                        <strong id="guarantorSelectedCount">{{ $selectedCount }}</strong>
                        dari {{ count($guarantors) }} dipilih
                    </span>
                </div>

                <div class="guarantor-settings-toolbar">
                    <label class="guarantor-settings-search" for="guarantorSettingsSearch">
                        <i class="bi bi-search"></i>
                        <input id="guarantorSettingsSearch" type="search"
                            placeholder="Cari nama atau kode penjamin..." maxlength="80"
                            autocomplete="off" @disabled($connectionError)>
                        <kbd>/</kbd>
                    </label>
                    <div class="guarantor-settings-bulk-actions">
                        <button type="button" id="selectAllGuarantors" @disabled($connectionError || empty($guarantors))>
                            <i class="bi bi-check2-square"></i> Pilih semua
                        </button>
                        <button type="button" id="clearAllGuarantors" @disabled($connectionError || empty($guarantors))>
                            <i class="bi bi-square"></i> Hapus pilihan
                        </button>
                    </div>
                </div>

                @if (! $connectionError && empty($guarantors))
                    <div class="guarantor-settings-empty">
                        <span><i class="bi bi-shield-slash"></i></span>
                        <strong>Penjamin belum tersedia</strong>
                        <p>Belum ada data penjamin yang dapat dikonfigurasi dari database rumah sakit.</p>
                    </div>
                @elseif (! $connectionError)
                    <div id="guarantorSettingsGrid" class="guarantor-settings-grid">
                        @foreach ($guarantors as $guarantor)
                            @php
                                $code = trim((string) $guarantor["kd_pj"]);
                                $name = trim((string) $guarantor["png_jawab"]);
                                $isBpjs = strtoupper($code) === "BPJ";
                            @endphp
                            <label class="guarantor-option {{ $isBpjs ? "bpjs" : "" }}"
                                data-guarantor-option
                                data-search="{{ Illuminate\Support\Str::lower($code." ".$name) }}">
                                <input type="checkbox" name="guarantor_codes[]" value="{{ $code }}"
                                    @checked(in_array(strtoupper($code), $normalizedSelectedCodes, true))>
                                <span class="guarantor-option-check"><i class="bi bi-check-lg"></i></span>
                                <span class="guarantor-option-icon">
                                    <i class="bi {{ $isBpjs ? "bi-heart-pulse" : "bi-shield" }}"></i>
                                </span>
                                <span class="guarantor-option-copy">
                                    <strong>{{ $name }}</strong>
                                    <small>Kode {{ $code }}</small>
                                </span>
                                @if ($isBpjs)
                                    <span class="guarantor-option-badge">BPJS</span>
                                @endif
                            </label>
                        @endforeach
                    </div>

                    <div id="guarantorSearchEmpty" class="guarantor-settings-empty compact" hidden>
                        <span><i class="bi bi-search"></i></span>
                        <strong>Penjamin tidak ditemukan</strong>
                        <p>Coba gunakan nama atau kode penjamin lain.</p>
                    </div>
                @endif
            </section>

            <aside class="guarantor-settings-note">
                <span><i class="bi bi-info-circle"></i></span>
                <div>
                    <strong>Yang perlu diketahui</strong>
                    <p>Pembatasan ini hanya berlaku bagi pasien. Role petugas pendaftaran tetap memiliki akses ke seluruh penjamin untuk melayani pasien.</p>
                </div>
            </aside>

            <div class="guarantor-settings-savebar">
                <div id="guarantorSettingsState" class="guarantor-settings-state"
                    data-default-title="{{ $configurationSaved ? "Konfigurasi sudah tersimpan" : "Menggunakan konfigurasi bawaan" }}"
                    data-default-hint="{{ $configurationSaved ? "Ubah pilihan untuk mengaktifkan tombol simpan." : "Semua penjamin tersedia sampai konfigurasi pertama disimpan." }}">
                    <span><i class="bi {{ $configurationSaved ? "bi-check-circle" : "bi-info-circle" }}"></i></span>
                    <div>
                        <strong>{{ $configurationSaved ? "Konfigurasi sudah tersimpan" : "Menggunakan konfigurasi bawaan" }}</strong>
                        <small>{{ $configurationSaved ? "Ubah pilihan untuk mengaktifkan tombol simpan." : "Semua penjamin tersedia sampai konfigurasi pertama disimpan." }}</small>
                    </div>
                </div>
                <button id="saveGuarantorConfiguration" type="submit"
                    @disabled($connectionError || empty($guarantors))>
                    <i class="bi bi-floppy"></i>
                    <span>Simpan Konfigurasi</span>
                </button>
            </div>
        </form>
    </main>
@endsection

@push("script")
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const form = document.getElementById("patientGuarantorConfigurationForm");
            if (!form) return;

            const checkboxes = Array.from(form.querySelectorAll('input[name="guarantor_codes[]"]'));
            const optionCards = Array.from(form.querySelectorAll("[data-guarantor-option]"));
            const searchInput = document.getElementById("guarantorSettingsSearch");
            const searchEmpty = document.getElementById("guarantorSearchEmpty");
            const selectedCount = document.getElementById("guarantorSelectedCount");
            const selectedStat = document.getElementById("guarantorSelectedStat");
            const blockedStat = document.getElementById("guarantorBlockedStat");
            const selectAll = document.getElementById("selectAllGuarantors");
            const clearAll = document.getElementById("clearAllGuarantors");
            const saveButton = document.getElementById("saveGuarantorConfiguration");
            const state = document.getElementById("guarantorSettingsState");
            const initialSelection = checkboxes.filter((checkbox) => checkbox.checked)
                .map((checkbox) => checkbox.value).sort().join("|");
            const savePermanentlyDisabled = Boolean(saveButton?.disabled);
            const defaultStateTitle = state?.dataset.defaultTitle || "Konfigurasi sudah tersimpan";
            const defaultStateHint = state?.dataset.defaultHint || "Ubah pilihan untuk mengaktifkan tombol simpan.";

            const updateState = () => {
                const selected = checkboxes.filter((checkbox) => checkbox.checked).length;
                const currentSelection = checkboxes.filter((checkbox) => checkbox.checked)
                    .map((checkbox) => checkbox.value).sort().join("|");
                const changed = currentSelection !== initialSelection;

                if (selectedCount) selectedCount.textContent = selected;
                if (selectedStat) selectedStat.textContent = selected.toLocaleString("id-ID");
                if (blockedStat) blockedStat.textContent = (checkboxes.length - selected).toLocaleString("id-ID");

                optionCards.forEach((card) => {
                    const checkbox = card.querySelector('input[type="checkbox"]');
                    card.classList.toggle("selected", Boolean(checkbox?.checked));
                });

                if (saveButton) {
                    saveButton.disabled = savePermanentlyDisabled || !changed || selected === 0;
                    saveButton.classList.toggle("ready", changed && selected > 0);
                }

                if (state) {
                    state.classList.toggle("changed", changed && selected > 0);
                    state.classList.toggle("invalid", selected === 0);

                    if (selected === 0) {
                        state.innerHTML = '<span><i class="bi bi-exclamation-triangle"></i></span><div><strong>Pilih minimal satu penjamin</strong><small>Pasien memerlukan setidaknya satu pilihan untuk dapat mendaftar.</small></div>';
                    } else if (changed) {
                        state.innerHTML = '<span><i class="bi bi-pencil-square"></i></span><div><strong>Ada perubahan yang belum disimpan</strong><small>Periksa pilihan lalu simpan konfigurasi.</small></div>';
                    } else {
                        state.innerHTML = '<span><i class="bi bi-check-circle"></i></span><div><strong>' + defaultStateTitle + '</strong><small>' + defaultStateHint + '</small></div>';
                    }
                }
            };

            const setVisibleCheckboxes = (checked) => {
                optionCards.filter((card) => !card.hidden).forEach((card) => {
                    const checkbox = card.querySelector('input[type="checkbox"]');
                    if (checkbox) checkbox.checked = checked;
                });
                updateState();
            };

            checkboxes.forEach((checkbox) => checkbox.addEventListener("change", updateState));
            selectAll?.addEventListener("click", () => setVisibleCheckboxes(true));
            clearAll?.addEventListener("click", () => setVisibleCheckboxes(false));

            searchInput?.addEventListener("input", function () {
                const term = this.value.trim().toLocaleLowerCase("id-ID");
                let visible = 0;
                optionCards.forEach((card) => {
                    const matches = !term || (card.dataset.search || "").includes(term);
                    card.hidden = !matches;
                    if (matches) visible += 1;
                });
                if (searchEmpty) searchEmpty.hidden = visible !== 0;
            });

            document.addEventListener("keydown", function (event) {
                if (event.key === "/" && document.activeElement?.tagName !== "INPUT") {
                    event.preventDefault();
                    searchInput?.focus();
                }
            });

            form.addEventListener("submit", function (event) {
                if (checkboxes.some((checkbox) => checkbox.checked)) return;
                event.preventDefault();
                state?.classList.add("invalid");
                if (state) {
                    state.innerHTML = '<span><i class="bi bi-exclamation-triangle"></i></span><div><strong>Pilih minimal satu penjamin</strong><small>Pasien memerlukan setidaknya satu pilihan untuk dapat mendaftar.</small></div>';
                }
            });

            updateState();
        });
    </script>
@endpush
