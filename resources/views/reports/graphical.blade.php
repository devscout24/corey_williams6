@extends('layouts.app')

@section('title', $title)
@section('page-title', $title)

@push('styles')
<style>
    .report-meta { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; padding: 16px; background: var(--gray-50); border-radius: 12px; border: 1px solid var(--gray-200); }
    .report-meta h4 { margin: 0; color: var(--gray-900); font-size: 1.1rem; }
    .chart-container { background: #fff; border-radius: 12px; box-shadow: var(--shadow-sm); padding: 24px; margin-bottom: 24px; }
    .data-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .summary-card { background: var(--gray-50); padding: 16px; border-radius: 12px; border: 1px solid var(--gray-100); }
    .summary-card .label { font-size: 0.8rem; color: var(--gray-500); text-transform: uppercase; margin-bottom: 4px; }
    .summary-card .value { font-size: 1.2rem; font-weight: 600; color: var(--gray-900); }

    @media print {
        .page-header { display: none !important; }
        .chart-container { border: none !important; }
    }
</style>
@endpush

@section('content')
<div class="report-meta">
    <div>
        <h4>{{ $title }}</h4>
        <p class="mb-0 text-muted small">Range: {{ $startDate }} to {{ $endDate }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light me-2">
            <i class="bi bi-arrow-left me-1"></i> Back to Reports
        </a>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <a href="{{ route('reports.generate', $report) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-arrow-repeat me-1"></i> Regenerate
        </a>
    </div>
</div>

@isset($summary)
<div class="data-summary">
    @foreach($summary as $label => $value)
    <div class="summary-card">
        <div class="label">{{ $label }}</div>
        <div class="value">{{ is_numeric($value) ? number_format($value, 2) : $value }}</div>
    </div>
    @endforeach
</div>
@endisset

<div class="chart-container">
    <canvas id="reportChart" height="400"></canvas>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('reportChart').getContext('2d');
    const chartData = @json($chartData);
    
    new Chart(ctx, {
        type: '{{ $chartType ?? 'bar' }}',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: '{{ $dataLabel ?? 'Total Sales' }}',
                data: chartData.values,
                backgroundColor: 'rgba(37, 99, 235, 0.2)',
                borderColor: 'rgba(37, 99, 235, 1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
</script>
@endpush
@endsection
