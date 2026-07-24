<!DOCTYPE html>
<html lang="en">

    @include("template.landing.head")
    @stack("style")

    <body>

        <!-- header area start -->
        @include("template.landing.navbar")
        <!-- header area end -->
        <!-- header area end -->

        @yield("content")

        <!-- header area start -->
        <!-- rts footer area start -->
        @include("template.landing.footer")
        <!-- rts footer area end -->
        <!-- header area end -->

        <!-- header style two -->
        @include("template.landing.sidebar")
        <!-- header style two End -->
        @include("template.landing.loading")

        @include("template.landing.js")
        @stack("script")
    </body>

</html>
