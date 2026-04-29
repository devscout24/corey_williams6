<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@yield('title', 'POS System')</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
  @stack('styles')
</head>
<body>

<div class="app-wrapper">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="sidebar-brand-icon"><i class="bi bi-shop"></i></div>
      <div class="sidebar-brand-info">
        <div class="sidebar-brand-name">Main Branch</div>
        <div class="sidebar-brand-role">Administrator</div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-item">
        <a href="{{ route('modules.index') }}" class="nav-link">
          <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
        </a>
      </div>
      <div class="nav-item">
        <a href="#" class="nav-link" data-toggle="submenu">
          <i class="bi bi-people-fill"></i><span>Contacts</span>
          <i class="bi bi-chevron-right nav-arrow"></i>
        </a>
        <div class="nav-submenu">
          <div class="nav-item"><a href="{{ Route::has('customers.index') ? route('customers.index') : '#' }}" class="nav-link"><i class="bi bi-dot"></i> Customers</a></div>
          <div class="nav-item"><a href="{{ route('suppliers.index') }}" class="nav-link"><i class="bi bi-dot"></i> Suppliers</a></div>
        </div>
      </div>
      <div class="nav-item">
        <a href="#" class="nav-link" data-toggle="submenu">
          <i class="bi bi-box-seam-fill"></i><span>Inventory</span>
          <i class="bi bi-chevron-right nav-arrow"></i>
        </a>
        <div class="nav-submenu">
          <div class="nav-item"><a href="{{ route('items.index') }}" class="nav-link"><i class="bi bi-dot"></i> Items</a></div>
          <div class="nav-item"><a href="{{ route('labels.index') }}" class="nav-link"><i class="bi bi-dot"></i> Labels</a></div>
          <div class="nav-item"><a href="{{ route('item-kits.index') }}" class="nav-link"><i class="bi bi-dot"></i> Item Kits</a></div>
          <div class="nav-item"><a href="{{ route('orders.index') }}" class="nav-link"><i class="bi bi-dot"></i> Orders</a></div>
          <div class="nav-item"><a href="{{ route('categories.index') }}" class="nav-link"><i class="bi bi-dot"></i> Categories</a></div>
          <div class="nav-item"><a href="{{ Route::has('tags.index') ? route('tags.index') : '#' }}" class="nav-link"><i class="bi bi-dot"></i> Tags</a></div>
        </div>
      </div>
      <div class="nav-item"><a href="#" class="nav-link"><i class="bi bi-bar-chart-fill"></i><span>Reports</span></a></div>
      <div class="nav-item"><a href="{{ route('receivings.index') }}" class="nav-link"><i class="bi bi-truck"></i><span>Receiving</span></a></div>
      <div class="nav-item"><a href="{{ route('sales.index') }}" class="nav-link"><i class="bi bi-cart-fill"></i><span>Sales</span></a></div>
      <div class="nav-item">
        <a href="#" class="nav-link" data-toggle="submenu">
          <i class="bi bi-arrow-left-right"></i><span>Transfer</span>
          <i class="bi bi-chevron-right nav-arrow"></i>
        </a>
        <div class="nav-submenu">
          <div class="nav-item"><a href="{{ route('inventory.operations') }}" class="nav-link"><i class="bi bi-dot"></i> Transfer Out</a></div>
          <div class="nav-item"><a href="{{ route('inventory.operations') }}" class="nav-link"><i class="bi bi-dot"></i> Transfer In</a></div>
        </div>
      </div>
      <div class="nav-item"><a href="{{ route('employees.index') }}" class="nav-link"><i class="bi bi-person-badge-fill"></i><span>Employees</span></a></div>
      <div class="nav-item"><a href="#" class="nav-link"><i class="bi bi-receipt"></i><span>VAT Report</span></a></div>
      <div class="nav-item"><a href="{{ route('config.index') }}" class="nav-link"><i class="bi bi-gear-fill"></i><span>Store Config</span></a></div>
      <div class="nav-item"><a href="#" class="nav-link"><i class="bi bi-geo-alt-fill"></i><span>Locations</span></a></div>
      <div class="nav-item"><a href="{{ route('messages.index') }}" class="nav-link"><i class="bi bi-chat-dots-fill"></i><span>Messages</span></a></div>
    </nav>

    <div class="sidebar-footer">
      <form class="sidebar-logout" method="post" action="{{ route('employee.logout') }}">
        @csrf
        <button type="submit" style="display:flex;align-items:center;gap:9px;">
          <i class="bi bi-box-arrow-left"></i><span>Logout</span>
        </button>
      </form>
    </div>
  </aside>

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
<script src="{{ asset('assets/js/main.js') }}"></script>
@stack('scripts')

@if(session('status'))
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

@if(session('error'))
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
