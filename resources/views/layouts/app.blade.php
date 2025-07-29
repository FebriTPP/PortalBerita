<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Winnews - @yield('title', 'Portal Berita Terkini')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    {{-- SweetAlert2 CSS --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('components.alert-swal')

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/comment.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <script>
        (function() {
            const themeKey = 'CONFIG.THEME.STORAGE_KEY'; // Ganti dengan CONFIG.THEME.STORAGE_KEY
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const storedTheme = localStorage.getItem(themeKey);
            const theme = storedTheme || (prefersDark ? 'dark' :
            'light'); // Ganti 'dark' dan 'light' sesuai dengan CONFIG.THEME.THEMES
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
</head>

<body class="d-flex flex-column min-vh-100">

    {{-- Header --}}
    @include('layouts.header')

    {{-- Konten utama --}}
    <main class="flex-grow-1">
        @yield('content')
    </main>

    {{-- Footer --}}
    @include('layouts.footer')

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous">
    </script>

    <!-- Vite JavaScript -->
    @vite(['resources/js/app.js'])
</body>

</html>
