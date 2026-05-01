@extends('layouts.app')

@section('title', 'POS Modules')
@section('page-title', 'Module Map')

@push('styles')
<style>
    .wrap { max-width: 100%; margin: 0; padding: 0; }
    .head { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-top: 18px; }
    .card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 8px 24px rgba(16, 24, 40, 0.08); }
    .card h2 { margin: 0 0 10px; font-size: 1.05rem; }
    .sub { list-style: none; margin: 0; padding: 0; }
    .sub li { padding: 6px 0; border-bottom: 1px dashed #dce3ee; }
    .sub li:last-child { border-bottom: 0; }
    .actions a, .actions button { border: 0; background: #0e7490; color: #fff; border-radius: 8px; padding: 8px 12px; text-decoration: none; cursor: pointer; }
    form { margin: 0; }
    
    .sales-info { margin-top: 24px; background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 8px 24px rgba(16, 24, 40, 0.08); }
    .sales-info h2 { margin: 0 0 16px; font-size: 1.2rem; text-align: center; }
    .nav-tabs { border-bottom: 2px solid #f1f5f9; margin-bottom: 20px; justify-content: center; }
    .nav-tabs .nav-link { border: none; color: #64748b; font-weight: 500; padding: 10px 20px; }
    .nav-tabs .nav-link.active { color: #0e7490; border-bottom: 2px solid #0e7490; background: transparent; }
    .chart-container { position: relative; height: 300px; width: 100%; }

    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .stat-card { background: #fff; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 8px 24px rgba(16, 24, 40, 0.08); text-decoration: none; color: inherit; transition: transform 0.2s; }
    .stat-card:hover { transform: translateY(-4px); color: inherit; }
    .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #fff; }
    .stat-info h3 { margin: 0; font-size: 1.5rem; font-weight: 700; }
    .stat-info p { margin: 0; color: #64748b; font-size: 0.9rem; font-weight: 500; }
    .bg-blue { background: #3b82f6; }
    .bg-green { background: #10b981; }
    .bg-red { background: #ef4444; }
    .bg-orange { background: #f59e0b; }
</style>
@endpush

@section('content')
<div class="wrap">
    <div class="head">
        <h1>Module Map</h1>
        <div class="actions" style="display:flex; gap:8px;">
            <a href="{{ route('labels.index') }}">Open Labels</a>
            <a href="{{ route('inventory.operations') }}">Inventory Ops</a>
            <a href="{{ route('sales.index') }}">Sales</a>
            <a href="{{ route('messages.index') }}">Messages</a>
            <form method="post" action="{{ route('employee.logout') }}">
                @csrf
                <button type="submit">Logout</button>
            </form>
        </div>
    </div>

    <div class="stats-grid">
        <a href="{{ route('sales.index') }}" class="stat-card">
            <div class="stat-icon bg-blue"><i class="bi bi-cart"></i></div>
            <div class="stat-info">
                <h3>{{ number_format($stats['total_sales']) }}</h3>
                <p>Total Sales</p>
            </div>
        </a>
        <a href="{{ route('suppliers.index') }}" class="stat-card">
            <div class="stat-icon bg-green"><i class="bi bi-people"></i></div>
            <div class="stat-info">
                <h3>{{ number_format($stats['total_customers']) }}</h3>
                <p>Total Customers</p>
            </div>
        </a>
        <a href="{{ route('items.index') }}" class="stat-card">
            <div class="stat-icon bg-red"><i class="bi bi-box"></i></div>
            <div class="stat-info">
                <h3>{{ number_format($stats['total_items']) }}</h3>
                <p>Total Items</p>
            </div>
        </a>
        <a href="{{ route('item-kits.index') }}" class="stat-card">
            <div class="stat-icon bg-orange"><i class="bi bi-boxes"></i></div>
            <div class="stat-info">
                <h3>{{ number_format($stats['total_item_kits']) }}</h3>
                <p>Total Item Kits</p>
            </div>
        </a>
    </div>

    <div class="grid">
        @foreach($modules as $module)
            <article class="card">
                <h2>{{ ucfirst($module->module_id) }}</h2>
                <ul class="sub">
                    @foreach($module->submodules as $sub)
                        <li>
                            @if($module->module_id === 'items' && $sub->submodule_key === 'labels')
                                <a href="{{ route('labels.index') }}">{{ $sub->label }}</a>
                            @elseif($module->module_id === 'items' && $sub->submodule_key === 'items')
                                <a href="{{ route('items.index') }}">{{ $sub->label }}</a>
                            @elseif($module->module_id === 'items' && $sub->submodule_key === 'item_kits')
                                <a href="{{ route('item-kits.index') }}">{{ $sub->label }}</a>
                            @elseif($module->module_id === 'items' && $sub->submodule_key === 'categories')
                                <a href="{{ route('categories.index') }}">{{ $sub->label }}</a>
                            @elseif($module->module_id === 'receivings')
                                <a href="{{ route('inventory.operations') }}">{{ $sub->label }}</a>
                            @elseif($module->module_id === 'sales')
                                <a href="{{ route('sales.index') }}">{{ $sub->label }}</a>
                            @elseif($module->module_id === 'messages')
                                <a href="{{ route('messages.index') }}">{{ $sub->label }}</a>
                            @elseif($module->module_id === 'contacts' && $sub->submodule_key === 'suppliers')
                                <a href="{{ route('suppliers.index') }}">{{ $sub->label }}</a>
                            @else
                                {{ $sub->label }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            </article>
        @endforeach
    </div>

    <section class="sales-info">
        <h2>Sales Information</h2>
        
        <ul class="nav nav-tabs" id="salesTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="month-tab" data-bs-toggle="tab" data-bs-target="#month" type="button" role="tab">Month</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="week-tab" data-bs-toggle="tab" data-bs-target="#week" type="button" role="tab">Week</button>
            </li>
        </ul>
        
        <div class="tab-content" id="salesTabsContent">
            <div class="tab-pane fade show active" id="month" role="tabpanel">
                <div class="chart-container">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
            <div class="tab-pane fade" id="week" role="tabpanel">
                <div class="chart-container">
                    <canvas id="weeklyChart"></canvas>
                </div>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value;
                    }
                }
            }
        },
        plugins: {
            legend: { display: false }
        }
    };

    // Monthly Chart
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($monthlySales->pluck('sale_date')) !!},
            datasets: [{
                label: 'Sales Amount',
                data: {!! json_encode($monthlySales->pluck('sale_amount')) !!},
                backgroundColor: '#5d9bfb',
                borderRadius: 4
            }]
        },
        options: commonOptions
    });

    // Weekly Chart
    const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
    new Chart(weeklyCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($weeklySales->pluck('sale_date')) !!},
            datasets: [{
                label: 'Sales Amount',
                data: {!! json_encode($weeklySales->pluck('sale_amount')) !!},
                backgroundColor: '#5d9bfb',
                borderRadius: 4
            }]
        },
        options: commonOptions
    });
});
</script>
@endpush
@endsection
