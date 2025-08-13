<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

    <!-- Loading Skeleton Styles -->
    <style>
        #app-skeleton { position: fixed; inset: 0; z-index: 2100; background: var(--bs-body-bg); }
        #app-skeleton.fade-out { opacity: 0; transition: opacity .25s ease; pointer-events: none; }
        .skel { position: relative; overflow: hidden; background: var(--bs-secondary-bg); border-radius: .5rem; }
        .skel.round { border-radius: 50%; }
        .skel.line { height: 12px; }
        .skel.line-lg { height: 24px; }
        .skel.line-sm { height: 8px; }
        .skel::after {
            content: ""; position: absolute; inset: 0; transform: translateX(-100%);
            background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,.2), rgba(255,255,255,0));
            animation: skel-shimmer 1.25s infinite;
        }
        @keyframes skel-shimmer { 100% { transform: translateX(100%); } }
        @media print { #app-skeleton { display: none !important; } }
    </style>
    <noscript>
        <style>#app-skeleton{display:none!important}</style>
    </noscript>
</head>

<body class="d-flex flex-column min-vh-100">

    <!-- Global Loading Skeleton Overlay -->
    <div id="app-skeleton" aria-hidden="true">
        <div class="bg-body border-bottom">
            <div class="container py-3 d-flex align-items-center justify-content-between">
                <div class="skel" style="width:140px; height:32px;"></div>
                <div class="d-flex align-items-center gap-2">
                    <div class="skel round" style="width:36px; height:36px;"></div>
                    <div class="skel round" style="width:36px; height:36px;"></div>
                    <div class="skel round" style="width:36px; height:36px;"></div>
                </div>
            </div>
        </div>
        <div class="container py-4">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="skel" style="height: 360px;"></div>
                    <div class="mt-3">
                        <div class="skel line-lg mb-2" style="width:80%"></div>
                        <div class="skel line" style="width:60%"></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="skel line-lg mb-3" style="width:50%"></div>
                    <div class="d-flex flex-column gap-3">
                        @for ($i = 0; $i < 5; $i++)
                            <div class="d-flex gap-3 align-items-center">
                                <div class="skel" style="width:80px; height:60px; border-radius:.5rem;"></div>
                                <div class="flex-grow-1">
                                    <div class="skel line mb-2" style="width:90%"></div>
                                    <div class="skel line-sm" style="width:60%"></div>
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <div class="skel line-lg mb-3" style="width:30%"></div>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
                    @for ($i = 0; $i < 4; $i++)
                        <div class="col">
                            <div class="skel" style="height: 220px;"></div>
                            <div class="mt-3">
                                <div class="skel line mb-2" style="width:95%"></div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="skel line-sm" style="width:30%"></div>
                                    <div class="skel line-sm" style="width:20%"></div>
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>

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

    <!-- Hide Skeleton on load -->
    <script>
        window.addEventListener('load', function () {
            var skel = document.getElementById('app-skeleton');
            if (skel) {
                skel.classList.add('fade-out');
                setTimeout(function(){ skel.remove(); }, 300);
            }
        });
    </script>
</body>

</html>
