<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Winnews</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    {{-- SweetAlert2 CSS --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('components.alert-swal')

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">

</head>

<body style="background-color: #363535;">
    <div class="login-page d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="card shadow p-4" style="max-width: 400px; width: 100%; border-radius: 16px; background-color: #ffffff">
            <h2 class="mb-4 text-center">@yield('heading')</h2>

            @yield('description')

            @yield('content')
        </div>
    </div>

    @yield('scripts')
</body>

</html>
