<!doctype html>
<html lang="en" class="minimal-theme">

    @include("template.epasien.head")
    @stack("style")

    <body>

        @include("template.epasien.pwa-splash")

        <div id="ep-navigation-loader" class="ep-navigation-loader" hidden aria-hidden="true">
            <div class="ep-navigation-loader__panel" role="status" aria-live="polite">
                <span class="ep-navigation-loader__spinner" aria-hidden="true"></span>
                <span class="ep-navigation-loader__text">Memuat halaman...</span>
            </div>
        </div>

        <!--start wrapper-->
        <div class="wrapper">
            <!--start top header-->
            @include("template.epasien.navbar")
            <!--end top header-->

            <!--start sidebar -->
            @include("template.epasien.sidebar")
            <!--end sidebar -->

            <!--start content-->
            <main class="page-content">
                @yield("content")
            </main>
            <!--end page main-->

            @include("template.epasien.extra")

        </div>
        <!--end wrapper-->

        @include("template.epasien.js")
        @stack("script")

    </body>

</html>
