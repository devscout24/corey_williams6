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

    /* ── Input-tax styling ────────────────────────────────────────── */
    .it-section {
        background: #fff;
        border-radius: 14px;
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        margin-top: 32px;
        margin-bottom: 28px;
    }
    .it-section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 18px 24px;
        border-bottom: 1px solid var(--gray-100);
    }
    .it-section-header .it-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        background: #f3e5f5;
        color: #7b1fa2;
    }
    .it-section-header h5 {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray-800);
    }
    .it-table {
        width: 100%;
        margin-bottom: 0;
        border-collapse: collapse;
    }
    .it-table th {
        background: var(--gray-50);
        color: var(--gray-500);
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        padding: 16px 24px;
        border-bottom: 1px solid var(--gray-100);
    }
    .it-table td {
        padding: 18px 24px;
        border-bottom: 1px solid var(--gray-50);
        font-size: 0.92rem;
        color: var(--gray-800);
        vertical-align: middle;
    }
    .it-table tr:last-child td {
        border-bottom: none;
    }
    .it-table .row-title {
        font-weight: 600;
        color: var(--gray-900);
    }
    .it-table .value-cell {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--gray-900);
        font-variant-numeric: tabular-nums;
    }
    .it-table .vat-cell {
        font-size: 1.15rem;
        font-weight: 700;
        color: #1a7f37;
        font-variant-numeric: tabular-nums;
    }
    .it-table .blank-cell {
        color: var(--gray-300);
        font-style: italic;
    }

    /* Net VAT payable card */
    .net-vat-section {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: #fff;
        border-radius: 14px;
        padding: 24px 28px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 32px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    .net-vat-section .net-title {
        font-size: 1.1rem;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    .net-vat-section .net-amount {
        font-size: 2rem;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
    }
    .net-vat-section .amount-payable {
        color: #f87171; /* red-400 */
    }
    .net-vat-section .amount-refundable {
        color: #4ade80; /* green-400 */
    }

    @media print {
        .actions { display: none !important; }
        .ot-section, .ot-grand-total, .it-section, .net-vat-section {
            box-shadow: none !important;
            border: 1px solid #ccc !important;
            background: #fff !important;
            color: #000 !important;
        }
        .net-vat-section {
            background: #fff !important;
            color: #000 !important;
        }
        .net-vat-section .net-amount {
            color: #000 !important;
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
<div class="ot-grand-total mb-4">
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

{{-- ── Input Tax Section ─────────────────────────────────────────────── --}}
@php
    $importsExVat   = $inputTaxData['imports']['total_excl_vat'];
    $importsVat     = $inputTaxData['imports']['vat_amount'];
    $domesticExVat  = $inputTaxData['domestic']['total_excl_vat'];
    $domesticVat    = $inputTaxData['domestic']['vat_amount'];

    $totalInputVat  = $importsVat + $domesticVat;
    $totalPurchasesExVat = $importsExVat + $domesticExVat;

    $netVat = $grandVat - $totalInputVat;
@endphp

<div class="it-section">
    <div class="it-section-header">
        <span class="it-badge">
            <i class="bi bi-cart-check-fill"></i>
            Input Tax
        </span>
        <div>
            <h5>Input Tax (Purchases / Receivings)</h5>
            <p class="mb-0 text-muted small">Import and domestic purchases with VAT details</p>
        </div>
    </div>

    <table class="it-table">
        <thead>
            <tr>
                <th>Purchase / Tax Type</th>
                <th class="text-end">Total Purchases (Excl. VAT)</th>
                <th class="text-end">VAT Paid / Claimable</th>
            </tr>
        </thead>
        <tbody>
            {{-- Row 1: Imports Excl VAT --}}
            <tr>
                <td class="row-title">Value of Imports (Goods & Services)</td>
                <td class="text-end value-cell">{{ $fmt($importsExVat) }}</td>
                <td class="text-end blank-cell">—</td>
            </tr>
            {{-- Row 2: Imports VAT --}}
            <tr>
                <td class="row-title">VAT Paid to Comptroller of Customs on Imports</td>
                <td class="text-end blank-cell">—</td>
                <td class="text-end vat-cell">{{ $fmt($importsVat) }}</td>
            </tr>
            {{-- Row 3: Domestic Excl VAT --}}
            <tr>
                <td class="row-title">Value of Domestic Purchases on which VAT was paid</td>
                <td class="text-end value-cell">{{ $fmt($domesticExVat) }}</td>
                <td class="text-end blank-cell">—</td>
            </tr>
            {{-- Row 4: Domestic VAT --}}
            <tr>
                <td class="row-title">VAT Paid, Payable or Claimable on Local Taxable Supplies (Purchases)</td>
                <td class="text-end blank-cell">—</td>
                <td class="text-end vat-cell">{{ $fmt($domesticVat) }}</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- ── Input Tax Grand Total ────────────────────────────────────────── --}}
<div class="ot-grand-total mt-0 mb-4" style="border-left-color: #7b1fa2;">
    <div>
        <div class="gt-label" style="color: #7b1fa2;">Grand Total – Input Tax (Purchases)</div>
        <div class="text-muted small mt-1">Sum of Imports + Domestic Purchases</div>
    </div>
    <div class="d-flex gap-5 align-items-center">
        <div class="text-end">
            <div class="gt-label">Total Purchases Excl. VAT</div>
            <div class="gt-value">{{ $fmt($totalPurchasesExVat) }}</div>
        </div>
        <div class="text-end">
            <div class="gt-label">Total Input VAT</div>
            <div class="gt-vat" style="color: #7b1fa2;">{{ $fmt($totalInputVat) }}</div>
        </div>
    </div>
</div>

{{-- ── Net VAT Summary ─────────────────────────────────────────────── --}}
<div class="net-vat-section">
    <div>
        <div class="net-title">Net VAT Status for Period</div>
        <div class="text-white-50 small mt-1">Output VAT ({{ $fmt($grandVat) }}) − Input VAT ({{ $fmt($totalInputVat) }})</div>
    </div>
    <div class="text-end">
        @if($netVat >= 0)
            <div class="gt-label text-white-50">Net VAT Payable</div>
            <div class="net-amount amount-payable">+${{ $fmt($netVat) }}</div>
        @else
            <div class="gt-label text-white-50">Net VAT Refundable</div>
            <div class="net-amount amount-refundable">-${{ $fmt(abs($netVat)) }}</div>
        @endif
    </div>
</div>

@endsection
