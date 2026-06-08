@extends('layouts.app')

@section('title', $title)
@section('page-title', $title)

@push('styles')
<style>
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

    @media print {
        .page-header { display: none !important; }
        .page-content .actions { display: none !important; }
        .table-container { border: none !important; }
        .hidden-print { display: none !important; }
    }
</style>
@endpush

@section('content')
<div class="report-meta">
    <div>
        <h4>{{ $title }}</h4>
        <p class="mb-0 text-muted small">Range: {{ $startDate }} to {{ $endDate }}</p>
    </div>
    <div class="actions hidden-print">
        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light me-2">
            <i class="bi bi-arrow-left me-1"></i> Back to Reports
        </a>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-filetype-csv me-1"></i> CSV
        </a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-filetype-pdf me-1"></i> PDF
        </a>
        <a href="{{ route('reports.generate', $report) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-arrow-repeat me-1"></i> Regenerate
        </a>
    </div>

<div class="table-container">
    <table class="table mb-0" id="inventory-low-table">
        <thead>
            <tr>
                @if($variations->isNotEmpty())
                <th style="width: 40px;"><a href="#" class="expand-all" style="text-decoration:none;color:inherit;">+</a></th>
                @endif
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            <tr>
                @if($variations->isNotEmpty())
                <td>
                    @if(isset($variations[$item->item_id]) && $variations[$item->item_id]->isNotEmpty())
                    <a href="#" class="expand-item" data-item-id="{{ $item->item_id }}" style="text-decoration:none;color:inherit;">+</a>
                    @endif
                </td>
                @endif
                <td>{{ $item->item_id }}</td>
                <td>{{ $item->name }}</td>
                <td>{{ $item->category ?? '' }}</td>
                <td>{{ $item->supplier ?? '' }}</td>
                <td>{{ $item->item_number ?? '' }}</td>
                <td>{{ $item->product_id ?? '' }}</td>
                <td>{{ $item->description ?? '' }}</td>
                <td>{{ $item->size ?? '' }}</td>
                <td>{{ number_format($item->cost_price ?? 0, 2) }}</td>
                <td>{{ number_format($item->unit_price ?? 0, 2) }}</td>
                <td>{{ number_format($item->quantity ?? 0, 2) }}</td>
                <td>{{ number_format($item->pending_inventory ?? 0, 2) }}</td>
                <td>{{ $item->effective_reorder_level !== null ? number_format($item->effective_reorder_level, 2) : '' }}</td>
                <td>{{ $item->effective_replenish_level !== null ? number_format($item->effective_replenish_level, 2) : '' }}</td>
                <td>
                    @php
                        $orderAmt = max(0, ($item->effective_replenish_level ?? 0) - ($item->quantity ?? 0));
                    @endphp
                    {{ number_format($orderAmt, 2) }}
                </td>
            </tr>
            @if(isset($variations[$item->item_id]) && $variations[$item->item_id]->isNotEmpty())
            <tr class="variation-detail" id="variations-{{ $item->item_id }}">
                <td colspan="{{ count($headers) + ($variations->isNotEmpty() ? 1 : 0) }}" style="padding: 0 !important;">
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
                <td colspan="{{ count($headers) + ($variations->isNotEmpty() ? 1 : 0) }}" class="text-center py-5 text-muted">
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
$(document).ready(function() {
    $('.expand-all').click(function(e) {
        e.preventDefault();
        var isExpand = $(this).text() === '+';
        $(this).text(isExpand ? '-' : '+');
        $('.expand-item').text(isExpand ? '-' : '+');
        $('.variation-detail').toggle(isExpand);
    });

    $(document).on('click', '.expand-item', function(e) {
        e.preventDefault();
        var $link = $(this);
        var itemId = $link.data('item-id');
        var $detailsRow = $('#variations-' + itemId);

        if ($link.text() === '+') {
            $link.text('-');
            $detailsRow.show();
        } else {
            $link.text('+');
            $detailsRow.hide();
        }
    });
});
</script>
@endpush
