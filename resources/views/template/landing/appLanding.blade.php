<!DOCTYPE html>
<html lang="id">

    @include("template.landing.head")
    @stack("style")

    <body class="landing-page">

        @include("template.landing.navbar")

        <main id="top">
            @yield("content")
        </main>

        @include("template.landing.footer")

        @include("template.landing.sidebar")
        @include("template.landing.loading")

        @include("template.landing.js")
        @stack("script")
    </body>

</html>
