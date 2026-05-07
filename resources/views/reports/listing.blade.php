@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports')

@push('styles')
<style>
    .reports-container { display: grid; grid-template-columns: 300px 1fr; gap: 24px; }
    .report-menu { background: #fff; border-radius: 12px; padding: 16px; box-shadow: var(--shadow-sm); height: fit-content; }
    .report-menu h3 { font-size: 0.9rem; color: var(--gray-400); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; padding-left: 8px; }
    .menu-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 8px; color: var(--gray-700); font-weight: 500; transition: var(--transition); cursor: pointer; border: none; width: 100%; text-align: left; background: none; }
    .menu-item:hover { background: var(--gray-50); color: var(--primary); }
    .menu-item.active { background: #000000; color: #ffffff; }
    .menu-item i { font-size: 1.1rem; }

    .report-selection { background: #fff; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm); min-height: 400px; }
    .report-group { display: none; }
    .report-group.active { display: block; }
    .report-link { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-bottom: 1px solid var(--gray-100); color: var(--gray-700); transition: var(--transition); text-decoration: none; }
    .report-link:last-child { border-bottom: none; }
    .report-link:hover { background: var(--gray-50); color: var(--primary); }
    .report-link i { font-size: 1.2rem; color: var(--gray-400); }
    
    .empty-selection { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 300px; color: var(--gray-400); }
    .empty-selection i { font-size: 4rem; margin-bottom: 16px; }
</style>
@endpush

@section('content')
<div class="reports-container">
    <aside class="report-menu">
        <h3>Report Categories</h3>
        <button class="menu-item active" data-target="sales">
            <i class="bi bi-cart"></i> Sales
        </button>
        <button class="menu-item" data-target="customers">
            <i class="bi bi-people"></i> Customers
        </button>
        <button class="menu-item" data-target="items">
            <i class="bi bi-box"></i> Items
        </button>
        <button class="menu-item" data-target="employees">
            <i class="bi bi-person-badge"></i> Employees
        </button>
        <button class="menu-item" data-target="inventory">
            <i class="bi bi-bar-chart"></i> Inventory
        </button>
        <button class="menu-item" data-target="payments">
            <i class="bi bi-cash-stack"></i> Payments
        </button>
    </aside>

    <main class="report-selection" id="reportSelection">
        <div id="emptyMessage" class="empty-selection d-none">
            <i class="bi bi-arrow-left-circle"></i>
            <p>Select a category to view available reports</p>
        </div>

        <div class="report-group active" id="sales">
            <h2 class="mb-4">Sales Reports</h2>
            <a href="{{ route('reports.generate', 'summary_sales') }}" class="report-link">
                <i class="bi bi-file-earmark-text"></i>
                <div>
                    <strong>Summary Sales</strong>
                    <p class="mb-0 text-muted small">View overall sales totals grouped by day</p>
                </div>
            </a>
            <a href="#" class="report-link">
                <i class="bi bi-calendar-check"></i>
                <div>
                    <strong>Detailed Sales</strong>
                    <p class="mb-0 text-muted small">View individual sale details and items sold</p>
                </div>
            </a>
            <a href="#" class="report-link">
                <i class="bi bi-clock-history"></i>
                <div>
                    <strong>Sales by Time</strong>
                    <p class="mb-0 text-muted small">Analyze sales performance by hour or day of week</p>
                </div>
            </a>
        </div>

        <div class="report-group" id="customers">
            <h2 class="mb-4">Customer Reports</h2>
            <a href="#" class="report-link">
                <i class="bi bi-people"></i>
                <div>
                    <strong>Summary Customers</strong>
                    <p class="mb-0 text-muted small">Sales performance grouped by customer</p>
                </div>
            </a>
        </div>

        <!-- Other groups would be here -->
    </main>
</div>

@push('scripts')
<script>
document.querySelectorAll('.menu-item').forEach(button => {
    button.addEventListener('click', () => {
        // Toggle active class on buttons
        document.querySelectorAll('.menu-item').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');

        // Toggle visibility of report groups
        const target = button.getAttribute('data-target');
        document.querySelectorAll('.report-group').forEach(group => group.classList.remove('active'));
        
        const activeGroup = document.getElementById(target);
        if (activeGroup) {
            activeGroup.classList.add('active');
            document.getElementById('emptyMessage').classList.add('d-none');
        } else {
            document.getElementById('emptyMessage').classList.remove('d-none');
        }
    });
});
</script>
@endpush
@endsection
