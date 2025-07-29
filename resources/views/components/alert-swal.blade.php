@foreach (['success', 'error', 'warning', 'info'] as $msg)
    @if (session($msg))
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    toast: true,
                    position: "top-end",
                    icon: "{{ $msg }}",
                    title: "{{ session($msg) }}",
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });
            });
        </script>
    @endif
@endforeach
