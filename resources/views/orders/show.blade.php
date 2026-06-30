@extends('layouts.app')

@section('title', 'Order: ' . ($order->internal_code ?? 'PO-' . str_pad($order->order_id, 8, '0', STR_PAD_LEFT)))
@section('page-title', 'Order Details')

@push('styles')
<style>
    .doc-card {
        background: #fff;
        border-radius: var(--radius-lg);
        border: 1px solid var(--gray-200);
        padding: 30px;
        box-shadow: var(--shadow-sm);
        margin-bottom: 20px;
    }
    .doc-header {
        display: flex;
        justify-content: space-between;
        border-bottom: 1px solid var(--gray-100);
        padding-bottom: 20px;
        margin-bottom: 20px;
    }
    .doc-title h2 {
        margin: 0;
        font-weight: 700;
        color: var(--gray-800);
        font-size: 24px;
    }
    .doc-title p {
        margin: 5px 0 0;
        color: var(--gray-500);
        font-size: 14px;
    }
    .doc-meta {
        text-align: right;
    }
    .doc-meta .badge {
        font-size: 14px;
        padding: 8px 12px;
        margin-bottom: 10px;
    }
    .doc-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .info-block strong {
        display: block;
        color: var(--gray-500);
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }
    .info-block p {
        margin: 0;
        color: var(--gray-800);
        font-size: 15px;
        font-weight: 500;
    }
    .items-table th {
        background: var(--gray-50);
        font-size: 13px;
        text-transform: uppercase;
        color: var(--gray-600);
        border-top: 1px solid var(--gray-200);
        border-bottom: 1px solid var(--gray-200);
    }
    .items-table td {
        vertical-align: middle;
        font-size: 14px;
        border-bottom: 1px solid var(--gray-100);
    }
    .totals-area {
        display: flex;
        justify-content: flex-end;
        margin-top: 20px;
    }
    .totals-table {
        width: 300px;
    }
    .totals-table td {
        padding: 8px 0;
        font-size: 15px;
    }
    .totals-table td.amount {
        text-align: right;
        font-weight: 600;
    }
    .totals-table .grand-total td {
        border-top: 2px solid var(--gray-200);
        font-size: 18px;
        font-weight: 700;
        color: var(--primary);
        padding-top: 12px;
    }
    @media print {
        .navbar, .sidebar, .btn, .doc-card {
            box-shadow: none !important;
            border: none !important;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="javascript:history.back()" class="btn btn-light border">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <div>
            <a href="{{ route('orders.print', $order->order_id) }}" target="_blank" class="btn btn-primary">
                <i class="bi bi-printer"></i> Print
            </a>
        </div>
    </div>

    <div class="doc-card">
        <div class="doc-header">
            <div class="doc-title">
                <h2>Purchase Order #{{ $order->internal_code ?? 'PO-' . str_pad($order->order_id, 8, '0', STR_PAD_LEFT) }}</h2>
                <p>Created on {{ \Carbon\Carbon::parse($order->order_time)->format('F d, Y h:i A') }}</p>
            </div>
            <div class="doc-meta">
                @if($order->suspended)
                    <span class="badge bg-secondary">Closed</span>
                @else
                    <span class="badge bg-success">Open</span>
                @endif
            </div>
        </div>

        <div class="doc-info-grid">
            <div class="info-block">
                <strong>Supplier</strong>
                <p>{{ $order->supplier->company_name ?? '—' }}</p>
            </div>
            <div class="info-block">
                <strong>Location</strong>
                <p>{{ $order->location->name ?? '—' }}</p>
            </div>
            <div class="info-block">
                <strong>Employee</strong>
                <p>{{ $order->employee->first_name ?? '' }} {{ $order->employee->last_name ?? '—' }}</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Product ID</th>
                        <th class="text-center">Qty on Hand</th>
                        <th class="text-center">Qty Ordered</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>
                            <div class="fw-semibold">
                                @if($item->item_id)
                                    {{ $item->item->name ?? 'Unknown Item' }}
                                @elseif($item->item_kit_id)
                                    {{ $item->kit->name ?? 'Unknown Kit' }}
                                @else
                                    {{ $item->description ?? 'Unknown' }}
                                @endif
                            </div>
                            @if($item->item_kit_id && ! $item->item_id)
                                <small class="badge bg-primary-subtle text-primary ms-1">Kit</small>
                            @endif
                        </td>
                        <td>{{ $item->item_id ? ($item->item->product_id ?? '—') : '—' }}</td>
                        <td class="text-center">{{ $qtyOnHand[$item->item_id] ?? $qtyOnHand['kit_' . $item->item_kit_id] ?? 0 }}</td>
                        <td class="text-center">{{ (float) $item->quantity_purchased }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="totals-area">
            <table class="totals-table">
                <tr class="grand-total">
                    <td>Total</td>
                    <td class="amount">${{ number_format($order->total, 2) }}</td>
                </tr>
            </table>
        </div>

        @if($order->comment)
        <div class="mt-4 pt-3 border-top">
            <h6 class="text-muted text-uppercase" style="font-size: 12px; letter-spacing: 0.5px;">Notes / Comments</h6>
            <p class="mb-0">{{ $order->comment }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
