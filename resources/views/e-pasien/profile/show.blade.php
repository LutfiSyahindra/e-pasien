@extends("template.epasien.appPasien")

@section("title", "Profil | E-Pasien")

@push("style")
    <style>
        .profile-page {
            --profile-ink: #101828;
            --profile-muted: #667085;
            --profile-line: #e5eaf2;
            --profile-soft: #f7f9fc;
            --profile-surface: #ffffff;
            --profile-teal: #0f766e;
            --profile-blue: #2563eb;
            --profile-gold: #b7791f;
            --profile-emerald: #059669;
            --profile-rose: #e11d48;
            --profile-shadow: 0 18px 46px rgba(15, 23, 42, .08);
            color: var(--profile-ink);
        }

        .profile-shell {
            margin: 0 auto;
            max-width: 1180px;
        }

        .profile-toolbar {
            align-items: center;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .profile-title {
            color: var(--profile-ink);
            font-size: 1.35rem;
            font-weight: 900;
            letter-spacing: 0;
            margin: 0;
        }

        .profile-subtitle {
            color: var(--profile-muted);
            font-size: .94rem;
            margin: 3px 0 0;
        }

        .profile-action,
        .profile-view-tab,
        .profile-group-tab,
        .profile-photo-button,
        .profile-photo-remove,
        .profile-crop-reset,
        .profile-crop-cancel,
        .profile-crop-submit,
        .profile-save-button {
            align-items: center;
            border-radius: 8px;
            display: inline-flex;
            font-weight: 900;
            gap: 8px;
            justify-content: center;
            letter-spacing: 0;
            text-decoration: none;
            white-space: nowrap;
        }

        .profile-action {
            background: var(--profile-surface);
            border: 1px solid var(--profile-line);
            color: var(--profile-ink);
            font-size: .92rem;
            min-height: 42px;
            padding: 0 13px;
        }

        .profile-action:hover {
            background: var(--profile-soft);
            color: var(--profile-ink);
        }

        .profile-alert {
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .profile-empty {
            align-items: flex-start;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            padding: 16px;
        }

        .profile-empty i {
            color: #c2410c;
            font-size: 1.35rem;
        }

        .profile-empty strong {
            display: block;
            margin-bottom: 3px;
        }

        .profile-empty p {
            color: #9a3412;
            margin: 0;
        }

        .profile-hero {
            background: var(--profile-surface);
            border: 1px solid var(--profile-line);
            border-radius: 8px;
            box-shadow: var(--profile-shadow);
            display: grid;
            gap: 22px;
            grid-template-columns: minmax(0, 1fr) 312px;
            overflow: hidden;
            padding: 22px;
        }

        .profile-identity {
            align-items: center;
            display: flex;
            gap: 20px;
            min-width: 0;
        }

        .profile-avatar-block {
            align-items: center;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            gap: 10px;
        }

        .profile-avatar-wrap {
            background: #f8fafc;
            border: 4px solid #fff;
            border-radius: 50%;
            box-shadow: 0 14px 30px rgba(15, 23, 42, .14);
            height: 118px;
            overflow: hidden;
            position: relative;
            width: 118px;
        }

        .profile-avatar-wrap img {
            height: 100%;
            object-fit: cover;
            width: 100%;
        }

        .profile-presence {
            background: #22c55e;
            border: 3px solid #fff;
            border-radius: 50%;
            bottom: 10px;
            height: 17px;
            position: absolute;
            right: 12px;
            width: 17px;
        }

        .profile-photo-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: center;
        }

        .profile-photo-input {
            display: none;
        }

        .profile-photo-button,
        .profile-photo-remove {
            border: 1px solid var(--profile-line);
            cursor: pointer;
            font-size: .8rem;
            min-height: 34px;
            padding: 0 10px;
        }

        .profile-photo-button {
            background: #ecfdf5;
            color: var(--profile-teal);
        }

        .profile-photo-remove {
            background: #fff1f2;
            color: var(--profile-rose);
        }

        .profile-crop-modal .modal-content {
            border: 0;
            border-radius: 8px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .2);
            overflow: hidden;
        }

        .profile-crop-modal .modal-header,
        .profile-crop-modal .modal-footer {
            border-color: var(--profile-line);
            padding: 16px 18px;
        }

        .profile-crop-modal .modal-title {
            color: var(--profile-ink);
            font-size: 1rem;
            font-weight: 900;
            letter-spacing: 0;
        }

        .profile-crop-layout {
            align-items: start;
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(0, 1fr) 178px;
        }

        .profile-crop-stage {
            aspect-ratio: 1 / 1;
            background: #111827;
            border-radius: 8px;
            cursor: grab;
            justify-self: center;
            max-width: min(100%, 62vh, 520px);
            max-height: min(62vh, 520px);
            overflow: hidden;
            position: relative;
            touch-action: none;
            user-select: none;
            width: 100%;
        }

        .profile-crop-stage.is-dragging {
            cursor: grabbing;
        }

        .profile-crop-stage img {
            left: 50%;
            max-width: none;
            pointer-events: none;
            position: absolute;
            top: 50%;
            transform-origin: center center;
            user-select: none;
        }

        .profile-crop-stage::after {
            background:
                linear-gradient(to right, rgba(255, 255, 255, .34) 1px, transparent 1px) 33.33% 0 / 33.33% 100%,
                linear-gradient(to right, rgba(255, 255, 255, .34) 1px, transparent 1px) 66.66% 0 / 33.33% 100%,
                linear-gradient(to bottom, rgba(255, 255, 255, .34) 1px, transparent 1px) 0 33.33% / 100% 33.33%,
                linear-gradient(to bottom, rgba(255, 255, 255, .34) 1px, transparent 1px) 0 66.66% / 100% 33.33%;
            box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .92), inset 0 0 0 999px rgba(15, 23, 42, .02);
            content: "";
            inset: 0;
            pointer-events: none;
            position: absolute;
        }

        .profile-crop-side {
            display: grid;
            gap: 14px;
        }

        .profile-crop-preview {
            align-items: center;
            background: var(--profile-soft);
            border: 1px solid var(--profile-line);
            border-radius: 8px;
            display: grid;
            gap: 10px;
            justify-items: center;
            padding: 14px;
        }

        .profile-crop-preview canvas {
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .12);
            height: 118px;
            width: 118px;
        }

        .profile-crop-preview span,
        .profile-crop-control label {
            color: var(--profile-muted);
            font-size: .78rem;
            font-weight: 900;
        }

        .profile-crop-control {
            display: grid;
            gap: 8px;
        }

        .profile-crop-range {
            accent-color: var(--profile-teal);
            width: 100%;
        }

        .profile-crop-reset,
        .profile-crop-cancel,
        .profile-crop-submit {
            border: 1px solid var(--profile-line);
            font-size: .88rem;
            min-height: 40px;
            padding: 0 14px;
        }

        .profile-crop-reset,
        .profile-crop-cancel {
            background: #fff;
            color: var(--profile-ink);
        }

        .profile-crop-submit {
            background: var(--profile-teal);
            border-color: var(--profile-teal);
            color: #fff;
        }

        .profile-crop-submit:disabled {
            cursor: not-allowed;
            opacity: .6;
        }

        .profile-eyebrow {
            color: var(--profile-teal);
            font-size: .76rem;
            font-weight: 900;
            letter-spacing: 0;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .profile-name {
            color: var(--profile-ink);
            font-size: 1.95rem;
            font-weight: 900;
            line-height: 1.12;
            margin: 0;
            overflow-wrap: anywhere;
        }

        .profile-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }

        .profile-meta-item {
            align-items: center;
            background: var(--profile-soft);
            border: 1px solid var(--profile-line);
            border-radius: 8px;
            color: #344054;
            display: inline-flex;
            font-size: .86rem;
            font-weight: 800;
            gap: 8px;
            min-height: 36px;
            padding: 0 11px;
        }

        .profile-health {
            background: #f8fafc;
            border: 1px solid var(--profile-line);
            border-radius: 8px;
            padding: 16px;
        }

        .profile-score-row {
            align-items: center;
            display: flex;
            gap: 13px;
        }

        .profile-score-ring {
            align-items: center;
            background: conic-gradient(var(--profile-teal) var(--score), #e2e8f0 0);
            border-radius: 50%;
            display: flex;
            flex: 0 0 68px;
            height: 68px;
            justify-content: center;
            position: relative;
            width: 68px;
        }

        .profile-score-ring::before {
            background: #fff;
            border-radius: 50%;
            content: "";
            inset: 7px;
            position: absolute;
        }

        .profile-score-ring span {
            color: var(--profile-ink);
            font-size: .96rem;
            font-weight: 900;
            position: relative;
        }

        .profile-health strong {
            color: var(--profile-ink);
            display: block;
            font-size: 1rem;
            line-height: 1.2;
        }

        .profile-health small {
            color: var(--profile-muted);
            display: block;
            margin-top: 4px;
        }

        .profile-progress {
            background: #e2e8f0;
            border-radius: 999px;
            height: 8px;
            margin-top: 15px;
            overflow: hidden;
        }

        .profile-progress span {
            background: linear-gradient(90deg, var(--profile-teal), var(--profile-blue));
            border-radius: inherit;
            display: block;
            height: 100%;
        }

        .profile-health-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 14px;
        }

        .profile-health-stat {
            background: #fff;
            border: 1px solid var(--profile-line);
            border-radius: 8px;
            padding: 10px;
        }

        .profile-health-stat span {
            color: var(--profile-muted);
            display: block;
            font-size: .76rem;
            font-weight: 800;
        }

        .profile-health-stat strong {
            font-size: 1rem;
            margin-top: 2px;
        }

        .profile-view-tabs {
            background: #eef2f7;
            border: 1px solid var(--profile-line);
            border-radius: 8px;
            display: grid;
            gap: 6px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin: 16px 0;
            padding: 6px;
        }

        .profile-view-tab {
            background: transparent;
            border: 0;
            color: #475467;
            min-height: 42px;
            padding: 0 12px;
            transition: background .16s ease, box-shadow .16s ease, color .16s ease;
        }

        .profile-view-tab.active {
            background: #fff;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .08);
            color: var(--profile-teal);
        }

        .profile-view[hidden] {
            display: none;
        }

        .profile-summary-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: minmax(0, 1fr) 340px;
        }

        .profile-kpi-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .profile-kpi {
            background: var(--profile-surface);
            border: 1px solid var(--profile-line);
            border-radius: 8px;
            min-height: 104px;
            padding: 15px;
        }

        .profile-kpi-top {
            align-items: center;
            display: flex;
            gap: 10px;
        }

        .profile-kpi-icon {
            align-items: center;
            border-radius: 8px;
            color: #fff;
            display: inline-flex;
            flex: 0 0 36px;
            height: 36px;
            justify-content: center;
            width: 36px;
        }

        .profile-kpi.tone-blue .profile-kpi-icon {
            background: var(--profile-blue);
        }

        .profile-kpi.tone-emerald .profile-kpi-icon {
            background: var(--profile-emerald);
        }

        .profile-kpi.tone-amber .profile-kpi-icon {
            background: var(--profile-gold);
        }

        .profile-kpi.tone-rose .profile-kpi-icon {
            background: var(--profile-rose);
        }

        .profile-kpi small,
        .profile-detail-label,
        .profile-missing-label {
            color: var(--profile-muted);
            display: block;
            font-size: .8rem;
            font-weight: 800;
        }

        .profile-kpi strong {
            color: var(--profile-ink);
            display: block;
            font-size: .98rem;
            overflow-wrap: anywhere;
        }

        .profile-panel {
            background: var(--profile-surface);
            border: 1px solid var(--profile-line);
            border-radius: 8px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .04);
            overflow: hidden;
        }

        .profile-panel-header {
            align-items: center;
            border-bottom: 1px solid var(--profile-line);
            display: flex;
            gap: 11px;
            padding: 16px 18px;
        }

        .profile-panel-icon {
            align-items: center;
            background: #eff6ff;
            border-radius: 8px;
            color: var(--profile-blue);
            display: inline-flex;
            flex: 0 0 38px;
            height: 38px;
            justify-content: center;
            width: 38px;
        }

        .profile-panel-title {
            color: var(--profile-ink);
            font-size: 1rem;
            font-weight: 900;
            margin: 0;
        }

        .profile-panel-subtitle {
            color: var(--profile-muted);
            margin: 3px 0 0;
        }

        .profile-missing-list {
            display: grid;
            gap: 1px;
            margin: 0;
            padding: 0;
        }

        .profile-missing-item {
            align-items: center;
            background: #fff;
            display: flex;
            gap: 11px;
            min-height: 58px;
            padding: 12px 16px;
        }

        .profile-missing-item + .profile-missing-item {
            border-top: 1px solid var(--profile-line);
        }

        .profile-missing-icon {
            align-items: center;
            background: #fff7ed;
            border-radius: 8px;
            color: var(--profile-gold);
            display: inline-flex;
            flex: 0 0 34px;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .profile-missing-value {
            color: var(--profile-ink);
            display: block;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .profile-data-layout {
            display: grid;
            gap: 16px;
            grid-template-columns: 292px minmax(0, 1fr);
        }

        .profile-group-tabs {
            background: var(--profile-surface);
            border: 1px solid var(--profile-line);
            border-radius: 8px;
            display: grid;
            gap: 8px;
            padding: 10px;
        }

        .profile-group-tab {
            background: #fff;
            border: 1px solid transparent;
            color: #475467;
            justify-content: flex-start;
            min-height: 58px;
            padding: 0 12px;
            text-align: left;
            width: 100%;
        }

        .profile-group-tab.active {
            background: #f0fdfa;
            border-color: rgba(15, 118, 110, .22);
            color: var(--profile-teal);
        }

        .profile-group-tab i {
            align-items: center;
            background: #eef2f7;
            border-radius: 8px;
            display: inline-flex;
            flex: 0 0 34px;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .profile-group-tab.active i {
            background: #ccfbf1;
        }

        .profile-group-name {
            display: block;
            font-size: .9rem;
            line-height: 1.15;
        }

        .profile-group-count {
            color: var(--profile-muted);
            display: block;
            font-size: .76rem;
            font-weight: 800;
            margin-top: 3px;
        }

        .profile-data-panel {
            background: var(--profile-surface);
            border: 1px solid var(--profile-line);
            border-radius: 8px;
            min-width: 0;
            overflow: hidden;
        }

        .profile-filter {
            align-items: center;
            border-bottom: 1px solid var(--profile-line);
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(0, 1fr) auto;
            padding: 14px;
        }

        .profile-search {
            align-items: center;
            background: var(--profile-soft);
            border: 1px solid var(--profile-line);
            border-radius: 8px;
            display: flex;
            gap: 9px;
            min-height: 42px;
            padding: 0 12px;
        }

        .profile-search i {
            color: var(--profile-muted);
        }

        .profile-search input {
            background: transparent;
            border: 0;
            color: var(--profile-ink);
            font-weight: 700;
            min-width: 0;
            outline: 0;
            width: 100%;
        }

        .profile-search-count {
            color: var(--profile-muted);
            font-size: .82rem;
            font-weight: 900;
            white-space: nowrap;
        }

        .profile-group-panel[hidden] {
            display: none;
        }

        .profile-detail-grid {
            background: var(--profile-line);
            display: grid;
            gap: 1px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .profile-detail-item {
            background: #fff;
            min-height: 86px;
            padding: 14px 16px;
        }

        .profile-detail-item.is-hidden {
            display: none;
        }

        .profile-detail-label {
            align-items: center;
            display: flex;
            gap: 8px;
            margin-bottom: 6px;
        }

        .profile-detail-value {
            color: var(--profile-ink);
            font-size: .95rem;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .profile-detail-value.empty {
            color: #98a2b3;
            font-weight: 800;
        }

        .profile-no-result {
            align-items: center;
            color: var(--profile-muted);
            display: flex;
            font-weight: 800;
            gap: 9px;
            justify-content: center;
            min-height: 132px;
            padding: 22px;
            text-align: center;
        }

        .profile-no-result[hidden] {
            display: none;
        }

        .profile-account-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .profile-form-body {
            padding: 18px;
        }

        .profile-form-body .form-label {
            color: #344054;
            font-weight: 900;
        }

        .profile-form-body .form-control {
            border-color: #d9e0eb;
            border-radius: 8px;
            min-height: 44px;
        }

        .profile-form-body .form-control:focus {
            border-color: rgba(15, 118, 110, .55);
            box-shadow: 0 0 0 .2rem rgba(15, 118, 110, .12);
        }

        .profile-save-button {
            background: linear-gradient(135deg, var(--profile-teal), var(--profile-blue));
            border: 0;
            color: #fff;
            font-size: .92rem;
            min-height: 42px;
            padding: 0 16px;
        }

        .profile-save-button:hover {
            color: #fff;
            filter: brightness(.98);
        }

        @media (max-width: 1199px) {
            .profile-hero,
            .profile-summary-grid,
            .profile-data-layout,
            .profile-account-grid {
                grid-template-columns: 1fr;
            }

            .profile-group-tabs {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 767px) {
            .profile-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .profile-hero {
                padding: 18px;
            }

            .profile-identity {
                flex-direction: column;
                text-align: center;
            }

            .profile-name {
                font-size: 1.55rem;
            }

            .profile-meta {
                justify-content: center;
            }

            .profile-health-grid,
            .profile-kpi-grid,
            .profile-detail-grid {
                grid-template-columns: 1fr;
            }

            .profile-view-tabs {
                display: flex;
                overflow-x: auto;
            }

            .profile-view-tab {
                flex: 0 0 auto;
                min-width: 148px;
            }

            .profile-group-tabs {
                display: flex;
                overflow-x: auto;
            }

            .profile-group-tab {
                flex: 0 0 228px;
            }

            .profile-filter {
                grid-template-columns: 1fr;
            }

            .profile-action,
            .profile-save-button {
                width: 100%;
            }

            .profile-crop-layout {
                grid-template-columns: 1fr;
            }

            .profile-crop-side {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @media (max-width: 480px) {
            .profile-meta-item {
                justify-content: center;
                width: 100%;
            }

            .profile-score-row {
                align-items: flex-start;
            }

            .profile-panel-header {
                align-items: flex-start;
            }
        }
    </style>
@endpush

@section("content")
    @php
        $roleNames = $user->roles->pluck("name");
        $displayName = data_get($patient, "nm_pasien") ?: $user->name;
        $medicalRecordNumber = data_get($patient, "no_rkm_medis") ?: ($user->username ?: "-");
        $defaultAvatar = asset("epasien/assets/images/avatars/avatar-1.png");
        $detailGroups = collect($patientGroups)->values();
        $detailItems = $detailGroups->flatMap(function (array $group): \Illuminate\Support\Collection {
            return collect($group["items"] ?? [])->map(function (array $item) use ($group): array {
                return $item + ["group" => $group["title"] ?? "Data pasien"];
            });
        });
        $filledDetailCount = $detailItems->filter(fn (array $item): bool => trim((string) ($item["value"] ?? "")) !== "")->count();
        $missingDetailCount = max($detailItems->count() - $filledDetailCount, 0);
        $missingDetailItems = $detailItems
            ->reject(fn (array $item): bool => trim((string) ($item["value"] ?? "")) !== "")
            ->take(5)
            ->values();
        $hasAccountErrors = $errors->has("name") || $errors->has("email") || $errors->getBag("updatePassword")->isNotEmpty();
        $initialProfileView = $hasAccountErrors ? "account" : "summary";
    @endphp

    <div class="profile-page" data-initial-view="{{ $initialProfileView }}">
        <div class="profile-shell">
            <div class="profile-toolbar">
                <div>
                    <h4 class="profile-title">Profil Saya</h4>
                    <p class="profile-subtitle">Ringkasan pasien, detail terarah, dan pengaturan akun.</p>
                </div>
                <a class="profile-action" href="{{ route("dashboard") }}">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
            </div>

            @if (session("status") === "profile-updated")
                <div class="alert alert-success profile-alert" role="alert">
                    <i class="bi bi-check-circle me-2"></i>Informasi akun berhasil diperbarui.
                </div>
            @elseif (session("status") === "profile-photo-updated")
                <div class="alert alert-success profile-alert" role="alert">
                    <i class="bi bi-camera me-2"></i>Foto profil berhasil diperbarui.
                </div>
            @elseif (session("status") === "profile-photo-deleted")
                <div class="alert alert-success profile-alert" role="alert">
                    <i class="bi bi-trash3 me-2"></i>Foto profil berhasil dihapus.
                </div>
            @elseif (session("status") === "password-updated")
                <div class="alert alert-success profile-alert" role="alert">
                    <i class="bi bi-shield-check me-2"></i>Password berhasil diperbarui.
                </div>
            @endif

            @error("profile_photo")
                <div class="alert alert-danger profile-alert" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>{{ $message }}
                </div>
            @enderror

            @error("profile_photo_cropped")
                <div class="alert alert-danger profile-alert" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>{{ $message }}
                </div>
            @enderror

            @if (! $patient)
                <div class="profile-empty">
                    <i class="bi bi-database-x"></i>
                    <div>
                        <strong>Data pasien belum ditemukan.</strong>
                        <p>Username akun ini belum cocok dengan `no_rkm_medis` pada tabel pasien atau koneksi data pasien sedang tidak tersedia.</p>
                    </div>
                </div>
            @endif

            <section class="profile-hero">
                <div class="profile-identity">
                    <div class="profile-avatar-block">
                        <div class="profile-avatar-wrap">
                            <img src="{{ $user->profile_photo_url }}" alt="Foto {{ $displayName }}" width="118" height="118" decoding="async" onerror="this.onerror=null;this.src='{{ $defaultAvatar }}';">
                            <span class="profile-presence"></span>
                        </div>
                        <div class="profile-photo-actions">
                            <form method="POST" action="{{ route("profile.photo.update") }}" enctype="multipart/form-data">
                                @csrf
                                <label class="profile-photo-button" for="profile_photo">
                                    <i class="bi bi-camera-fill"></i>
                                    Ganti Foto
                                </label>
                                <input class="profile-photo-input" id="profile_photo" name="profile_photo" type="file" accept="image/png,image/jpeg,image/webp">
                                <input id="profile_photo_cropped" name="profile_photo_cropped" type="hidden">
                            </form>

                            @if ($user->profile_photo_path)
                                <form method="POST" action="{{ route("profile.photo.destroy") }}">
                                    @csrf
                                    @method("DELETE")
                                    <button class="profile-photo-remove" type="submit">
                                        <i class="bi bi-trash3"></i>
                                        Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="profile-eyebrow">Akun Pasien</div>
                        <h1 class="profile-name">{{ $displayName }}</h1>
                        <div class="profile-meta">
                            <span class="profile-meta-item">
                                <i class="bi bi-upc-scan"></i>
                                RM {{ $medicalRecordNumber }}
                            </span>
                            <span class="profile-meta-item">
                                <i class="bi bi-person-badge"></i>
                                {{ $roleNames->isNotEmpty() ? $roleNames->implode(", ") : "Belum ada role" }}
                            </span>
                            <span class="profile-meta-item">
                                <i class="bi bi-envelope"></i>
                                {{ $user->email }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="profile-health">
                    <div class="profile-score-row">
                        <div class="profile-score-ring" style="--score: {{ $patientCompletion }}%">
                            <span>{{ $patientCompletion }}%</span>
                        </div>
                        <div>
                            <strong>Kelengkapan Data</strong>
                            <small>{{ $filledDetailCount }} dari {{ $detailItems->count() }} kolom profil terisi</small>
                        </div>
                    </div>
                    <div class="profile-progress" aria-hidden="true">
                        <span style="width: {{ $patientCompletion }}%"></span>
                    </div>
                    <div class="profile-health-grid">
                        <div class="profile-health-stat">
                            <span>Terisi</span>
                            <strong>{{ $filledDetailCount }}</strong>
                        </div>
                        <div class="profile-health-stat">
                            <span>Kosong</span>
                            <strong>{{ $missingDetailCount }}</strong>
                        </div>
                    </div>
                </div>
            </section>

            <div class="profile-view-tabs" role="tablist" aria-label="Navigasi profil">
                <button class="profile-view-tab active" type="button" role="tab" aria-selected="true" data-profile-tab="summary">
                    <i class="bi bi-grid-1x2"></i>
                    Ringkasan
                </button>
                <button class="profile-view-tab" type="button" role="tab" aria-selected="false" data-profile-tab="details">
                    <i class="bi bi-folder2-open"></i>
                    Data Pasien
                </button>
                <button class="profile-view-tab" type="button" role="tab" aria-selected="false" data-profile-tab="account">
                    <i class="bi bi-person-gear"></i>
                    Akun
                </button>
            </div>

            <section class="profile-view" data-profile-view="summary">
                <div class="profile-summary-grid">
                    <div class="profile-kpi-grid">
                        @foreach ($patientOverview as $overview)
                            <article class="profile-kpi tone-{{ $overview["tone"] }}">
                                <div class="profile-kpi-top">
                                    <span class="profile-kpi-icon">
                                        <i class="bi {{ $overview["icon"] }}"></i>
                                    </span>
                                    <div>
                                        <small>{{ $overview["label"] }}</small>
                                        <strong>{{ $overview["value"] ?: "Belum tersedia" }}</strong>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <section class="profile-panel">
                        <div class="profile-panel-header">
                            <span class="profile-panel-icon">
                                <i class="bi bi-clipboard2-check"></i>
                            </span>
                            <div>
                                <h5 class="profile-panel-title">Prioritas Data</h5>
                                <p class="profile-panel-subtitle">{{ $missingDetailCount }} kolom belum terisi</p>
                            </div>
                        </div>
                        <div class="profile-missing-list">
                            @forelse ($missingDetailItems as $item)
                                <div class="profile-missing-item">
                                    <span class="profile-missing-icon">
                                        <i class="bi {{ $item["icon"] }}"></i>
                                    </span>
                                    <div>
                                        <span class="profile-missing-label">{{ $item["group"] }}</span>
                                        <span class="profile-missing-value">{{ $item["label"] }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="profile-missing-item">
                                    <span class="profile-missing-icon">
                                        <i class="bi bi-check2-circle"></i>
                                    </span>
                                    <div>
                                        <span class="profile-missing-label">Status</span>
                                        <span class="profile-missing-value">Data utama sudah lengkap</span>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </section>
                </div>
            </section>

            <section class="profile-view" data-profile-view="details" hidden>
                <div class="profile-data-layout">
                    <div class="profile-group-tabs" role="tablist" aria-label="Kategori data pasien">
                        @foreach ($detailGroups as $group)
                            @php
                                $groupKey = "profile-group-" . $loop->index;
                                $groupItems = collect($group["items"] ?? []);
                                $groupFilledCount = $groupItems->filter(fn (array $item): bool => trim((string) ($item["value"] ?? "")) !== "")->count();
                            @endphp
                            <button class="profile-group-tab {{ $loop->first ? "active" : "" }}" type="button" role="tab" aria-selected="{{ $loop->first ? "true" : "false" }}" data-profile-group="{{ $groupKey }}">
                                <i class="bi {{ $group["icon"] }}"></i>
                                <span>
                                    <span class="profile-group-name">{{ $group["title"] }}</span>
                                    <span class="profile-group-count">{{ $groupFilledCount }}/{{ $groupItems->count() }} terisi</span>
                                </span>
                            </button>
                        @endforeach
                    </div>

                    <div class="profile-data-panel">
                        <div class="profile-filter">
                            <label class="profile-search" for="profile_detail_search">
                                <i class="bi bi-search"></i>
                                <input id="profile_detail_search" type="search" placeholder="Cari data pasien" autocomplete="off">
                            </label>
                            <span class="profile-search-count" data-profile-search-count></span>
                        </div>

                        @foreach ($detailGroups as $group)
                            @php
                                $groupKey = "profile-group-" . $loop->index;
                            @endphp
                            <div class="profile-group-panel" data-profile-group-panel="{{ $groupKey }}" {{ $loop->first ? "" : "hidden" }}>
                                <div class="profile-detail-grid">
                                    @foreach ($group["items"] as $item)
                                        @php
                                            $itemValue = $item["value"] ?: "";
                                            $searchText = strtolower(($item["label"] ?? "") . " " . $itemValue);
                                        @endphp
                                        <article class="profile-detail-item" data-profile-detail-item data-search="{{ $searchText }}">
                                            <div class="profile-detail-label">
                                                <i class="bi {{ $item["icon"] }}"></i>
                                                {{ $item["label"] }}
                                            </div>
                                            <div class="profile-detail-value {{ $itemValue ? "" : "empty" }}">
                                                {{ $itemValue ?: "Belum tersedia" }}
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                                <div class="profile-no-result" data-profile-empty-state hidden>
                                    <i class="bi bi-search"></i>
                                    Data tidak ditemukan.
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="profile-view" data-profile-view="account" hidden>
                <div class="profile-account-grid">
                    <section class="profile-panel">
                        <div class="profile-panel-header">
                            <span class="profile-panel-icon">
                                <i class="bi bi-person-gear"></i>
                            </span>
                            <div>
                                <h5 class="profile-panel-title">Informasi Akun</h5>
                                <p class="profile-panel-subtitle">Nama dan email untuk akses aplikasi.</p>
                            </div>
                        </div>
                        <form class="profile-form-body" method="POST" action="{{ route("profile.update") }}">
                            @csrf
                            @method("PATCH")

                            <div class="mb-3">
                                <label class="form-label" for="name">Nama Akun</label>
                                <input class="form-control @error("name") is-invalid @enderror" id="name" name="name" type="text" value="{{ old("name", $user->name) }}" required>
                                @error("name")
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="username">Username / No. RM</label>
                                <input class="form-control" id="username" type="text" value="{{ $user->username ?: "-" }}" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="email">Email</label>
                                <input class="form-control @error("email") is-invalid @enderror" id="email" name="email" type="email" value="{{ old("email", $user->email) }}" required>
                                @error("email")
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button class="profile-save-button" type="submit">
                                <i class="bi bi-check2-circle"></i>
                                Simpan Akun
                            </button>
                        </form>
                    </section>

                    <section class="profile-panel">
                        <div class="profile-panel-header">
                            <span class="profile-panel-icon">
                                <i class="bi bi-shield-lock"></i>
                            </span>
                            <div>
                                <h5 class="profile-panel-title">Keamanan</h5>
                                <p class="profile-panel-subtitle">Perbarui password akun login.</p>
                            </div>
                        </div>
                        <form class="profile-form-body" method="POST" action="{{ route("password.update") }}">
                            @csrf
                            @method("PUT")

                            <div class="mb-3">
                                <label class="form-label" for="update_password_current_password">Password Saat Ini</label>
                                <input class="form-control @if ($errors->updatePassword->has("current_password")) is-invalid @endif" id="update_password_current_password" name="current_password" type="password" autocomplete="current-password">
                                @foreach ($errors->updatePassword->get("current_password") as $message)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @endforeach
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="update_password_password">Password Baru</label>
                                <input class="form-control @if ($errors->updatePassword->has("password")) is-invalid @endif" id="update_password_password" name="password" type="password" autocomplete="new-password">
                                @foreach ($errors->updatePassword->get("password") as $message)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @endforeach
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="update_password_password_confirmation">Konfirmasi Password Baru</label>
                                <input class="form-control @if ($errors->updatePassword->has("password_confirmation")) is-invalid @endif" id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
                                @foreach ($errors->updatePassword->get("password_confirmation") as $message)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @endforeach
                            </div>

                            <button class="profile-save-button" type="submit">
                                <i class="bi bi-key"></i>
                                Update Password
                            </button>
                        </form>
                    </section>
                </div>
            </section>
        </div>

        <div class="modal fade profile-crop-modal" id="profile_photo_crop_modal" tabindex="-1" aria-labelledby="profile_photo_crop_title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="profile_photo_crop_title">Atur Foto Profil</h5>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="profile-crop-layout">
                            <div class="profile-crop-stage" data-profile-crop-stage>
                                <img id="profile_photo_crop_image" alt="Preview foto profil">
                            </div>
                            <div class="profile-crop-side">
                                <div class="profile-crop-preview">
                                    <canvas id="profile_photo_crop_preview" width="160" height="160"></canvas>
                                    <span>Preview</span>
                                </div>
                                <div class="profile-crop-control">
                                    <label for="profile_photo_zoom">Zoom</label>
                                    <input class="profile-crop-range" id="profile_photo_zoom" type="range" min="1" max="3" step="0.01" value="1">
                                </div>
                                <button class="profile-crop-reset" type="button" data-profile-crop-reset>
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                    Reset
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="profile-crop-cancel" type="button" data-bs-dismiss="modal">Batal</button>
                        <button class="profile-crop-submit" type="button" data-profile-crop-submit disabled>
                            <i class="bi bi-check2-circle"></i>
                            Simpan Foto
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("script")
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var photoInput = document.getElementById("profile_photo");
            var profilePage = document.querySelector(".profile-page");
            var viewTabs = document.querySelectorAll("[data-profile-tab]");
            var profileViews = document.querySelectorAll("[data-profile-view]");
            var groupTabs = document.querySelectorAll("[data-profile-group]");
            var groupPanels = document.querySelectorAll("[data-profile-group-panel]");
            var detailSearch = document.getElementById("profile_detail_search");
            var searchCount = document.querySelector("[data-profile-search-count]");
            var photoCropInput = document.getElementById("profile_photo_cropped");
            var cropModalElement = document.getElementById("profile_photo_crop_modal");
            var cropStage = document.querySelector("[data-profile-crop-stage]");
            var cropImage = document.getElementById("profile_photo_crop_image");
            var cropZoom = document.getElementById("profile_photo_zoom");
            var cropPreview = document.getElementById("profile_photo_crop_preview");
            var cropReset = document.querySelector("[data-profile-crop-reset]");
            var cropSubmit = document.querySelector("[data-profile-crop-submit]");
            var cropForm = photoInput ? photoInput.form : null;
            var cropModal = cropModalElement && window.bootstrap ? new bootstrap.Modal(cropModalElement) : null;
            var cropState = {
                baseScale: 1,
                dragging: false,
                loaded: false,
                naturalHeight: 0,
                naturalWidth: 0,
                objectUrl: null,
                offsetX: 0,
                offsetY: 0,
                pointerId: null,
                stageSize: 0,
                startOffsetX: 0,
                startOffsetY: 0,
                startX: 0,
                startY: 0,
                submitting: false,
                zoom: 1
            };

            function setProfileView(viewName) {
                viewTabs.forEach(function (tab) {
                    var isActive = tab.dataset.profileTab === viewName;
                    tab.classList.toggle("active", isActive);
                    tab.setAttribute("aria-selected", isActive ? "true" : "false");
                });

                profileViews.forEach(function (view) {
                    view.hidden = view.dataset.profileView !== viewName;
                });
            }

            function activeGroupPanel() {
                return document.querySelector("[data-profile-group-panel]:not([hidden])");
            }

            function updateSearchState() {
                var panel = activeGroupPanel();

                if (! panel) {
                    return;
                }

                var query = detailSearch ? detailSearch.value.trim().toLowerCase() : "";
                var items = panel.querySelectorAll("[data-profile-detail-item]");
                var emptyState = panel.querySelector("[data-profile-empty-state]");
                var visibleCount = 0;

                items.forEach(function (item) {
                    var matches = ! query || item.dataset.search.indexOf(query) !== -1;
                    item.classList.toggle("is-hidden", ! matches);

                    if (matches) {
                        visibleCount++;
                    }
                });

                if (emptyState) {
                    emptyState.hidden = visibleCount > 0;
                }

                if (searchCount) {
                    searchCount.textContent = visibleCount + " data";
                }
            }

            function setProfileGroup(groupName) {
                groupTabs.forEach(function (tab) {
                    var isActive = tab.dataset.profileGroup === groupName;
                    tab.classList.toggle("active", isActive);
                    tab.setAttribute("aria-selected", isActive ? "true" : "false");
                });

                groupPanels.forEach(function (panel) {
                    panel.hidden = panel.dataset.profileGroupPanel !== groupName;
                });

                if (detailSearch) {
                    detailSearch.value = "";
                }

                updateSearchState();
            }

            function clamp(value, min, max) {
                return Math.min(Math.max(value, min), max);
            }

            function showProfilePhotoError(message) {
                if (window.Swal) {
                    Swal.fire({
                        icon: "error",
                        title: "Foto belum bisa dipakai",
                        text: message
                    });

                    return;
                }

                window.alert(message);
            }

            function clearCropObjectUrl() {
                if (cropState.objectUrl && window.URL) {
                    URL.revokeObjectURL(cropState.objectUrl);
                }

                cropState.objectUrl = null;
            }

            function setCropSubmitReady(isReady) {
                if (cropSubmit) {
                    cropSubmit.disabled = ! isReady;
                }
            }

            function resetCropPicker() {
                if (! cropState.submitting) {
                    if (photoInput) {
                        photoInput.value = "";
                    }

                    if (photoCropInput) {
                        photoCropInput.value = "";
                    }
                }

                clearCropObjectUrl();

                if (cropImage) {
                    cropImage.removeAttribute("src");
                    cropImage.removeAttribute("style");
                }

                if (cropStage) {
                    cropStage.classList.remove("is-dragging");
                }

                cropState.dragging = false;
                cropState.loaded = false;
                cropState.pointerId = null;
                setCropSubmitReady(false);
            }

            function isValidPhotoFile(file) {
                var validTypes = ["image/jpeg", "image/png", "image/webp"];

                if (validTypes.indexOf(file.type) === -1) {
                    showProfilePhotoError("Foto profil harus berformat JPG, JPEG, PNG, atau WEBP.");

                    return false;
                }

                if (file.size > 2 * 1024 * 1024) {
                    showProfilePhotoError("Ukuran foto profil maksimal 2 MB.");

                    return false;
                }

                return true;
            }

            function currentCropRectangle() {
                var scale = cropState.baseScale * cropState.zoom;
                var displayWidth = cropState.naturalWidth * scale;
                var displayHeight = cropState.naturalHeight * scale;
                var displayLeft = (cropState.stageSize / 2) + cropState.offsetX - (displayWidth / 2);
                var displayTop = (cropState.stageSize / 2) + cropState.offsetY - (displayHeight / 2);
                var cropWidth = Math.min(cropState.naturalWidth, cropState.stageSize / scale);
                var cropHeight = Math.min(cropState.naturalHeight, cropState.stageSize / scale);

                return {
                    height: cropHeight,
                    width: cropWidth,
                    x: clamp((0 - displayLeft) / scale, 0, cropState.naturalWidth - cropWidth),
                    y: clamp((0 - displayTop) / scale, 0, cropState.naturalHeight - cropHeight)
                };
            }

            function renderCropPreview() {
                if (! cropPreview || ! cropImage || ! cropState.loaded) {
                    return;
                }

                var context = cropPreview.getContext("2d");

                if (! context) {
                    return;
                }

                var crop = currentCropRectangle();

                context.clearRect(0, 0, cropPreview.width, cropPreview.height);
                context.fillStyle = "#ffffff";
                context.fillRect(0, 0, cropPreview.width, cropPreview.height);
                context.drawImage(
                    cropImage,
                    crop.x,
                    crop.y,
                    crop.width,
                    crop.height,
                    0,
                    0,
                    cropPreview.width,
                    cropPreview.height
                );
            }

            function updateCrop() {
                if (! cropState.loaded || ! cropStage || ! cropImage) {
                    return;
                }

                var zoomValue = cropZoom ? parseFloat(cropZoom.value) : cropState.zoom;
                cropState.zoom = clamp(isNaN(zoomValue) ? 1 : zoomValue, 1, 3);

                if (cropZoom) {
                    cropZoom.value = cropState.zoom;
                }

                var baseWidth = cropState.naturalWidth * cropState.baseScale;
                var baseHeight = cropState.naturalHeight * cropState.baseScale;
                var scaledWidth = baseWidth * cropState.zoom;
                var scaledHeight = baseHeight * cropState.zoom;
                var maxOffsetX = Math.max((scaledWidth - cropState.stageSize) / 2, 0);
                var maxOffsetY = Math.max((scaledHeight - cropState.stageSize) / 2, 0);

                cropState.offsetX = clamp(cropState.offsetX, -maxOffsetX, maxOffsetX);
                cropState.offsetY = clamp(cropState.offsetY, -maxOffsetY, maxOffsetY);

                cropImage.style.height = baseHeight + "px";
                cropImage.style.left = "calc(50% + " + cropState.offsetX + "px)";
                cropImage.style.top = "calc(50% + " + cropState.offsetY + "px)";
                cropImage.style.transform = "translate(-50%, -50%) scale(" + cropState.zoom + ")";
                cropImage.style.width = baseWidth + "px";

                renderCropPreview();
            }

            function prepareCrop(resetPosition) {
                if (! cropState.loaded || ! cropStage) {
                    return;
                }

                var cropStageRect = cropStage.getBoundingClientRect();
                cropState.stageSize = Math.max(1, Math.round(Math.min(cropStageRect.width, cropStageRect.height)));
                cropState.baseScale = Math.max(cropState.stageSize / cropState.naturalWidth, cropState.stageSize / cropState.naturalHeight);

                if (resetPosition) {
                    cropState.offsetX = 0;
                    cropState.offsetY = 0;
                    cropState.zoom = 1;

                    if (cropZoom) {
                        cropZoom.value = "1";
                    }
                }

                updateCrop();
                setCropSubmitReady(true);
            }

            function openCropModal(file) {
                if (! cropModal || ! cropStage || ! cropImage || ! cropZoom || ! cropPreview || ! photoCropInput || ! cropForm || ! window.URL) {
                    if (cropForm) {
                        cropForm.submit();
                    }

                    return;
                }

                clearCropObjectUrl();
                photoCropInput.value = "";
                cropState.loaded = false;
                cropState.submitting = false;
                setCropSubmitReady(false);

                cropState.objectUrl = URL.createObjectURL(file);
                cropImage.onload = function () {
                    cropState.loaded = true;
                    cropState.naturalHeight = cropImage.naturalHeight;
                    cropState.naturalWidth = cropImage.naturalWidth;

                    if (cropModalElement.classList.contains("show")) {
                        prepareCrop(true);
                    }
                };
                cropImage.onerror = function () {
                    showProfilePhotoError("Foto profil tidak bisa dibaca.");
                    resetCropPicker();
                };
                cropImage.src = cropState.objectUrl;
                cropModal.show();
            }

            function submitCroppedPhoto() {
                if (! cropState.loaded || ! cropImage || ! photoCropInput || ! cropForm) {
                    return;
                }

                var crop = currentCropRectangle();
                var outputCanvas = document.createElement("canvas");
                var outputContext = outputCanvas.getContext("2d");

                if (! outputContext) {
                    showProfilePhotoError("Browser belum bisa memproses crop foto.");

                    return;
                }

                outputCanvas.width = 640;
                outputCanvas.height = 640;
                outputContext.fillStyle = "#ffffff";
                outputContext.fillRect(0, 0, outputCanvas.width, outputCanvas.height);
                outputContext.drawImage(
                    cropImage,
                    crop.x,
                    crop.y,
                    crop.width,
                    crop.height,
                    0,
                    0,
                    outputCanvas.width,
                    outputCanvas.height
                );

                var dataUrl = outputCanvas.toDataURL("image/jpeg", .9);

                if (dataUrl.length > 3145728) {
                    showProfilePhotoError("Hasil crop foto profil maksimal 2 MB.");

                    return;
                }

                photoCropInput.value = dataUrl;

                if (photoInput) {
                    photoInput.value = "";
                }

                cropState.submitting = true;
                setCropSubmitReady(false);
                cropForm.submit();
            }

            if (cropModalElement) {
                cropModalElement.addEventListener("shown.bs.modal", function () {
                    prepareCrop(true);
                });

                cropModalElement.addEventListener("hidden.bs.modal", function () {
                    resetCropPicker();
                });
            }

            if (cropStage) {
                cropStage.addEventListener("pointerdown", function (event) {
                    if (! cropState.loaded) {
                        return;
                    }

                    cropState.dragging = true;
                    cropState.pointerId = event.pointerId;
                    cropState.startOffsetX = cropState.offsetX;
                    cropState.startOffsetY = cropState.offsetY;
                    cropState.startX = event.clientX;
                    cropState.startY = event.clientY;
                    cropStage.classList.add("is-dragging");
                    cropStage.setPointerCapture(event.pointerId);
                    event.preventDefault();
                });

                cropStage.addEventListener("pointermove", function (event) {
                    if (! cropState.dragging || cropState.pointerId !== event.pointerId) {
                        return;
                    }

                    cropState.offsetX = cropState.startOffsetX + event.clientX - cropState.startX;
                    cropState.offsetY = cropState.startOffsetY + event.clientY - cropState.startY;
                    updateCrop();
                });

                ["pointerup", "pointercancel"].forEach(function (eventName) {
                    cropStage.addEventListener(eventName, function (event) {
                        if (cropState.pointerId !== event.pointerId) {
                            return;
                        }

                        cropState.dragging = false;
                        cropState.pointerId = null;
                        cropStage.classList.remove("is-dragging");
                    });
                });

                cropStage.addEventListener("wheel", function (event) {
                    if (! cropState.loaded || ! cropZoom) {
                        return;
                    }

                    event.preventDefault();
                    cropZoom.value = clamp(cropState.zoom + (event.deltaY > 0 ? -0.08 : 0.08), 1, 3);
                    updateCrop();
                }, { passive: false });
            }

            if (cropZoom) {
                cropZoom.addEventListener("input", updateCrop);
            }

            if (cropReset) {
                cropReset.addEventListener("click", function () {
                    cropState.offsetX = 0;
                    cropState.offsetY = 0;

                    if (cropZoom) {
                        cropZoom.value = "1";
                    }

                    updateCrop();
                });
            }

            if (cropSubmit) {
                cropSubmit.addEventListener("click", submitCroppedPhoto);
            }

            if (photoInput) {
                photoInput.addEventListener("change", function () {
                    var file = photoInput.files && photoInput.files.length > 0 ? photoInput.files[0] : null;

                    if (! file) {
                        return;
                    }

                    if (! isValidPhotoFile(file)) {
                        photoInput.value = "";

                        return;
                    }

                    openCropModal(file);
                });
            }

            window.addEventListener("resize", function () {
                if (cropModalElement && cropModalElement.classList.contains("show")) {
                    prepareCrop(false);
                }
            });

            viewTabs.forEach(function (tab) {
                tab.addEventListener("click", function () {
                    setProfileView(tab.dataset.profileTab);
                });
            });

            groupTabs.forEach(function (tab) {
                tab.addEventListener("click", function () {
                    setProfileGroup(tab.dataset.profileGroup);
                });
            });

            if (detailSearch) {
                detailSearch.addEventListener("input", updateSearchState);
            }

            var initialView = profilePage ? profilePage.dataset.initialView : "summary";
            setProfileView(initialView || "summary");

            if (groupTabs.length > 0) {
                setProfileGroup(groupTabs[0].dataset.profileGroup);
            }
        });
    </script>
@endpush
