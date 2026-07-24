<!doctype html>
<html lang="en" class="minimal-theme">

    @include("template.epasien.head")
    @stack("style")

    <body>

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
