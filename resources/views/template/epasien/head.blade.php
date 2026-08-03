<head>
    @php
        $usesDataTables = request()->routeIs("users.*", "roles.*", "permissions.*");
        $usesSelect2 = request()->routeIs("users.*", "roles.*", "daftarOnline.index");
        $usesDashboardCharts = request()->routeIs("dashboard");
        $usesAccessControl = request()->routeIs("users.*", "roles.*", "permissions.*", "roleConfiguration.*");
        $usesSweetAlert = request()->routeIs("profile.*", "users.*", "roles.*", "permissions.*", "daftarOnline.*");
    @endphp

    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">

    <link href="{{ asset("epasien/assets/css/navigation-loader.css") }}" rel="stylesheet" />
    <script src="{{ asset("epasien/assets/js/navigation-loader.js") }}" defer></script>

    @if ($usesAccessControl)
        <link href="{{ asset("epasien/assets/css/access-control.css") }}" rel="stylesheet" />
    @elseif ($usesSweetAlert)
        <link href="{{ asset("epasien/assets/css/sweetalert-premium.css") }}" rel="stylesheet" />
    @endif
    <link href="{{ asset("epasien/assets/css/sidebar-premium.css") }}" rel="stylesheet" />

    <title>@yield("title", "E-Pasien")</title>
</head>
