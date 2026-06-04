@extends('layouts.app')

@section('title', 'Dashboard | POS System')
@section('page-title', 'Executive Dashboard')
@section('page-description')
Overview of your business performance at {{ $currentStoreLocationName ?? 'Main Branch' }}
@endsection

@push('styles')
<style>
    /* Custom styles for matching corey-dashboard index.html precisely */
    .gs-progress-badge {
        background: #DBE1FF;
        color: var(--primary);
        font-size: 11px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 99px;
    }

    .gs-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }

    .gs-item {
        border: 1px solid var(--gray-200);
        border-radius: var(--radius);
        padding: 18px 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        text-align: center;
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
        color: var(--gray-700);
    }

    .gs-item.done {
        background: #f8fafc;
    }

    .gs-item:hover {
        border-color: var(--primary);
        background: var(--primary);
        color: #fff;
        transform: translateY(-2px);
    }

    .gs-item-icon {
        width: 38px;
        height: 38px;
        border-radius: var(--radius-sm);
        background: var(--gray-100);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--gray-500);
        font-size: 17px;
        transition: var(--transition);
    }

    .gs-item:hover .gs-item-icon {
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
    }

    .gs-item span {
        font-size: 12px;
        font-weight: 600;
    }

    .cmd-list { padding: 10px; }

    .cmd-btn-primary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--primary);
        color: #fff;
        border-radius: var(--radius);
        padding: 12px 16px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        margin-bottom: 8px;
        width: 100%;
        text-decoration: none;
    }

    .cmd-btn-primary:hover {
        background: var(--primary-dark);
        color: #fff;
    }

    .cmd-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 12px;
        border-radius: var(--radius-sm);
        cursor: pointer;
        transition: var(--transition);
        color: var(--gray-700);
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
    }

    .cmd-item:hover {
        background: var(--gray-50);
        color: var(--primary);
    }

    .cmd-item-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .cmd-item-left i { font-size: 14px; color: var(--gray-400); }
    .cmd-item:hover .cmd-item-left i { color: var(--primary); }

    .chart-wrapper {
        padding: 20px;
        height: 320px;
    }

    /* Dark Mode Overrides */
    [data-theme='dark'] .gs-item {
        background: var(--gray-100);
        border-color: var(--gray-200);
        color: var(--gray-700);
    }
    [data-theme='dark'] .gs-item.done {
        background: var(--gray-200);
    }
    [data-theme='dark'] .cmd-item {
        color: var(--gray-700);
    }
</style>
@endpush

