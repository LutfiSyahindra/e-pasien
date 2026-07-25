<!doctype html>
<html lang="id" class="minimal-theme">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Login - E-Pasien</title>

        <link rel="icon" href="{{ asset("epasien/assets/images/favicon-32x32.png") }}" type="image/png" />

        <link href="{{ asset("epasien/assets/css/bootstrap.min.css") }}" rel="stylesheet">
        <link href="{{ asset("epasien/assets/css/bootstrap-extended.css") }}" rel="stylesheet">
        <link href="{{ asset("epasien/assets/css/style.css") }}" rel="stylesheet">
        <link href="{{ asset("epasien/assets/css/icons.css") }}" rel="stylesheet">
        <link href="{{ asset("epasien/assets/css/pace.min.css") }}" rel="stylesheet">

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">

        <style>
            :root {
                --login-ink: #102033;
                --login-muted: #64748b;
                --login-line: #d9e6f2;
                --login-blue: #0862c7;
                --login-cyan: #05a8c8;
                --login-teal: #16b6a6;
                --login-green: #21a66f;
                --login-red: #dc2626;
                --login-surface: rgba(255, 255, 255, .94);
                --login-shadow: 0 28px 70px rgba(15, 45, 89, .18);
            }

            * {
                letter-spacing: 0;
            }

            body {
                min-height: 100vh;
                margin: 0;
                color: var(--login-ink);
                font-family: Inter, "Segoe UI", Roboto, Arial, sans-serif;
                background:
                    linear-gradient(145deg, rgba(8, 98, 199, .1) 0%, rgba(22, 182, 166, .08) 38%, rgba(255, 255, 255, .78) 100%),
                    linear-gradient(90deg, rgba(220, 38, 38, .07), rgba(255, 255, 255, 0) 34%),
                    #f5f9fc;
            }

            .wrapper {
                min-height: 100vh;
            }

            .authentication-content {
                min-height: 100vh;
                display: flex;
                align-items: center;
                padding: 14px;
            }

            .login-shell {
                width: min(980px, 100%);
                margin: 0 auto;
            }

            .login-frame {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(380px, .9fr);
                min-height: 560px;
                overflow: hidden;
                border: 1px solid rgba(255, 255, 255, .82);
                border-radius: 8px;
                background: var(--login-surface);
                box-shadow: var(--login-shadow);
                backdrop-filter: blur(18px);
            }

            .login-visual {
                position: relative;
                min-height: 560px;
                isolation: isolate;
                background: #0a63c7;
            }

            .login-visual::before,
            .login-visual::after {
                content: "";
                position: absolute;
                inset: 0;
                z-index: 1;
                pointer-events: none;
            }

            .login-visual::before {
                background:
                    linear-gradient(180deg, rgba(6, 43, 91, .08), rgba(6, 43, 91, .82)),
                    linear-gradient(120deg, rgba(4, 168, 200, .52), rgba(10, 99, 199, .2) 44%, rgba(16, 32, 51, .66));
            }

            .login-visual::after {
                inset: auto 0 0;
                height: 46%;
                background: linear-gradient(180deg, rgba(4, 14, 29, 0), rgba(4, 14, 29, .76));
            }

            .login-visual img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                object-position: center;
                filter: saturate(1.08) contrast(1.02);
            }

            .visual-topline {
                position: absolute;
                top: 22px;
                left: 24px;
                right: 24px;
                z-index: 2;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                color: rgba(255, 255, 255, .94);
            }

            .visual-badge {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                min-height: 36px;
                padding: 8px 12px;
                border: 1px solid rgba(255, 255, 255, .3);
                border-radius: 8px;
                background: rgba(255, 255, 255, .14);
                color: #fff;
                font-size: 12px;
                font-weight: 700;
                line-height: 1;
                text-transform: uppercase;
                backdrop-filter: blur(14px);
            }

            .visual-badge i {
                font-size: 16px;
                color: #7ff0df;
            }

            .visual-copy {
                position: absolute;
                left: 30px;
                right: 30px;
                bottom: 30px;
                z-index: 2;
                color: #fff;
            }

            .visual-copy h1 {
                max-width: 520px;
                margin: 0 0 10px;
                color: #fff;
                font-size: clamp(28px, 3vw, 40px);
                font-weight: 800;
                line-height: 1.05;
            }

            .visual-copy p {
                max-width: 520px;
                margin: 0;
                color: rgba(255, 255, 255, .82);
                font-size: 14px;
                line-height: 1.55;
            }

            .login-form-panel {
                display: flex;
                align-items: center;
                justify-content: center;
                min-width: 0;
                padding: 28px 34px;
                background:
                    linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(250, 253, 255, .95)),
                    #fff;
            }

            .login-form-inner {
                width: min(100%, 370px);
            }

            .login-brand {
                display: inline-flex;
                align-items: center;
                width: 100%;
                margin-bottom: 18px;
            }

            .login-brand img {
                width: min(206px, 82%);
                height: auto;
                object-fit: contain;
            }

            .login-eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 8px;
                color: var(--login-teal);
                font-size: 12px;
                font-weight: 800;
                text-transform: uppercase;
            }

            .login-eyebrow::before {
                content: "";
                width: 28px;
                height: 2px;
                border-radius: 8px;
                background: linear-gradient(90deg, var(--login-red), var(--login-teal));
            }

            .login-title {
                margin: 0;
                color: var(--login-ink);
                font-size: 27px;
                font-weight: 800;
                line-height: 1.14;
            }

            .login-subtitle {
                margin: 6px 0 16px;
                color: var(--login-muted);
                font-size: 13px;
                line-height: 1.5;
            }

            .premium-alert {
                border: 0;
                border-left: 4px solid var(--login-green);
                border-radius: 8px;
                background: #ecfdf5;
                color: #166534;
                font-weight: 600;
            }

            .login-field {
                margin-bottom: 10px;
            }

            .login-field label {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 6px;
                color: #24364c;
                font-size: 13px;
                font-weight: 800;
            }

            .login-input-wrap {
                position: relative;
            }

            .login-input-icon {
                position: absolute;
                top: 50%;
                left: 15px;
                z-index: 2;
                display: grid;
                width: 22px;
                height: 22px;
                place-items: center;
                color: var(--login-cyan);
                transform: translateY(-50%);
            }

            .premium-input {
                width: 100%;
                min-height: 44px;
                border: 1px solid var(--login-line);
                border-radius: 8px;
                background: #f8fbfe;
                color: var(--login-ink);
                font-size: 15px;
                font-weight: 600;
                box-shadow: none;
                transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
            }

            .premium-input.ps-5 {
                padding-left: 48px !important;
            }

            .premium-input:focus {
                border-color: rgba(5, 168, 200, .72);
                background: #fff;
                box-shadow: 0 0 0 4px rgba(5, 168, 200, .13);
            }

            .premium-input.is-invalid {
                border-color: #ef4444;
            }

            .password-input {
                padding-right: 52px;
            }

            .password-toggle {
                position: absolute;
                top: 50%;
                right: 7px;
                z-index: 3;
                display: grid;
                width: 34px;
                height: 34px;
                place-items: center;
                border: 0;
                border-radius: 8px;
                background: transparent;
                color: #58708d;
                transform: translateY(-50%);
                transition: background .2s ease, color .2s ease;
            }

            .password-toggle:hover,
            .password-toggle:focus {
                background: rgba(5, 168, 200, .1);
                color: var(--login-blue);
                outline: 0;
            }

            .login-options {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 18px;
                margin: 2px 0 14px;
                font-size: 13px;
            }

            .captcha-row {
                display: grid;
                grid-template-columns: 124px minmax(0, 1fr);
                gap: 8px;
                align-items: center;
            }

            .captcha-question {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                min-height: 44px;
                border: 1px dashed rgba(5, 168, 200, .48);
                border-radius: 8px;
                background: linear-gradient(135deg, #eefaf8, #f4fbff);
                color: var(--login-blue);
                font-family: Consolas, "Courier New", monospace;
                font-size: 17px;
                font-weight: 900;
                line-height: 1;
            }

            .captcha-input {
                text-transform: uppercase;
            }

            .captcha-question i {
                color: var(--login-teal);
                font-size: 17px;
            }

            .login-options .form-check {
                min-height: 24px;
                margin: 0;
                color: #51657f;
                font-weight: 700;
            }

            .login-options .form-check-input {
                width: 2.4em;
                height: 1.2em;
                margin-top: .12em;
                border-color: #b9c9d9;
                box-shadow: none;
            }

            .login-options .form-check-input:checked {
                border-color: var(--login-teal);
                background-color: var(--login-teal);
            }

            .forgot-link {
                color: var(--login-blue);
                font-weight: 800;
                text-decoration: none;
                white-space: nowrap;
            }

            .forgot-link:hover {
                color: var(--login-cyan);
            }

            .login-submit {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                min-height: 46px;
                width: 100%;
                border: 0;
                border-radius: 8px;
                background: linear-gradient(90deg, var(--login-blue), var(--login-cyan) 52%, var(--login-teal));
                color: #fff;
                font-size: 15px;
                font-weight: 800;
                box-shadow: 0 16px 30px rgba(8, 98, 199, .28);
                transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
            }

            .login-submit:hover,
            .login-submit:focus {
                color: #fff;
                filter: saturate(1.08);
                box-shadow: 0 18px 34px rgba(8, 98, 199, .34);
                transform: translateY(-1px);
            }

            .login-submit i {
                font-size: 18px;
                line-height: 1;
            }

            .login-error {
                margin-top: 7px;
                color: #dc2626;
                font-size: 12px;
                font-weight: 700;
            }

            @media (max-width: 991.98px) {
                .authentication-content {
                    padding: 14px;
                }

                .login-frame {
                    display: block;
                    min-height: 0;
                }

                .login-visual {
                    display: none;
                }

                .login-form-panel {
                    min-height: calc(100vh - 28px);
                    padding: 24px 22px;
                }
            }

            @media (max-width: 575.98px) {
                .login-form-panel {
                    padding: 22px 18px;
                }

                .login-brand {
                    margin-bottom: 16px;
                }

                .login-brand img {
                    width: min(194px, 86%);
                }

                .login-title {
                    font-size: 25px;
                }

                .captcha-row {
                    grid-template-columns: 1fr;
                    gap: 8px;
                }

                .login-options {
                    align-items: flex-start;
                    flex-direction: column;
                    gap: 12px;
                }
            }
        </style>
    </head>

    <body>

        <div class="wrapper">

            <main class="authentication-content">
                <div class="login-shell">
                    <div class="login-frame">
                        <section class="login-visual" aria-label="Akses aman E-Pasien">
                            <img src="{{ asset("epasien/assets/images/error/login-img.jpg") }}" alt="Ilustrasi akses aman E-Pasien">

                            <div class="visual-topline">
                                <span class="visual-badge">
                                    <i class="bi bi-shield-check"></i>
                                    Akses Aman
                                </span>
                            </div>

                            <div class="visual-copy">
                                <h1>Pelayanan pasien dalam satu pintu digital.</h1>
                                <p>
                                    Masuk untuk mengelola akses layanan, data operasional, dan aktivitas harian E-Pasien.
                                </p>

                            </div>
                        </section>

                        <section class="login-form-panel" aria-label="Form login E-Pasien">
                            <div class="login-form-inner">
                                <a class="login-brand" href="{{ url("/") }}" aria-label="Beranda E-Pasien">
                                    <img src="{{ asset("landing/assets/imagesArsy/epasien.png") }}" alt="E-Pasien">
                                </a>

                                <span class="login-eyebrow">Selamat Datang</span>
                                <h2 class="login-title">Masuk ke akun Anda</h2>
                                <p class="login-subtitle">Gunakan akun resmi untuk melanjutkan ke dashboard E-Pasien.</p>

                                @if (session("status"))
                                    <div class="alert premium-alert">
                                        {{ session("status") }}
                                    </div>
                                @endif

                                <form method="POST" action="{{ route("login") }}">
                                    @csrf

                                    <div class="login-field">
                                        <label for="email">Alamat Email</label>
                                        <div class="login-input-wrap">
                                            <span class="login-input-icon" aria-hidden="true">
                                                <i class="bi bi-envelope-fill"></i>
                                            </span>
                                            <input id="email" type="email" name="email" value="{{ old("email") }}"
                                                class="form-control premium-input ps-5 @error("email") is-invalid @enderror"
                                                placeholder="nama@email.com" required autofocus autocomplete="username">
                                        </div>

                                        @error("email")
                                            <div class="login-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="login-field">
                                        <label for="password">Password</label>
                                        <div class="login-input-wrap">
                                            <span class="login-input-icon" aria-hidden="true">
                                                <i class="bi bi-lock-fill"></i>
                                            </span>
                                            <input id="password" type="password" name="password"
                                                class="form-control premium-input password-input ps-5 @error("password") is-invalid @enderror"
                                                placeholder="Masukkan password" required autocomplete="current-password">
                                            <button class="password-toggle" type="button" aria-label="Tampilkan password"
                                                data-password-toggle>
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>

                                        @error("password")
                                            <div class="login-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="login-field">
                                        <label for="captcha_answer">Captcha</label>
                                        <div class="captcha-row">
                                            <div class="captcha-question" aria-label="Pertanyaan captcha">
                                                <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                                                <span>{{ $captchaQuestion ?? "" }}</span>
                                            </div>
                                            <input id="captcha_answer" type="text" name="captcha_answer"
                                                class="form-control premium-input captcha-input @error("captcha_answer") is-invalid @enderror"
                                                placeholder="Masukkan kode" required maxlength="5" autocomplete="off">
                                        </div>

                                        @error("captcha_answer")
                                            <div class="login-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="login-options">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                            <label class="form-check-label" for="remember">Ingat saya</label>
                                        </div>

                                        @if (Route::has("password.request"))
                                            <a class="forgot-link" href="{{ route("password.request") }}">
                                                Lupa password?
                                            </a>
                                        @endif
                                    </div>

                                    <button class="login-submit" type="submit">
                                        <span>Masuk</span>
                                        <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </section>
                    </div>
                </div>
            </main>

        </div>

        <script src="{{ asset("epasien/assets/js/jquery.min.js") }}"></script>
        <script src="{{ asset("epasien/assets/js/bootstrap.bundle.min.js") }}"></script>
        <script src="{{ asset("epasien/assets/js/pace.min.js") }}"></script>
        <script>
            document.querySelectorAll("[data-password-toggle]").forEach(function (button) {
                button.addEventListener("click", function () {
                    var input = document.getElementById("password");
                    var icon = button.querySelector("i");
                    var isPassword = input.type === "password";

                    input.type = isPassword ? "text" : "password";
                    button.setAttribute("aria-label", isPassword ? "Sembunyikan password" : "Tampilkan password");
                    icon.className = isPassword ? "bi bi-eye-slash" : "bi bi-eye";
                });
            });
        </script>

    </body>

</html>
