@php
    $defaultVisible = ['item_id', 'name', 'category', 'quantity', 'cost_price', 'unit_price', 'effective_reorder_level'];
@endphp

@extends('layouts.app')

@section('title', $title)
@section('page-title', $title)

@push('styles')
<style>
    .main-content, .page-content { min-width: 0; }

    .report-meta { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; padding: 16px; background: var(--gray-50); border-radius: 12px; border: 1px solid var(--gray-200); }
    .report-meta h4 { margin: 0; color: var(--gray-900); font-size: 1.1rem; }

    .table-container { background: #fff; border-radius: 12px; box-shadow: var(--shadow-sm); overflow: hidden; }
    .table thead th { background: var(--gray-50); border-top: none; color: var(--gray-500); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 16px; }
    .table tbody td { padding: 12px 16px; vertical-align: middle; color: var(--gray-700); border-bottom: 1px solid var(--gray-100); }
    .table tbody tr:last-child td { border-bottom: none; }

    .expand-btn { cursor: pointer; user-select: none; font-weight: bold; font-size: 1.1rem; }
    .variation-detail { display: none; }
    .variation-detail td { padding: 8px 16px !important; background: #fafbfc; }
    .variation-detail .inner-table { margin: 0; }
    .variation-detail .inner-table td { padding: 6px 12px !important; font-size: 0.85rem; background: transparent; border-bottom: 1px solid var(--gray-100); }
    .variation-detail .inner-table th { font-size: 0.75rem; padding: 6px 12px; background: #f0f2f5; text-transform: uppercase; letter-spacing: 0.3px; }

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

    .col-hidden { display: none; }

    @media print {
        .page-header { display: none !important; }
        .page-content .actions { display: none !important; }
        .table-container { border: none !important; box-shadow: none !important; }
        .col-hidden { display: none !important; }
        .hidden-print { display: none !important; }
        .variation-detail { display: table-row !important; }
    }
</style>
@endpush

@section('content')
<div class="report-meta">
    <div>
        <h4>{{ $title }}</h4>
        <p class="mb-0 text-muted small">Range: {{ $startDate }} to {{ $endDate }}</p>
    </div>
    <div class="actions d-flex align-items-center gap-2 hidden-print">
        <div class="dropdown">
            <button type="button" class="btn btn-more dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Toggle columns">
                <i class="bi bi-gear"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end shadow border-0 column-picker">
                <div class="dropdown-header">
                    <span>Columns</span>
                    <a class="reset-link" id="resetColumns"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
                @foreach([
                    'Item ID' => 'item_id',
                    'Item Name' => 'name',
                    'Category' => 'category',
                    'Supplier' => 'supplier',
                    'Item Number' => 'item_number',
                    'Product ID' => 'product_id',
                    'Description' => 'description',
                    'Size' => 'size',
                    'Cost Price' => 'cost_price',
                    'Unit Price' => 'unit_price',
                    'Quantity' => 'quantity',
                    'Pending Inventory' => 'pending_inventory',
                    'Reorder Level' => 'effective_reorder_level',
                    'Replenish Level' => 'effective_replenish_level',
                    'Order Amount' => 'order_amount',
                ] as $label => $key)
                <div class="form-check">
                    <input class="form-check-input toggle-col" type="checkbox" value="{{ $key }}" id="colcb-{{ $key }}" {{ in_array($key, $defaultVisible) ? 'checked' : '' }}>
                    <label class="form-check-label" for="colcb-{{ $key }}">{{ $label }}</label>
                </div>
                @endforeach
            </div>
        </div>
        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-filetype-csv me-1"></i> CSV
        </a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-filetype-pdf me-1"></i> PDF
        </a>
        <a href="{{ route('reports.generate', $report) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-arrow-repeat me-1"></i> Regenerate
        </a>
    </div>
</div>

<div class="table-responsive shadow-sm">
    <table class="table mb-0" id="inventory-low-table">
        <thead>
            <tr>
                @if($variations->isNotEmpty())
                <th style="width: 32px;"><a href="#" class="expand-all" style="text-decoration:none;color:inherit;font-weight:bold;">+</a></th>
                @endif
                @foreach([
                    'item_id' => 'Item ID',
                    'name' => 'Item Name',
                    'category' => 'Category',
                    'supplier' => 'Supplier',
                    'item_number' => 'Item Number',
                    'product_id' => 'Product ID',
                    'description' => 'Description',
                    'size' => 'Size',
                    'cost_price' => 'Cost Price',
                    'unit_price' => 'Unit Price',
                    'quantity' => 'Quantity',
                    'pending_inventory' => 'Pending Inventory',
                    'effective_reorder_level' => 'Reorder Level',
                    'effective_replenish_level' => 'Replenish Level',
                    'order_amount' => 'Order Amount',
                ] as $key => $label)
                    <th class="col-{{ $key }} {{ !in_array($key, $defaultVisible) ? 'col-hidden' : '' }}">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            <tr>
                @if($variations->isNotEmpty())
                <td>
                    @if(isset($variations[$item->item_id]) && $variations[$item->item_id]->isNotEmpty())
                    <a href="#" class="expand-item" data-item-id="{{ $item->item_id }}" style="text-decoration:none;color:inherit;font-weight:bold;">+</a>
                    @endif
                </td>
                @endif
                <td class="col-item_id">{{ $item->item_id }}</td>
                <td class="col-name">{{ $item->name }}</td>
                <td class="col-category {{ !in_array('category', $defaultVisible) ? 'col-hidden' : '' }}">{{ $item->category ?? '' }}</td>
                <td class="col-supplier {{ !in_array('supplier', $defaultVisible) ? 'col-hidden' : '' }}">{{ $item->supplier ?? '' }}</td>
                <td class="col-item_number {{ !in_array('item_number', $defaultVisible) ? 'col-hidden' : '' }}">{{ $item->item_number ?? '' }}</td>
                <td class="col-product_id {{ !in_array('product_id', $defaultVisible) ? 'col-hidden' : '' }}">{{ $item->product_id ?? '' }}</td>
                <td class="col-description {{ !in_array('description', $defaultVisible) ? 'col-hidden' : '' }}">{{ $item->description ?? '' }}</td>
                <td class="col-size {{ !in_array('size', $defaultVisible) ? 'col-hidden' : '' }}">{{ $item->size ?? '' }}</td>
                <td class="col-cost_price {{ !in_array('cost_price', $defaultVisible) ? 'col-hidden' : '' }}">{{ number_format($item->cost_price ?? 0, 2) }}</td>
                <td class="col-unit_price {{ !in_array('unit_price', $defaultVisible) ? 'col-hidden' : '' }}">{{ number_format($item->unit_price ?? 0, 2) }}</td>
                <td class="col-quantity {{ !in_array('quantity', $defaultVisible) ? 'col-hidden' : '' }}">{{ number_format($item->quantity ?? 0, 2) }}</td>
                <td class="col-pending_inventory {{ !in_array('pending_inventory', $defaultVisible) ? 'col-hidden' : '' }}">{{ number_format($item->pending_inventory ?? 0, 2) }}</td>
                <td class="col-effective_reorder_level {{ !in_array('effective_reorder_level', $defaultVisible) ? 'col-hidden' : '' }}">{{ $item->effective_reorder_level !== null ? number_format($item->effective_reorder_level, 2) : '' }}</td>
                <td class="col-effective_replenish_level {{ !in_array('effective_replenish_level', $defaultVisible) ? 'col-hidden' : '' }}">{{ $item->effective_replenish_level !== null ? number_format($item->effective_replenish_level, 2) : '' }}</td>
                <td class="col-order_amount {{ !in_array('order_amount', $defaultVisible) ? 'col-hidden' : '' }}">
                    @php
                        $orderAmt = max(0, ($item->effective_replenish_level ?? 0) - ($item->quantity ?? 0));
                    @endphp
                    {{ number_format($orderAmt, 2) }}
                </td>
            </tr>
            @if(isset($variations[$item->item_id]) && $variations[$item->item_id]->isNotEmpty())
            <tr class="variation-detail" id="variations-{{ $item->item_id }}">
                <td colspan="{{ 16 + ($variations->isNotEmpty() ? 1 : 0) }}" style="padding: 0 !important;">
                    <table class="inner-table table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Variation</th>
                                <th>Attributes</th>
                                <th>Item Number</th>
                                <th>Cost Price</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th>Pending</th>
                                <th>Reorder Level</th>
                                <th>Replenish Level</th>
                                <th>Order Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($variations[$item->item_id] as $var)
                            <tr>
                                <td>{{ $var->variation_name }}</td>
                                <td>{{ $var->attribute_names ?? '' }}</td>
                                <td>{{ $var->variation_item_number ?? '' }}</td>
                                <td>{{ number_format($var->variation_cost_price ?? 0, 2) }}</td>
                                <td>{{ number_format($var->variation_unit_price ?? 0, 2) }}</td>
                                <td>{{ number_format($var->variation_quantity ?? 0, 2) }}</td>
                                <td>{{ number_format($var->variation_pending_inventory ?? 0, 2) }}</td>
                                <td>{{ $var->variation_reorder_level !== null ? number_format($var->variation_reorder_level, 2) : '' }}</td>
                                <td>{{ $var->variation_replenish_level !== null ? number_format($var->variation_replenish_level, 2) : '' }}</td>
                                <td>
                                    @php
                                        $varOrderAmt = max(0, ($var->variation_replenish_level ?? 0) - ($var->variation_quantity ?? 0));
                                    @endphp
                                    {{ number_format($varOrderAmt, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
            @endif
            @empty
            <tr>
                <td colspan="{{ 16 + ($variations->isNotEmpty() ? 1 : 0) }}" class="text-center py-5 text-muted">
                    No items found matching the criteria.
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
    var STORAGE_KEY = 'inv_low_cols';
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

    // Variation expand/collapse
    var expandAll = document.querySelector('.expand-all');
    if (expandAll) {
        expandAll.addEventListener('click', function(e) {
            e.preventDefault();
            var isExpand = this.textContent === '+';
            this.textContent = isExpand ? '-' : '+';
            document.querySelectorAll('.expand-item').forEach(function(el) {
                el.textContent = isExpand ? '-' : '+';
            });
            document.querySelectorAll('.variation-detail').forEach(function(el) {
                el.style.display = isExpand ? 'table-row' : 'none';
            });
        });
    }

    document.addEventListener('click', function(e) {
        var link = e.target.closest('.expand-item');
        if (!link) return;
        e.preventDefault();
        var itemId = link.dataset.itemId;
        var detailsRow = document.getElementById('variations-' + itemId);
        if (!detailsRow) return;
        if (link.textContent === '+') {
            link.textContent = '-';
            detailsRow.style.display = 'table-row';
        } else {
            link.textContent = '+';
            detailsRow.style.display = 'none';
        }
    });
});
</script>
@endpush
