@extends('layouts.app')

@section('title', $title)
@section('page-title', $title)

@push('styles')
<style>
    /* ── Report meta bar ───────────────────────────────────────────── */
    .report-meta {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 28px;
        padding: 18px 20px;
        background: var(--gray-50);
        border-radius: 12px;
        border: 1px solid var(--gray-200);
    }
    .report-meta h4 { margin: 0; color: var(--gray-900); font-size: 1.15rem; font-weight: 700; }

    /* ── Output-tax section cards ─────────────────────────────────── */
    .ot-section {
        background: #fff;
        border-radius: 14px;
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        margin-bottom: 28px;
    }

    .ot-section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 18px 24px;
        border-bottom: 1px solid var(--gray-100);
    }
    .ot-section-header .ot-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .ot-badge.standard  { background: #e8f5e9; color: #2e7d32; }
    .ot-badge.zero      { background: #e3f2fd; color: #1565c0; }
    .ot-badge.exempt    { background: #fce4ec; color: #880e4f; }

    .ot-section-header h5 {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray-800);
    }

    /* ── Three-column stats grid ──────────────────────────────────── */
    .ot-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0;
    }
    .ot-stat-cell {
        padding: 24px 28px;
        border-right: 1px solid var(--gray-100);
    }
    .ot-stat-cell:last-child { border-right: none; }

    .ot-stat-cell .stat-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: var(--gray-400);
        font-weight: 600;
        margin-bottom: 8px;
    }
    .ot-stat-cell .stat-value {
        font-size: 1.55rem;
        font-weight: 700;
        color: var(--gray-900);
        font-variant-numeric: tabular-nums;
    }
    .ot-stat-cell.vat-cell .stat-value { color: #1a7f37; }

    /* ── Grand total footer ───────────────────────────────────────── */
    .ot-grand-total {
        background: #fff;
        border-radius: 14px;
        box-shadow: var(--shadow-sm);
        padding: 22px 28px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-left: 5px solid var(--primary, #0d6efd);
    }
    .ot-grand-total .gt-label {
        font-size: 0.85rem;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: 0.8px;
        font-weight: 700;
    }
    .ot-grand-total .gt-value {
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--gray-900);
    }
    .ot-grand-total .gt-vat {
        font-size: 1.35rem;
        font-weight: 800;
        color: #1a7f37;
    }

    @media print {
        .actions { display: none !important; }
        .ot-section, .ot-grand-total {
            box-shadow: none !important;
            border: 1px solid #ccc;
        }
    }
</style>
@endpush

@section('content')

{{-- ── Meta Bar ─────────────────────────────────────────────────────── --}}
<div class="report-meta">
    <div>
        <h4><i class="bi bi-receipt me-2"></i>{{ $title }}</h4>
        <p class="mb-0 text-muted small">Period: {{ $startDate }} to {{ $endDate }}</p>
    </div>
    <div class="actions d-flex gap-2">
        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light">
            <i class="bi bi-arrow-left me-1"></i> Back to Reports
        </a>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <a href="{{ route('reports.generate', $report) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-arrow-repeat me-1"></i> Regenerate
        </a>
    </div>
</div>

{{-- ── Helper: format currency ─────────────────────────────────────── --}}
@php
    $fmt = fn(float $v): string => number_format($v, 2);

    $sections = [
        [
            'key'       => 'standard',
            'badge'     => 'standard',
            'icon'      => 'bi-check-circle-fill',
            'label'     => 'Standard Rated Supplies (Sales) – VAT Incl.',
            'col_total' => 'Total Sales of Standard Rated Items Including VAT',
            'col_vat'   => 'VAT on Total Sales of Standard Rated Items',
            'desc'      => 'Items with an active tax rate (VAT > 0)',
        ],
        [
            'key'       => 'zero_rated',
            'badge'     => 'zero',
            'icon'      => 'bi-dash-circle-fill',
            'label'     => 'Zero Rated Supplies (Sales) – VAT Incl.',
            'col_total' => 'Total Sales of Zero Rated Items Including VAT',
            'col_vat'   => 'VAT on Total Sales of Zero Rated Items',
            'desc'      => 'Items assigned a tax class with 0 % rate',
        ],
        [
            'key'       => 'exempt',
            'badge'     => 'exempt',
            'icon'      => 'bi-slash-circle-fill',
            'label'     => 'Exempt Supplies (Sales) – VAT Incl.',
            'col_total' => 'Total Sales of Exempt Items Including VAT',
            'col_vat'   => 'VAT on Total Sales of Exempt Items',
            'desc'      => 'Items with no tax class assigned',
        ],
    ];

    $grandTotal    = array_sum(array_column($outputTaxData, 'total_incl_vat'));
    $grandVat      = array_sum(array_column($outputTaxData, 'vat_amount'));
@endphp

{{-- ── Supply Sections ─────────────────────────────────────────────── --}}
@foreach($sections as $sec)
@php
    $row = $outputTaxData[$sec['key']];
@endphp
<div class="ot-section">
    <div class="ot-section-header">
        <span class="ot-badge {{ $sec['badge'] }}">
            <i class="bi {{ $sec['icon'] }}"></i>
            {{ ucfirst(str_replace('_', ' ', $sec['key'])) }}
        </span>
        <div>
            <h5>{{ $sec['label'] }}</h5>
            <p class="mb-0 text-muted small">{{ $sec['desc'] }}</p>
        </div>
    </div>

    <div class="ot-stats">
        {{-- Column 1: Section label / description --}}
        <div class="ot-stat-cell">
            <div class="stat-label">Supply Type</div>
            <div class="stat-value" style="font-size:1rem; font-weight:600; color:var(--gray-600);">
                {{ $sec['label'] }}
            </div>
        </div>

        {{-- Column 2: Total Sales incl. VAT --}}
        <div class="ot-stat-cell">
            <div class="stat-label">{{ $sec['col_total'] }}</div>
            <div class="stat-value">{{ $fmt($row['total_incl_vat']) }}</div>
        </div>

        {{-- Column 3: VAT Amount --}}
        <div class="ot-stat-cell vat-cell">
            <div class="stat-label">{{ $sec['col_vat'] }}</div>
            <div class="stat-value">{{ $fmt($row['vat_amount']) }}</div>
        </div>
    </div>
</div>
@endforeach

{{-- ── Grand Total ──────────────────────────────────────────────────── --}}
<div class="ot-grand-total">
    <div>
        <div class="gt-label">Grand Total – All Supplies (VAT Incl.)</div>
        <div class="text-muted small mt-1">Sum of Standard + Zero Rated + Exempt</div>
    </div>
    <div class="d-flex gap-5 align-items-center">
        <div class="text-end">
            <div class="gt-label">Total Sales Incl. VAT</div>
            <div class="gt-value">{{ $fmt($grandTotal) }}</div>
        </div>
        <div class="text-end">
            <div class="gt-label">Total Output VAT</div>
            <div class="gt-vat">{{ $fmt($grandVat) }}</div>
        </div>
    </div>
</div>

@endsection
