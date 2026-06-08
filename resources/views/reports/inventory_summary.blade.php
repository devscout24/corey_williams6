@php
    $defaultVisible = ['item_id', 'name', 'category', 'quantity', 'cost_price', 'unit_price', 'total_inventory_value'];
@endphp

@extends('layouts.app')

@section('title', $title)
@section('page-title', $title)

@push('styles')
<style>
    .main-content, .page-content { min-width: 0; }

    .summary-cards { margin-bottom: 24px; }
    .summary-data { margin-bottom: 12px; }
    .info-seven {
        background: #fff;
        border: 1px solid var(--gray-200);
        border-radius: 12px;
        padding: 16px;
        text-align: center;
    }
    .info-seven .value {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--gray-900);
    }
    .info-seven p {
        margin: 4px 0 0;
        font-size: 0.8rem;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .report-meta { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; padding: 16px; background: var(--gray-50); border-radius: 12px; border: 1px solid var(--gray-200); }
    .report-meta h4 { margin: 0; color: var(--gray-900); font-size: 1.1rem; }

    .table-responsive { border-radius: 12px; overflow-x: auto; }
    #inventory-table th, #inventory-table td { white-space: nowrap; }
    .table thead th { background: var(--gray-50); border-top: none; color: var(--gray-500); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 16px; }
    .table tbody td { padding: 16px; vertical-align: middle; color: var(--gray-700); border-bottom: 1px solid var(--gray-100); }
    .table tbody tr:last-child td { border-bottom: none; }

    .col-hidden { display: none; }

    .btn-more {
        background: transparent;
        border: 1px solid var(--gray-200);
        border-radius: 8px;
        padding: 6px 12px;
        font-size: 1.2rem;
        line-height: 1;
        color: var(--gray-600);
        position: relative;
        z-index: 5;
    }
    .btn-more:hover {
        background: var(--gray-50);
        color: var(--gray-900);
    }
    .btn-more::after { display: none; }

    .column-picker {
        max-height: 350px;
        overflow-y: auto;
        padding: 8px;
        min-width: 270px;
    }
    .column-picker .dropdown-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 8px 12px;
        border-bottom: 1px solid var(--gray-100);
        margin-bottom: 6px;
    }
    .column-picker .reset-link {
        font-size: 0.8rem;
        text-transform: none;
        letter-spacing: 0;
        cursor: pointer;
        color: var(--primary);
        text-decoration: none;
    }
    .column-picker .reset-link:hover {
        text-decoration: underline;
    }
    .column-picker .form-check {
        padding: 6px 12px;
        border-radius: 6px;
        margin: 0;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .column-picker .form-check:hover {
        background: var(--gray-50);
    }
    .column-picker .form-check-input {
        margin: 0;
        flex-shrink: 0;
    }
    .column-picker .form-check-input:checked {
        background-color: #000;
        border-color: #000;
    }
    .column-picker .form-check-label {
        font-size: 0.9rem;
        cursor: pointer;
        flex: 1;
    }

    @media print {
        .page-header { display: none !important; }
        .page-content .actions { display: none !important; }
        .table-responsive { border: none !important; }
        .col-hidden { display: none !important; }
        .summary-data { break-inside: avoid; }
        .info-seven { border: 1px solid #ddd !important; box-shadow: none !important; }
        .hidden-print { display: none !important; }
    }
</style>
@endpush

@section('content')
@if(isset($overallSummary) && count($overallSummary) > 0)
<div class="row summary-cards">
    @foreach($overallSummary as $name => $value)
    <div class="col-md-4 col-sm-6 summary-data">
        <div class="info-seven">
            <div class="value">
                @if(in_array($name, ['total_items_in_inventory', 'number_items_counted']))
                    {{ number_format($value) }}
                @else
                    {{ number_format($value, 2) }}
                @endif
            </div>
            <p>{{ $summaryLabels[$name] ?? ucwords(str_replace('_', ' ', $name)) }}</p>
        </div>
    </div>
    @endforeach
</div>
@endif

<div class="report-meta">
    <div>
        <h4>{{ $title }}</h4>
        <p class="mb-0 text-muted small">Range: {{ $startDate }} to {{ $endDate }}</p>
    </div>
    <div class="actions d-flex align-items-center gap-2">
        <div class="dropdown hidden-print">
            <button type="button" class="btn btn-more dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Toggle columns">
                <i class="bi bi-gear"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end shadow border-0 column-picker">
                <div class="dropdown-header">
                    <span>Columns</span>
                    <a class="reset-link" id="resetColumns"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
                @foreach($headers as $label => $key)
                <div class="form-check">
                    <input class="form-check-input toggle-col" type="checkbox" value="{{ $key }}" id="colcb-{{ $key }}" {{ in_array($key, $defaultVisible) ? 'checked' : '' }}>
                    <label class="form-check-label" for="colcb-{{ $key }}">{{ $label }}</label>
                </div>
                @endforeach
            </div>
        </div>
        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light hidden-print">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary hidden-print">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-sm btn-outline-secondary hidden-print">
            <i class="bi bi-filetype-csv me-1"></i> CSV
        </a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn btn-sm btn-outline-secondary hidden-print">
            <i class="bi bi-filetype-pdf me-1"></i> PDF
        </a>
    </div>
</div>

<div class="table-responsive shadow-sm">
    <table class="table mb-0" id="inventory-table">
        <thead>
            <tr>
                @foreach($headers as $label => $key)
                    <th class="col-{{ $key }} {{ !in_array($key, $defaultVisible) ? 'col-hidden' : '' }}">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                <tr>
                    @foreach($headers as $label => $key)
                        <td class="col-{{ $key }} {{ !in_array($key, $defaultVisible) ? 'col-hidden' : '' }}">
                            @php
                                $val = $row->$key ?? 0;
                                if ($key === 'order_amount') {
                                    $val = max(0, ($row->replenish_level ?? 0) - ($row->quantity ?? 0));
                                }
                            @endphp
                            @if(is_numeric($val) && str_contains((string)$val, '.'))
                                {{ number_format($val, 2) }}
                            @elseif(is_numeric($val))
                                {{ $val }}
                            @else
                                {{ $val }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="text-center py-5 text-muted">
                        No data found for the selected period.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="text-center mt-4 hidden-print">
    <button onclick="window.print()" class="btn btn-primary">
        <i class="bi bi-printer me-1"></i> Print
    </button>
    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-secondary">
        <i class="bi bi-filetype-csv me-1"></i> CSV
    </a>
    <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn btn-outline-secondary">
        <i class="bi bi-filetype-pdf me-1"></i> PDF
    </a>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var STORAGE_KEY = 'inv_summary_cols';
    var defaults = @json($defaultVisible);

    function loadSaved() {
        var saved = localStorage.getItem(STORAGE_KEY);
        if (!saved) return null;
        try { return JSON.parse(saved); } catch(e) { return null; }
    }

    function saveState() {
        var state = {};
        document.querySelectorAll('.toggle-col').forEach(function(cb) {
            state[cb.value] = cb.checked;
        });
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    }

    function applyColumn(col, visible) {
        document.querySelectorAll('.col-' + col).forEach(function(el) {
            el.classList.toggle('col-hidden', !visible);
        });
    }

    var saved = loadSaved();
    if (saved) {
        document.querySelectorAll('.toggle-col').forEach(function(cb) {
            var col = cb.value;
            if (saved.hasOwnProperty(col)) {
                cb.checked = saved[col];
                applyColumn(col, saved[col]);
            }
        });
    }

    document.querySelectorAll('.toggle-col').forEach(function(cb) {
        cb.addEventListener('change', function() {
            applyColumn(this.value, this.checked);
            saveState();
        });
    });

    document.getElementById('resetColumns').addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.toggle-col').forEach(function(cb) {
            var col = cb.value;
            var visible = defaults.indexOf(col) !== -1;
            cb.checked = visible;
            applyColumn(col, visible);
        });
        localStorage.removeItem(STORAGE_KEY);
    });
});
</script>
@endpush