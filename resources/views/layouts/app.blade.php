<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/defaults/def-favicon.svg') }}" />
    <title>@yield('title', 'POS System')</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css" />
    @stack('styles')
</head>



<body>

    <div class="app-wrapper">
        @include('layouts.sidebar')


        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <div class="main-content">
            <header class="topbar">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <span class="topbar-title">@yield('page-title', 'Dashboard')</span>
                <div class="topbar-actions">
                    <button class="btn-add"><i class="bi bi-plus-lg"></i> Add</button>
                    <div class="topbar-icon-btn"><i class="bi bi-bell"></i><span class="badge-dot"></span></div>
                    <div class="topbar-user">
                        <div class="topbar-user-info">
                            <div class="topbar-user-name">Shaun Marphy</div>
                            <div class="topbar-user-role">Admin</div>
                        </div>
                        <div class="topbar-avatar">SM</div>
                    </div>
                </div>
            </header>

            <main class="page-content">
                <div class="page-header">
                    <h1>@yield('page-title', 'Dashboard')</h1>
                    @hasSection('page-description')
                        <p>@yield('page-description')</p>
                    @endif
                </div>
                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    @stack('scripts')

    @if (session('status'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: '{{ session('status') }}',
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                });
            });
        </script>
    @endif

    @if (session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '{{ session('error') }}',
                    timer: 5000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                });
            });
        </script>
    @endif

</body>

</html>