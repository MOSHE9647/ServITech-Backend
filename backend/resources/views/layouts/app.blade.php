{{-- filepath: resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Title -->
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div id="app" class="font-sans text-gray-900 antialiased">
        <main>
            @yield('content')
        </main>
    </div>

    @if (session()->has('swal'))
        <script>
            const swalData = @json($swal ?? []);
            console.log("SwalData: ", swalData);

            const Toast = (type, title) => Swal.mixin({
                toast: true,
                theme: 'auto',
                icon: `${type}`,
                title: `${title}`,
                position: "top-end",
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                customClass: {
                    timerProgressBar: `swal-${type}-progress-bar`,
                },
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                }
            });
            
            Toast(swalData.type, swalData.title).fire();
        </script>
    @endif
</body>
</html>