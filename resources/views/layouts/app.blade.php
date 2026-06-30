<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="sync-token" content="{{ config('sync.shared_token') }}" />

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
        if (!function_exists('adjustBrightness')) {
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
        }
        
        $primaryLight = '#F8FAFC'; // Neutral light gray (gray-50)
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
                    <button type="button" class="topbar-icon-btn" id="lanSyncButton" title="Sync LAN">
                        <i class="bi bi-arrow-repeat"></i>
                    </button>
                    <div class="dropdown" id="notificationBell">
                        <button type="button" class="topbar-icon-btn dropdown-toggle border-0" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                            <i class="bi bi-bell"></i>
                            <span class="badge-dot" id="notificationBadge" style="display:none;"></span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow border-0 radius-lg mt-2" id="notificationDropdown" style="width:360px;max-height:480px;overflow-y:auto;">
                            <div class="px-3 py-2 fw-semibold border-bottom" style="font-size:0.9rem;">
                                Notifications
                                <span class="badge bg-secondary ms-1" id="unreadCountLabel">0</span>
                            </div>
                            <div id="notificationList" style="min-height:60px;">
                                <div class="px-3 py-3 text-muted small text-center">Loading...</div>
                            </div>
                            <div class="border-top text-center">
                                <a href="{{ route('app.notifications.all') }}" class="dropdown-item py-2 small text-primary">
                                    View all notifications
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="btn-add dropdown-toggle border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-plus-lg"></i> Add
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 radius-lg mt-2">
                            <li><a class="dropdown-item py-2 px-3" href="{{ route('sales.index') }}"><i class="bi bi-cart me-2"></i> New Sale</a></li>
                            <li><a class="dropdown-item py-2 px-3" href="{{ route('orders.index') }}"><i class="bi bi-file-earmark-text me-2"></i> New Order</a></li>
                            <li><a class="dropdown-item py-2 px-3" href="{{ route('purchases.create', ['mode' => 'receive']) }}"><i class="bi bi-truck me-2"></i> New purchase</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 px-3" href="{{ route('customers.index') }}"><i class="bi bi-person-plus me-2"></i> New Customer</a></li>
                            <li><a class="dropdown-item py-2 px-3" href="{{ route('items.index') }}"><i class="bi bi-box-seam me-2"></i> New Item</a></li>
                        </ul>
                    </div>
                    <div class="topbar-icon-btn" id="themeToggle" title="Toggle Dark Mode">
                        <i class="bi bi-moon-fill" id="themeIcon"></i>
                    </div>
                    {{-- <div class="topbar-icon-btn"><i class="bi bi-bell"></i><span class="badge-dot"></span></div> --}}
                    @php
                        $currentEmployee = auth('employee')->user();
                        $currentPerson = $currentEmployee?->person;
                        $employeeName = $currentPerson ? ($currentPerson->full_name ?? trim($currentPerson->first_name . ' ' . $currentPerson->last_name)) : 'Employee';
                        $employeeRole = $currentPerson?->title ?? 'Employee';
                        $employeeInitials = $currentPerson ? strtoupper(substr($currentPerson->first_name, 0, 1) . substr($currentPerson->last_name, 0, 1)) : '??';
                    @endphp
                    <div class="dropdown">
                        <div class="topbar-user dropdown-toggle border-0" data-bs-toggle="dropdown" aria-expanded="false" role="button">
                            <div class="topbar-user-info">
                                <div class="topbar-user-name">{{ $employeeName }}</div>
                                <div class="topbar-user-role">{{ $employeeRole }}</div>
                            </div>
                            <div class="topbar-avatar">{{ $employeeInitials }}</div>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 radius-lg mt-2">
                            <li><a class="dropdown-item py-2 px-3" href="{{ route('employee.profile') }}"><i class="bi bi-person me-2"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('employee.logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item py-2 px-3"><i class="bi bi-box-arrow-right me-2"></i> Logout</button>
                                </form>
                            </li>
                        </ul>
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
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = document.getElementById('themeIcon');
            const syncButton = document.getElementById('lanSyncButton');
            const htmlEl = document.documentElement;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const syncToken = document.querySelector('meta[name="sync-token"]')?.getAttribute('content');
            
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

            const notifList = document.getElementById('notificationList');
            const notifBadge = document.getElementById('notificationBadge');
            const unreadCountLabel = document.getElementById('unreadCountLabel');

            async function loadNotifications() {
                try {
                    const res = await fetch('{{ route('app.notifications') }}', {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!res.ok) return;
                    const data = await res.json();

                    const unread = data.unread_count || 0;
                    if (unreadCountLabel) unreadCountLabel.textContent = unread;
                    if (notifBadge) {
                        notifBadge.style.display = unread > 0 ? '' : 'none';
                    }

                    if (!notifList) return;
                    const items = data.notifications || [];
                    if (items.length === 0) {
                        notifList.innerHTML = '<div class="px-3 py-3 text-muted small text-center">No notifications</div>';
                        return;
                    }

                    notifList.innerHTML = items.map(n => {
                        const url = n.action_url || '#';
                        const cls = n.is_unread ? 'fw-semibold' : '';
                        const ref = (n.reference_type && n.reference_id) ? n.reference_type + ' #' + n.reference_id : '';
                        return '<a href="' + url + '" class="dropdown-item py-2 px-3 border-bottom notification-item ' + cls + '" data-id="' + n.id + '" data-unread="' + n.is_unread + '">' +
                            '<div style="font-size:0.85rem;">' + escHtml(n.title) + '</div>' +
                            (n.body ? '<div class="text-muted small" style="font-size:0.75rem;">' + escHtml(n.body) + '</div>' : '') +
                            (ref ? '<div class="text-muted" style="font-size:0.7rem;">' + ref + '</div>' : '') +
                            '<div class="text-muted" style="font-size:0.7rem;">' + (n.time_ago || '') + '</div>' +
                            '</a>';
                    }).join('');

                    notifList.querySelectorAll('.notification-item').forEach(el => {
                        el.addEventListener('click', async function(e) {
                            const id = this.dataset.id;
                            const unread = this.dataset.unread === 'true' || this.dataset.unread === '1';
                            if (unread && id) {
                                await fetch('/app/notifications/' + id + '/read', {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': csrfToken,
                                        'Accept': 'application/json',
                                    },
                                });
                                loadNotifications();
                            }
                        });
                    });
                } catch (e) {
                    // ignore
                }
            }

            function escHtml(str) {
                if (!str) return '';
                const d = document.createElement('div');
                d.textContent = str;
                return d.innerHTML;
            }

            if (syncButton) {
                syncButton.addEventListener('click', async () => {
                    syncButton.disabled = true;
                    syncButton.classList.add('disabled');

                    try {
                        await fetch('/api/lan/sync', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                                ...(syncToken ? { 'X-Sync-Token': syncToken } : {}),
                            },
                            body: JSON.stringify({}),
                        });

                        Swal.fire({
                            icon: 'info',
                            title: 'Sync queued',
                            text: 'Looking for LAN updates...',
                            timer: 2000,
                            timerProgressBar: true,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end',
                        });

                        setTimeout(loadNotifications, 3000);
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Sync failed',
                            text: 'Unable to start LAN sync.',
                            timer: 3000,
                            timerProgressBar: true,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end',
                        });
                    } finally {
                        syncButton.disabled = false;
                        syncButton.classList.remove('disabled');
                    }
                });
            }

            loadNotifications();
            setInterval(loadNotifications, 15000);
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
