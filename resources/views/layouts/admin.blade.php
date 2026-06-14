<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Admin - {{ config('app.name') }}</title>

        <!-- Google font Poppins: weights yang dipakai admin saja -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Manggil SCSS dan JS -->
        @vite(['resources/scss/admin.scss', 'resources/js/admin.js'])
    </head>
    <body class="sb-nav-fixed">
            <x-admin-navbar />
            <div id="layoutSidenav">
                <div id="layoutSidenav_nav">
                    <x-admin-sidebar />
                </div>
                <div id="layoutSidenav_content">
                    <!-- Content -->
                    @yield('content')
                </div>
            </div>
        @stack('scripts')
    </body>
</html>
