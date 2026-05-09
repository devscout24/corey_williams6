<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <link rel="shortcut icon" href="{{asset('assets/images/defaults/fv.png')}}" type="image/svg+xml">

    <title>@yield('title', 'POS System')</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css" />
    
    @php
        $currentLocationId = auth('employee')->user()?->location_id ?? 1;
        $currentLocation = \App\Models\PhpposLocation::find($currentLocationId);
        $primaryColor = $currentLocation?->color ?? '#2563EB';
        $secondaryColor = $currentLocation?->secondary_color ?? '#1E293B';
        
        // Generate lighter/darker variations for the primary color
        function adjustBrightness($hex, $steps) {
            $steps = max(-255, min(255, $steps));
            $hex = str_replace('#', '', $hex);
            if (strlen($hex) == 3) {
                $hex = str_repeat(substr($hex, 0, 1), 2).str_repeat(substr($hex, 1, 1), 2).str_repeat(substr($hex, 2, 1), 2);
            }
            $color = '';
            for ($x = 0; $x < 3; $x++) {
                $c = hexdec(substr($hex, $x * 2, 2));
                $c = dechex(max(0, min(255, $c + $steps)));
                $color .= str_pad($c, 2, '0', STR_PAD_LEFT);
            }
            return '#' . $color;
        }
        
        $primaryLight = adjustBrightness($primaryColor, 180);
        $primaryDark = adjustBrightness($primaryColor, -40);
    @endphp

    <style>
        :root {
            --primary: {{ $primaryColor }};
            --primary-dark: {{ $primaryDark }};
            --primary-light: {{ $primaryLight }};
            --secondary: {{ $secondaryColor }};
        }
    </style>

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
                    <div class="dropdown">
                        <button class="btn-add dropdown-toggle border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-plus-lg"></i> Add
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 radius-lg mt-2">
                            <li><a class="dropdown-item py-2 px-3" href="{{ route('sales.index') }}"><i class="bi bi-cart me-2"></i> New Sale</a></li>
                            <li><a class="dropdown-item py-2 px-3" href="{{ route('orders.index') }}"><i class="bi bi-file-earmark-text me-2"></i> New Order</a></li>
                            <li><a class="dropdown-item py-2 px-3" href="{{ route('purchases.create') }}"><i class="bi bi-truck me-2"></i> New purchase</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 px-3" href="{{ route('customers.index') }}"><i class="bi bi-person-plus me-2"></i> New Customer</a></li>
                            <li><a class="dropdown-item py-2 px-3" href="{{ route('items.index') }}"><i class="bi bi-box-seam me-2"></i> New Item</a></li>
                        </ul>
                    </div>
                    <div class="topbar-icon-btn" id="themeToggle" title="Toggle Dark Mode">
                        <i class="bi bi-moon-fill" id="themeIcon"></i>
                    </div>
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
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = document.getElementById('themeIcon');
            const htmlEl = document.documentElement;
            
            const savedTheme = localStorage.getItem('theme') || 'light';
            htmlEl.setAttribute('data-theme', savedTheme);
            updateIcon(savedTheme);

            themeToggle.addEventListener('click', () => {
                const currentTheme = htmlEl.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                htmlEl.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateIcon(newTheme);
            });

            function updateIcon(theme) {
                if (theme === 'dark') {
                    themeIcon.classList.remove('bi-moon-fill');
                    themeIcon.classList.add('bi-sun-fill');
                } else {
                    themeIcon.classList.remove('bi-sun-fill');
                    themeIcon.classList.add('bi-moon-fill');
                }
            }
        });
    </script>
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