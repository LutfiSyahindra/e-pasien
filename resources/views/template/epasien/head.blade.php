<head>
    @php
        $usesDataTables = request()->routeIs("users.*", "roles.*", "permissions.*");
        $usesSelect2 = request()->routeIs("users.*", "roles.*", "roleConfiguration.*", "daftarOnline.index");
        $usesDashboardCharts = request()->routeIs("dashboard");
        $usesAccessControl = request()->routeIs("users.*", "roles.*", "permissions.*", "roleConfiguration.*");
        $usesSweetAlert = request()->routeIs("profile.*", "users.*", "roles.*", "permissions.*", "daftarOnline.*");
    @endphp

    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="epasien-user-id" content="{{ auth()->id() }}">
        <meta name="epasien-notifications-url" content="{{ route("notifications.index") }}">
        <meta name="epasien-notifications-read-url" content="{{ url("/e-pasien/notifications") }}">
        <meta name="epasien-notifications-read-all-url" content="{{ route("notifications.readAll") }}">
        <meta name="epasien-push-config-url" content="{{ route("push.config") }}">
        <meta name="epasien-push-subscription-url" content="{{ route("push.store") }}">
        <meta name="epasien-notification-sound-url" content="{{ asset("landing/assets/sound/notif.mp3") }}">
    @endauth
    <meta name="theme-color" content="#0b766d">
    <link rel="manifest" href="{{ asset("manifest.webmanifest") }}">
    <link rel="icon" href="{{ asset("epasien/assets/images/favicon-32x32.png") }}" type="image/png" />
    <!--plugins-->
    <link href="{{ asset("epasien/assets/plugins/simplebar/css/simplebar.css") }}" rel="stylesheet" />
    <link href="{{ asset("epasien/assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css") }}" rel="stylesheet" />
    <link href="{{ asset("epasien/assets/plugins/metismenu/css/metisMenu.min.css") }}" rel="stylesheet" />
    @if ($usesDashboardCharts)
        <link href="{{ asset("epasien/assets/plugins/vectormap/jquery-jvectormap-2.0.2.css") }}" rel="stylesheet" />
    @endif
    @if ($usesDataTables)
        <link href="{{ asset("epasien/assets/plugins/datatable/css/dataTables.bootstrap5.min.css") }}" rel="stylesheet" />
    @endif
    @if ($usesSelect2)
        <link href="{{ asset("epasien/assets/plugins/select2/css/select2.min.css") }}" rel="stylesheet" />
        <link href="{{ asset("epasien/assets/plugins/select2/css/select2-bootstrap4.css") }}" rel="stylesheet" />
    @endif
    <!-- Bootstrap CSS -->
    <link href="{{ asset("epasien/assets/css/bootstrap.min.css") }}" rel="stylesheet" />
    <link href="{{ asset("epasien/assets/css/bootstrap-extended.css") }}" rel="stylesheet" />
    <link href="{{ asset("epasien/assets/css/style.css") }}" rel="stylesheet" />
    <link href="{{ asset("epasien/assets/css/icons.css") }}" rel="stylesheet">

    <link href="{{ asset("epasien/assets/css/navigation-loader.css") }}" rel="stylesheet" />
    <script src="{{ asset("epasien/assets/js/navigation-loader.js") }}" defer></script>

    @if ($usesAccessControl)
        <link href="{{ asset("epasien/assets/css/access-control.css") }}" rel="stylesheet" />
    @elseif ($usesSweetAlert)
        <link href="{{ asset("epasien/assets/css/sweetalert-premium.css") }}" rel="stylesheet" />
    @endif
    <link href="{{ asset("epasien/assets/css/sidebar-premium.css") }}" rel="stylesheet" />
    <link href="{{ asset("epasien/assets/css/notification-center.css") }}" rel="stylesheet" />

    <title>@yield("title", "E-Pasien")</title>
    @if (file_exists(public_path("build/manifest.json")) || file_exists(public_path("hot")))
        @vite("resources/js/app.js")
    @endif
</head>