@section('content')
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="bi bi-cart3"></i>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Sales Amount</div>
            <div class="stat-value">${{ number_format($stats['total_sales_amount'], 2) }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">
            <i class="bi bi-people-fill"></i>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Customers</div>
            <div class="stat-value">{{ number_format($stats['total_customers']) }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon red">
            <i class="bi bi-box-seam"></i>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Items</div>
            <div class="stat-value">{{ number_format($stats['total_items']) }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="bi bi-boxes"></i>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Item Kits</div>
            <div class="stat-value">{{ number_format($stats['total_item_kits']) }}</div>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Getting Started -->
    <div class="card h-100">
        <div class="card-header border-0 pb-0">
            <div>
                <div class="card-title mb-1">Getting Started</div>
                <div style="font-size:12px; color:var(--gray-400);">Complete these steps to set up your store</div>
            </div>
            @php
                $completed = 0;
                if($stats['total_locations'] > 0) $completed++;
                if($stats['total_items'] > 0) $completed++;
                if($stats['total_employees'] > 0) $completed++;
                if($stats['total_customers'] > 0) $completed++;
                if($stats['total_sales'] > 0) $completed++;
            @endphp
            <span class="gs-progress-badge">{{ $completed }} / 6 Completed</span>
        </div>
        <div class="card-body">
            <div class="gs-grid">
                <a href="{{ route('config.index') }}" class="gs-item done">
                    <div class="gs-item-icon"><i class="bi bi-gear-fill"></i></div>
                    <span>Store Config</span>
                </a>
                <a href="{{ route('config.index') }}" class="gs-item {{ $stats['total_locations'] > 0 ? 'done' : '' }}">
                    <div class="gs-item-icon"><i class="bi bi-geo-alt-fill"></i></div>
                    <span>Locations</span>
                </a>
                <a href="{{ route('items.index') }}" class="gs-item {{ $stats['total_items'] > 0 ? 'done' : '' }}">
                    <div class="gs-item-icon"><i class="bi bi-box-seam"></i></div>
                    <span>Items</span>
                </a>
                <a href="{{ route('employees.index') }}" class="gs-item {{ $stats['total_employees'] > 0 ? 'done' : '' }}">
                    <div class="gs-item-icon"><i class="bi bi-person-badge-fill"></i></div>
                    <span>Employees</span>
                </a>
                <a href="{{ route('customers.index') }}" class="gs-item {{ $stats['total_customers'] > 0 ? 'done' : '' }}">
                    <div class="gs-item-icon"><i class="bi bi-people"></i></div>
                    <span>Customers</span>
                </a>
                <a href="{{ route('sales.index') }}" class="gs-item {{ $stats['total_sales'] > 0 ? 'done' : '' }}">
                    <div class="gs-item-icon"><i class="bi bi-cart"></i></div>
                    <span>Start Sales</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Command Center -->
    <div class="card h-100">
        <div class="card-header border-0 pb-0">
            <div class="card-title">Command Center</div>
        </div>
        <div class="card-body p-0">
            <div class="cmd-list">
                <a href="{{ route('sales.index') }}" class="cmd-btn-primary">
                    <span style="display:flex; align-items:center; gap:8px;">
                        <i class="bi bi-cart-fill"></i> Start New Sale
                    </span>
                    <i class="bi bi-chevron-right"></i>
                </a>
                <a href="{{ route('purchases.create', ['mode' => 'receive']) }}" class="cmd-item">
                    <div class="cmd-item-left">
                        <i class="bi bi-truck"></i>
                        <span>Start a New Purchase</span>
                    </div>
                    <i class="bi bi-chevron-right" style="font-size:12px; color:var(--gray-300);"></i>
                </a>
                <a href="{{ route('reports.index') }}" class="cmd-item">
                    <div class="cmd-item-left">
                        <i class="bi bi-clock-history"></i>
                        <span>Today's closeout report</span>
                    </div>
                    <i class="bi bi-chevron-right" style="font-size:12px; color:var(--gray-300);"></i>
                </a>
                <a href="{{ route('reports.index') }}" class="cmd-item">
                    <div class="cmd-item-left">
                        <i class="bi bi-clock-history"></i>
                        <span>Today's detailed sales report</span>
                    </div>
                    <i class="bi bi-chevron-right" style="font-size:12px; color:var(--gray-300);"></i>
                </a>
                <a href="{{ route('reports.index') }}" class="cmd-item">
                    <div class="cmd-item-left">
                        <i class="bi bi-bar-chart-line"></i>
                        <span>Today's summary items report</span>
                    </div>
                    <i class="bi bi-chevron-right" style="font-size:12px; color:var(--gray-300);"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="card mb-4 mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="card-title">Recent Activity</div>
        <a href="{{ route('sales.index') }}" style="font-size:12px; color:var(--primary); font-weight:600;">View All</a>
    </div>
    <div class="card-body p-0">
        @forelse($recentSales as $sale)
            <div class="activity-item">
                <div class="activity-icon"><i class="bi bi-receipt-cutoff"></i></div>
                <div class="activity-info">
                    <div class="activity-title">Sale #{{ $sale->sale_id }}</div>
                    <div class="activity-meta">
                        {{ \Carbon\Carbon::parse($sale->created_at)->diffForHumans() }} &bull;
                        {{ ($sale->first_name || $sale->last_name) ? $sale->first_name . ' ' . $sale->last_name : 'Walk-in Customer' }}
                    </div>
                </div>
                <div class="activity-amount">${{ number_format($sale->total, 2) }}</div>
            </div>
        @empty
            <div class="p-4 text-center text-muted">No recent sales found</div>
        @endforelse
    </div>
</div>

<!-- Total Sales Chart -->
<div class="card chart-card">
    <div class="card-header border-0 d-flex justify-content-between align-items-center">
        <div class="card-title">Total Sales Analytics</div>
        <div class="d-flex gap-2">
             <ul class="nav nav-pills" id="salesTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="btn btn-sm btn-outline active" id="year-tab" data-bs-toggle="tab" data-bs-target="#year" type="button" role="tab">Year</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="btn btn-sm btn-outline ms-2" id="month-tab" data-bs-toggle="tab" data-bs-target="#month" type="button" role="tab">Month</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="btn btn-sm btn-outline ms-2" id="week-tab" data-bs-toggle="tab" data-bs-target="#week" type="button" role="tab">Week</button>
                </li>
            </ul>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="tab-content" id="salesTabsContent">
            <div class="tab-pane fade show active" id="year" role="tabpanel">
                <div class="chart-wrapper">
                    <canvas id="yearlyChart"></canvas>
                </div>
            </div>
            <div class="tab-pane fade" id="month" role="tabpanel">
                <div class="chart-wrapper">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
            <div class="tab-pane fade" id="week" role="tabpanel">
                <div class="chart-wrapper">
                    <canvas id="weeklyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
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
            }]
        },
        options: commonOptions
    });
});
</script>
@endpush
@endsection
