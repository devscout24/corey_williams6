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
    
    .sales-info { margin-top: 24px; background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05); border: 1px solid #f1f5f9; }
    .sales-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .sales-header h2 { margin: 0; font-size: 1.15rem; font-weight: 700; color: #1e293b; }
    .chart-period-select { padding: 6px 12px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; font-size: 0.85rem; font-weight: 500; color: #64748b; cursor: pointer; outline: none; }
    .nav-tabs { border: none; margin-bottom: 0; gap: 8px; }
    .nav-tabs .nav-link { border: none; color: #94a3b8; font-weight: 600; padding: 8px 16px; border-radius: 8px; font-size: 0.9rem; transition: all 0.2s; }
    .nav-tabs .nav-link:hover { background: #f8fafc; color: #64748b; }
    .nav-tabs .nav-link.active { color: #2563eb; background: #eff6ff; }
    .chart-container { position: relative; height: 320px; width: 100%; margin-top: 10px; }

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
        <div class="sales-header">
            <h2>Total Sales</h2>
            <div style="display: flex; gap: 12px; align-items: center;">
                <ul class="nav nav-tabs" id="salesTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="year-tab" data-bs-toggle="tab" data-bs-target="#year" type="button" role="tab">Year</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="month-tab" data-bs-toggle="tab" data-bs-target="#month" type="button" role="tab">Month</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="week-tab" data-bs-toggle="tab" data-bs-target="#week" type="button" role="tab">Week</button>
                    </li>
                </ul>
                <select class="chart-period-select">
                    <option>Last One Year</option>
                    <option>Last 6 Months</option>
                    <option>Last 30 Days</option>
                </select>
            </div>
        </div>
        
        <div class="tab-content" id="salesTabsContent">
            <div class="tab-pane fade show active" id="year" role="tabpanel">
                <div class="chart-container">
                    <canvas id="yearlyChart"></canvas>
                </div>
            </div>
            <div class="tab-pane fade" id="month" role="tabpanel">
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
    const createGradient = (ctx) => {
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(37, 99, 235, 0.2)');
        gradient.addColorStop(1, 'rgba(37, 99, 235, 0)');
        return gradient;
    };

    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            intersect: false,
            mode: 'index',
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    display: true,
                    drawBorder: false,
                    color: '#f1f5f9'
                },
                ticks: {
                    color: '#94a3b8',
                    font: { size: 11 },
                    callback: function(value) {
                        return '$' + value;
                    }
                }
            },
            x: {
                grid: {
                    display: false,
                    drawBorder: false
                },
                ticks: {
                    color: '#94a3b8',
                    font: { size: 11 }
                }
            }
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1e293b',
                padding: 12,
                titleFont: { size: 13 },
                bodyFont: { size: 13 },
                cornerRadius: 8,
                displayColors: false
            }
        }
    };

    // Yearly Chart
    const yearlyCtx = document.getElementById('yearlyChart').getContext('2d');
    new Chart(yearlyCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Sales Amount',
                data: (function() {
                    const data = new Array(12).fill(0);
                    {!! json_encode($yearlySales) !!}.forEach(item => {
                        data[item.month - 1] = item.sale_amount;
                    });
                    return data;
                })(),
                borderColor: '#2563eb',
                borderWidth: 3,
                fill: true,
                backgroundColor: createGradient(yearlyCtx),
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#2563eb',
                pointBorderWidth: 2,
            }]
        },
        options: commonOptions
    });

    // Monthly Chart
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($monthlySales->pluck('sale_date')->map(fn($d) => \Illuminate\Support\Carbon::parse($d)->format('M d'))) !!},
            datasets: [{
                label: 'Sales Amount',
                data: {!! json_encode($monthlySales->pluck('sale_amount')) !!},
                borderColor: '#2563eb',
                borderWidth: 3,
                fill: true,
                backgroundColor: createGradient(monthlyCtx),
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#2563eb',
                pointBorderWidth: 2,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#2563eb',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2,
            }]
        },
        options: commonOptions
    });

    // Weekly Chart
    const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
    new Chart(weeklyCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($weeklySales->pluck('sale_date')->map(fn($d) => \Illuminate\Support\Carbon::parse($d)->format('D'))) !!},
            datasets: [{
                label: 'Sales Amount',
                data: {!! json_encode($weeklySales->pluck('sale_amount')) !!},
                borderColor: '#2563eb',
                borderWidth: 3,
                fill: true,
                backgroundColor: createGradient(weeklyCtx),
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#2563eb',
                pointBorderWidth: 2,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#2563eb',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2,
            }]
        },
        options: commonOptions
    });
});
</script>
@endpush
@endsection
