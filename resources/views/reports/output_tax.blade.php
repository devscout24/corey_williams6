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

    /* ── Section card ──────────────────────────────────────────────── */
    .ot-section, .it-section {
        background: var(--gray-50);
        border-radius: 14px;
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        margin-bottom: 28px;
    }
    .it-section { margin-top: 32px; }
    .ot-section-header, .it-section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 18px 24px;
        border-bottom: 1px solid var(--gray-100);
    }
    .ot-section-header h5, .it-section-header h5 {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray-800);
    }
    .ot-section-header .ot-badge, .it-section-header .it-badge {
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
    .it-badge           { background: #f3e5f5; color: #7b1fa2; }

    /* ── Shared table styles ───────────────────────────────────────── */
    .ot-table, .it-table {
        width: 100%;
        margin-bottom: 0;
        border-collapse: collapse;
    }
    .ot-table th, .it-table th {
        background: var(--gray-100);
        color: var(--gray-500);
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        padding: 16px 24px;
        border-bottom: 1px solid var(--gray-200);
    }
    .ot-table td, .it-table td {
        padding: 18px 24px;
        border-bottom: 1px solid var(--gray-100);
        font-size: 0.92rem;
        color: var(--gray-800);
        vertical-align: middle;
    }
    .ot-table tr:last-child td, .it-table tr:last-child td {
        border-bottom: none;
    }
    .ot-table .row-label, .it-table .row-title {
        font-weight: 600;
        color: var(--gray-900);
    }
    .ot-table .value-cell, .it-table .value-cell {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--gray-900);
        font-variant-numeric: tabular-nums;
    }
    .ot-table .vat-cell, .it-table .vat-cell {
        font-size: 1.15rem;
        font-weight: 700;
        color: #1a7f37;
        font-variant-numeric: tabular-nums;
    }
    .it-table .blank-cell {
        color: var(--gray-400);
        font-style: italic;
    }
    .ot-table .grand-total td {
        font-weight: 700;
        border-top: 2px solid var(--gray-200);
    }
    .ot-table .grand-total .value-cell,
    .ot-table .grand-total .vat-cell {
        font-size: 1.25rem;
    }
    .ot-table .badge-row {
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }
    .ot-table .badge-row .ot-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.70rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .ot-section-header p, .it-section-header p {
        color: var(--gray-500);
    }

    /* ── Grand total footer ───────────────────────────────────────── */
    .ot-grand-total {
        background: var(--gray-50);
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

    /* ── Net VAT payable card ──────────────────────────────────────── */
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
    .net-vat-section .amount-payable { color: #f87171; }
    .net-vat-section .amount-refundable { color: #4ade80; }
    .net-vat-section .text-white-50 { color: rgba(255,255,255,.5); }

    /* ── Dark mode overrides ────────────────────────────────────────── */
    [data-theme='dark'] .ot-badge.standard { background: #1b5e20; color: #a5d6a7; }
    [data-theme='dark'] .ot-badge.zero     { background: #0d47a1; color: #90caf9; }
    [data-theme='dark'] .ot-badge.exempt   { background: #4a148c; color: #ce93d8; }
    [data-theme='dark'] .it-badge          { background: #4a148c; color: #ce93d8; }
    [data-theme='dark'] .ot-table .vat-cell,
    [data-theme='dark'] .it-table .vat-cell,
    [data-theme='dark'] .ot-grand-total .gt-vat { color: #4ade80; }
    [data-theme='dark'] .net-vat-section {
        background: var(--gray-100);
        color: var(--gray-900);
        box-shadow: var(--shadow-sm);
    }
    [data-theme='dark'] .net-vat-section .text-white-50 { color: var(--gray-500); }
    [data-theme='dark'] .net-vat-section .amount-payable { color: #f87171; }
    [data-theme='dark'] .net-vat-section .amount-refundable { color: #4ade80; }

    /* ── Print ─────────────────────────────────────────────────────── */
    @media print {
        .sidebar, .topbar, .page-header, .sidebar-overlay, .actions { display: none !important; }
        .main-content { margin-left: 0 !important; }
        .page-content { padding: 20px !important; }
        .report-meta { border: none !important; padding: 0 0 16px !important; }
        .ot-section, .it-section, .ot-grand-total {
            box-shadow: none !important;
            border: 1px solid #ccc !important;
            break-inside: avoid;
        }
        .net-vat-section {
            box-shadow: none !important;
            border: 1px solid #ccc !important;
            background: #fff !important;
            color: #000 !important;
        }
        .net-vat-section .net-amount { color: #000 !important; }
        .net-vat-section .text-white-50 { color: #666 !important; }
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
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-download me-1"></i> Download
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                <li>
                    <a class="dropdown-item" href="#" onclick="event.preventDefault(); downloadExport('xls');">
                        <i class="bi bi-file-earmark-excel me-2 text-success"></i> Excel (.xls)
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="#" onclick="event.preventDefault(); downloadExport('csv');">
                        <i class="bi bi-filetype-csv me-2 text-primary"></i> CSV (.csv)
                    </a>
                </li>
            </ul>
        </div>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <a href="{{ route('reports.generate', ['report' => $report, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-sm btn-primary">
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

{{-- ── Output Tax Table ─────────────────────────────────────────────── --}}
<div class="ot-section">
    <div class="ot-section-header">
        <span class="ot-badge standard">
            <i class="bi bi-receipt-cutoff"></i>
            Output Tax
        </span>
        <div>
            <h5>Output Tax (Sales) – VAT Incl.</h5>
            <p class="mb-0 text-muted small">Standard Rated, Zero Rated, and Exempt supplies</p>
        </div>
    </div>

    <table class="ot-table">
        <thead>
            <tr>
                <th>Supply Type</th>
                <th class="text-end">Total Sales Incl. VAT</th>
                <th class="text-end">VAT on Total Sales</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sections as $sec)
            @php $row = $outputTaxData[$sec['key']]; @endphp
            <tr>
                <td class="row-label">
                    <span class="badge-row">
                        <span class="ot-badge {{ $sec['badge'] }}">
                            <i class="bi {{ $sec['icon'] }}"></i>
                            {{ ucfirst(str_replace('_', ' ', $sec['key'])) }}
                        </span>
                        <span>{{ $sec['label'] }}</span>
                    </span>
                </td>
                <td class="text-end value-cell">{{ $fmt($row['total_incl_vat']) }}</td>
                <td class="text-end vat-cell">{{ $fmt($row['vat_amount']) }}</td>
            </tr>
            @endforeach
            <tr>
                <td class="row-label">
                    <span class="badge-row">
                        <span class="ot-badge {{'gray-500'}}">
                            <i class="bi {{ 'bi-dash-circle-fill' }}"></i>
                            Reduced Rate - VAT Incl. 10%
                        </span>
                    </span>
                </td>
                <td class="text-end value-cell">{{ 0 }}</td>
                <td class="text-end vat-cell">{{ 0 }}</td>
            </tr>
            <tr>
                <td class="row-label">
                    <span class="badge-row">
                        <span class="ot-badge {{'gray-500'}}">
                            <i class="bi {{ 'bi-dash-circle-fill' }}"></i>
                            Electricity Supplies / Services (Sales)
                        </span>
                    </span>
                </td>
                <td class="text-end value-cell">{{ 0 }}</td>
                <td class="text-end vat-cell">{{ 0 }}</td>
            </tr>
            {{-- Grand Total row --}}
            <tr class="grand-total">
                <td class="row-label">Grand Total – All Supplies</td>
                <td class="text-end value-cell">{{ $fmt($grandTotal) }}</td>
                <td class="text-end vat-cell">{{ $fmt($grandVat) }}</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- ── Input Tax Section ─────────────────────────────────────────────── --}}
@php
    $importsExVat     = $inputTaxData['imports']['total_excl_vat'];
    $importsVat       = $inputTaxData['imports']['vat_amount'];
    $domesticExVat    = $inputTaxData['domestic']['total_excl_vat'];
    $domesticVat      = $inputTaxData['domestic']['vat_amount'];

    $electricityVat   = 0;
    $nonInventoryVat  = 0;

    $totalInputVat    = $importsVat + $domesticVat + $electricityVat + $nonInventoryVat;
    $totalPurchasesExVat = $importsExVat + $domesticExVat;

    $netVat = $grandVat - $totalInputVat;
@endphp

<div class="it-section">
    <div class="it-section-header" style="flex-wrap: wrap;">
        <div class="d-flex gap-3 align-items-center w-100 mb-2" style="justify-content: flex-end;">
            <label class="small fw-semibold text-muted mb-0">Claimable <input type="text" id="claimable-input" class="form-control form-control-sm d-inline-block" style="width:120px" readonly tabindex="-1"></label>
            <label class="small fw-semibold text-muted mb-0">VAT Non-Inventory <input type="number" id="vat-non-inventory-input" class="form-control form-control-sm d-inline-block" style="width:100px" step="any"></label>
            <label class="small fw-semibold text-muted mb-0">VAT on Electricity <input type="number" id="vat-electricity-input" class="form-control form-control-sm d-inline-block" style="width:100px" step="any"></label>
        </div>
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
            {{-- Row 5: Electricity Consumption VAT --}}
            <tr id="electricity-excl-row">
                <td class="row-title">Value of Electricity Consumption on which VAT was paid</td>
                <td class="text-end value-cell">—</td>
                <td class="text-end blank-cell">—</td>
            </tr>
            {{-- Row 6: VAT Paid, Payable or Claimable --}}
            <tr id="electricity-vat-row">
                <td class="row-title">VAT Paid, Payable or Claimable on Electricity Consumption</td>
                <td class="text-end blank-cell">—</td>
                <td class="text-end vat-cell">0.00</td>
            </tr>
            {{-- Row 7: Non-Inventory --}}
            <tr id="non-inventory-excl-row">
                <td class="row-title">Value of Non-Inventory Purchases (Services, Operating Expenses) on which VAT was paid</td>
                <td class="text-end value-cell">—</td>
                <td class="text-end blank-cell">—</td>
            </tr>
            {{-- Row 8: Non-Inventory VAT --}}
            <tr id="non-inventory-vat-row">
                <td class="row-title">VAT Paid, Payable or Claimable on Non-Inventory Purchases</td>
                <td class="text-end blank-cell">—</td>
                <td class="text-end vat-cell">0.00</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- ── Input Tax Grand Total ────────────────────────────────────────── --}}
<div class="ot-grand-total mt-0 mb-4" style="border-left-color: #7b1fa2;">
    <div>
        <div class="gt-label" style="color: #7b1fa2;">Grand Total – Input Tax (Purchases)</div>
        <div class="text-muted small mt-1">Imports + Domestic + Electricity + Non-Inventory</div>
    </div>
    <div class="d-flex gap-5 align-items-center">
        <div class="text-end">
            <div class="gt-label">Total Purchases Excl. VAT</div>
            <div class="gt-value" id="gt-input-excl-vat">{{ $fmt($totalPurchasesExVat) }}</div>
        </div>
        <div class="text-end">
            <div class="gt-label">Total Input VAT</div>
            <div class="gt-vat" style="color: #7b1fa2;" id="gt-input-vat">{{ $fmt($totalInputVat) }}</div>
        </div>
    </div>
</div>

{{-- ── Net VAT Summary ─────────────────────────────────────────────── --}}
<div class="net-vat-section">
    <div>
        <div class="net-title">Net VAT Status for Period</div>
        <div class="text-white-50 small mt-1" id="net-vat-desc">Output VAT ({{ $fmt($grandVat) }}) − Input VAT ({{ $fmt($totalInputVat) }})</div>
    </div>
    <div class="text-end" id="net-vat-amount">
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

@push('scripts')
<script>
    // ── Fixed inputs from DB ──────────────────────────────────────────
    const IMPORTS_VAT      = {{ $importsVat }};
    const DOMESTIC_VAT     = {{ $domesticVat }};
    const IMPORTS_EXCL     = {{ $importsExVat }};
    const DOMESTIC_EXCL    = {{ $domesticExVat }};
    const GRAND_VAT        = {{ $grandVat }};

    // ── DOM refs ──────────────────────────────────────────────────────
    const electricityExclRow     = document.getElementById('electricity-excl-row');
    const electricityVatRow      = document.getElementById('electricity-vat-row');
    const nonInventoryExclRow    = document.getElementById('non-inventory-excl-row');
    const nonInventoryVatRow     = document.getElementById('non-inventory-vat-row');
    const gtInputExclVat         = document.getElementById('gt-input-excl-vat');
    const gtInputVat             = document.getElementById('gt-input-vat');
    const netVatDesc             = document.getElementById('net-vat-desc');
    const netVatAmount           = document.getElementById('net-vat-amount');

    const vatElectricityInput    = document.getElementById('vat-electricity-input');
    const vatNonInventoryInput   = document.getElementById('vat-non-inventory-input');
    const claimableInput         = document.getElementById('claimable-input');

    // ── Helpers ───────────────────────────────────────────────────────
    function exclFromVat(vat) { return vat / 0.15; }

    function fmt(n) {
        return n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function recalc() {
        const elecVat  = parseFloat(vatElectricityInput.value) || 0;
        const nonInvVat = parseFloat(vatNonInventoryInput.value) || 0;
        const elecExcl  = exclFromVat(elecVat);
        const nonInvExcl = exclFromVat(nonInvVat);

        // Update table cells
        electricityExclRow.querySelector('.value-cell').textContent = elecExcl ? fmt(elecExcl) : '—';
        electricityVatRow.querySelector('.vat-cell').textContent   = fmt(elecVat);
        nonInventoryExclRow.querySelector('.value-cell').textContent  = nonInvExcl ? fmt(nonInvExcl) : '—';
        nonInventoryVatRow.querySelector('.vat-cell').textContent     = fmt(nonInvVat);

        // Totals
        const totalExcl  = IMPORTS_EXCL + DOMESTIC_EXCL + elecExcl + nonInvExcl;
        const totalVat   = IMPORTS_VAT + DOMESTIC_VAT + elecVat + nonInvVat;
        const netVat     = GRAND_VAT - totalVat;

        gtInputExclVat.textContent = fmt(totalExcl);
        gtInputVat.textContent     = fmt(totalVat);

        // Claimable = sum of all input VAT
        claimableInput.value = fmt(totalVat);

        // Net VAT section
        netVatDesc.innerHTML = `Output VAT (${ fmt(GRAND_VAT) }) − Input VAT (${ fmt(totalVat) })`;
        if (netVat >= 0) {
            netVatAmount.innerHTML = `
                <div class="gt-label text-white-50">Net VAT Payable</div>
                <div class="net-amount amount-payable">+$${ fmt(netVat) }</div>
            `;
        } else {
            netVatAmount.innerHTML = `
                <div class="gt-label text-white-50">Net VAT Refundable</div>
                <div class="net-amount amount-refundable">-$${ fmt(Math.abs(netVat)) }</div>
            `;
        }
    }

    // ── Wire inputs ───────────────────────────────────────────────────
    vatElectricityInput.addEventListener('input', recalc);
    vatNonInventoryInput.addEventListener('input', recalc);

    // ── Download ──────────────────────────────────────────────────────
    function downloadExport(format) {
        const elec  = vatElectricityInput.value || '0';
        const nonInv = vatNonInventoryInput.value || '0';
        const url = '{{ route('reports.generate', $report) }}' +
            '?start_date={{ urlencode($startDate) }}' +
            '&end_date={{ urlencode($endDate) }}' +
            '&export_format=' + format +
            '&vat_electricity=' + encodeURIComponent(elec) +
            '&vat_non_inventory=' + encodeURIComponent(nonInv);
        window.location.href = url;
    }
</script>
@endpush
