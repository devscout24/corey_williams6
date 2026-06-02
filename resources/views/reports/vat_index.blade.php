@extends('layouts.app')

@section('title', 'VAT Report')
@section('page-title', 'VAT Report')

@push('styles')
<style>
    .vat-page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }
    .vat-page-header h4 {
        margin: 0;
        color: var(--gray-900);
        font-weight: 700;
    }

    .vat-card {
        background: var(--gray-50);
        border-radius: 14px;
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }
    .vat-table {
        width: 100%;
        margin-bottom: 0;
        border-collapse: collapse;
    }
    .vat-table th {
        background: var(--gray-100);
        color: var(--gray-500);
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        padding: 16px 24px;
        border-bottom: 1px solid var(--gray-200);
    }
    .vat-table td {
        padding: 18px 24px;
        border-bottom: 1px solid var(--gray-100);
        font-size: 0.92rem;
        color: var(--gray-800);
        vertical-align: middle;
    }
    .vat-table tr:last-child td { border-bottom: none; }
    .vat-table .period-cell {
        font-weight: 600;
        color: var(--gray-900);
    }
    .vat-table .value-cell {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--gray-900);
        font-variant-numeric: tabular-nums;
    }
    .vat-table .vat-positive { color: #1a7f37; }
    .vat-table .vat-negative { color: #d32f2f; }
    .vat-table .empty-row td {
        color: var(--gray-400);
        text-align: center;
        padding: 40px 24px;
        font-style: italic;
    }
    .btn-view {
        padding: 6px 16px;
        border-radius: 8px;
        font-size: 0.82rem;
        font-weight: 600;
        background: var(--primary);
        color: #fff;
        border: none;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: opacity .15s;
    }
    .btn-view:hover { opacity: .85; color: #fff; }

    [data-theme='dark'] .vat-table .vat-positive { color: #4ade80; }
    [data-theme='dark'] .vat-table .vat-negative { color: #f87171; }
</style>
@endpush

@section('content')
<div class="vat-page-header">
    <div>
        <h4><i class="bi bi-receipt me-2"></i>VAT Report</h4>
        <p class="mb-0 text-muted small">Monthly VAT summary – click any month to view the detailed report</p>
    </div>
    <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light">
        <i class="bi bi-arrow-left me-1"></i> All Reports
    </a>
</div>

<div class="vat-card">
    <table class="vat-table">
        <thead>
            <tr>
                <th>Period</th>
                <th class="text-end">Output (Taxable Value)</th>
                <th class="text-end">Output VAT</th>
                <th class="text-end">Input (Taxable Value)</th>
                <th class="text-end">Input VAT</th>
                <th class="text-end">Net VAT</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($months as $m)
            <tr>
                <td class="period-cell">{{ $m->label }}</td>
                <td class="text-end value-cell">{{ number_format($m->taxable_output, 2) }}</td>
                <td class="text-end value-cell">{{ number_format($m->output_vat, 2) }}</td>
                <td class="text-end value-cell">{{ number_format($m->taxable_input, 2) }}</td>
                <td class="text-end value-cell">{{ number_format($m->input_vat, 2) }}</td>
                <td class="text-end value-cell {{ $m->net_vat >= 0 ? 'vat-positive' : 'vat-negative' }}">
                    {{ $m->net_vat >= 0 ? '+' : '-' }}${{ number_format(abs($m->net_vat), 2) }}
                </td>
                <td class="text-center">
                    <a href="{{ route('reports.generate', ['report' => 'output_tax', 'start_date' => $m->start_date, 'end_date' => $m->end_date]) }}"
                       class="btn-view">
                        <i class="bi bi-eye"></i> View
                    </a>
                </td>
            </tr>
            @empty
            <tr class="empty-row">
                <td colspan="7">No VAT data yet. Complete some sales and purchases to see monthly summaries.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
