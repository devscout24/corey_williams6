<!-- Sidebar -->
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
                    <div class="nav-item">
                        <a href="{{ Route::has('customers.index') ? route('customers.index') : '#' }}" class="nav-link">
                            <i class="bi bi-dot"></i> Customers
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="{{ route('suppliers.index') }}" class="nav-link">
                            <i class="bi bi-dot"></i> Suppliers
                        </a>
                    </div>
                </div>
            </div>

            <div class="nav-item">
                <a href="#" class="nav-link" data-toggle="submenu">
                    <i class="bi bi-box-seam-fill"></i><span>Inventory</span>
                    <i class="bi bi-chevron-right nav-arrow"></i>
                </a>
                <div class="nav-submenu">
                    <div class="nav-item"><a href="{{ route('items.index') }}" class="nav-link"><i class="bi bi-dot"></i> Items</a></div>
                    <div class="nav-item"><a href="{{ route('item-kits.index') }}" class="nav-link"><i class="bi bi-dot"></i> Item Kits</a></div>
                    <div class="nav-item"><a href="{{ route('labels.index') }}" class="nav-link"><i class="bi bi-dot"></i> Labels</a></div>
                    <div class="nav-item"><a href="{{ route('orders.index') }}" class="nav-link"><i class="bi bi-dot"></i> Orders</a></div>
                    <div class="nav-item"><a href="{{ route('categories.index') }}" class="nav-link"><i class="bi bi-dot"></i> Categories</a></div>
                    <div class="nav-item"><a href="{{ route('attributes.index') }}" class="nav-link"><i class="bi bi-dot"></i> Attributes</a></div>
                    <div class="nav-item"><a href="{{ Route::has('tags.index') ? route('tags.index') : '#' }}" class="nav-link"><i class="bi bi-dot"></i> Tags</a></div>
                    <div class="nav-item"><a href="{{ Route::has('price-rules.index') ? route('price-rules.index') : '#' }}" class="nav-link"><i class="bi bi-dot"></i> Price Rules</a></div>
                </div>
            </div>

            <div class="nav-item">
                <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <i class="bi bi-bar-chart-fill"></i><span>Reports</span>
                </a>
            </div>

            <div class="nav-item">
                <a href="{{ route('purchases.index') }}"
                    class="nav-link {{ request()->routeIs('purchases.*', 'receivings.*') ? 'active' : '' }}">
                    <i class="bi bi-truck"></i><span>Purchases</span>
                </a>
            </div>
            <div class="nav-item"><a href="{{ route('sales.index') }}" class="nav-link"><i class="bi bi-cart-fill"></i><span>Sales</span></a></div>

            <div class="nav-item">
                <a href="#" class="nav-link" data-toggle="submenu">
                    <i class="bi bi-arrow-left-right"></i><span>Transfer</span>
                    <i class="bi bi-chevron-right nav-arrow"></i>
                </a>
                <div class="nav-submenu">
                    <div class="nav-item"><a href="{{ route('transfers.out') }}" class="nav-link"><i class="bi bi-dot"></i> Transfer Out</a></div>
                    <div class="nav-item"><a href="{{ route('transfers.in') }}" class="nav-link"><i class="bi bi-dot"></i> Transfer In</a></div>
                </div>
            </div>

            <div class="nav-item"><a href="{{ route('employees.index') }}" class="nav-link"><i class="bi bi-person-badge-fill"></i><span>Employees</span></a></div>
            <div class="nav-item"><a href="{{ route('reports.vat') }}" class="nav-link {{ request()->routeIs('reports.vat') ? 'active' : '' }}"><i class="bi bi-receipt"></i><span>VAT Report</span></a></div>
            <div class="nav-item"><a href="{{ route('config.index') }}" class="nav-link"><i class="bi bi-gear-fill"></i><span>Store Config</span></a></div>
            <div class="nav-item">
                <a href="{{ route('lan.locations') }}" class="nav-link {{ request()->routeIs('lan.locations') ? 'active' : '' }}">
                    <i class="bi bi-geo-alt-fill"></i><span>Locations</span>
                </a>
            </div>
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