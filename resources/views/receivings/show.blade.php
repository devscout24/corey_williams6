@extends('layouts.app')

@section('title', 'Details: ' . ($receiving->internal_code ?? 'RCV-' . str_pad($receiving->receiving_id, 8, '0', STR_PAD_LEFT)))
@section('page-title', 'Document Details')

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
                @if($receiving->suspended && !$receiving->is_po)
                    <a href="{{ route('receivings.resume', $receiving->receiving_id) }}" class="btn btn-warning">
                        <i class="bi bi-play-fill me-1"></i> Resume
                    </a>
                @endif
                <a href="{{ route($receiving->is_po ? 'orders.print' : 'purchases.print', $receiving->receiving_id) }}" target="_blank" class="btn btn-primary">
                    <i class="bi bi-printer"></i> Print
                </a>
            </div>
    </div>

    <div class="doc-card">
        <div class="doc-header">
            <div class="doc-title">
                <h2>
                    @if($receiving->is_po) Purchase Order @else Receiving @endif
                    #{{ $receiving->internal_code ?? 'RCV-' . str_pad($receiving->receiving_id, 8, '0', STR_PAD_LEFT) }}
                </h2>
                <p>Created on {{ \Carbon\Carbon::parse($receiving->receiving_time)->format('F d, Y h:i A') }}</p>
            </div>
            <div class="doc-meta">
                @if($receiving->suspended)
                    <span class="badge bg-secondary">Closed / Suspended</span>
                @elseif(!$receiving->closed_at && $receiving->source === 'transfer')
                    <span class="badge bg-warning text-dark">Pending Transfer</span>
                @else
                    <span class="badge bg-success">Open</span>
                @endif

                @if(!$receiving->closed_at && $receiving->source === 'transfer')
                    <form method="POST" action="{{ route('receivings.close-transfer', $receiving->receiving_id) }}" class="d-inline-block mt-2">
                        @csrf
                        <button type="submit" class="btn btn-success w-100" onclick="return confirm('Complete this transfer receiving? Items will be added to inventory.');">
                            <i class="bi bi-check-circle"></i> Complete Transfer
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="doc-info-grid">
            <div class="info-block">
                <strong>Supplier</strong>
                <p>{{ $receiving->supplier->company_name ?? '—' }}</p>
            </div>
            <div class="info-block">
                <strong>Location</strong>
                <p>{{ $receiving->location->name ?? '—' }}</p>
            </div>
            <div class="info-block">
                <strong>Employee</strong>
                <p>{{ $receiving->employee->first_name ?? '' }} {{ $receiving->employee->last_name ?? '—' }}</p>
            </div>
            @if($receiving->source)
            <div class="info-block">
                <strong>Source</strong>
                <p>{{ ucfirst($receiving->source) }} @if($receiving->reference_id) ({{ $receiving->reference_id }}) @endif</p>
            </div>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-center">Qty Purchased</th>
                        <th class="text-center">Qty Received</th>
                        <th class="text-end">Cost Price</th>
                        <th class="text-end">Discount</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($receiving->items as $item)
                    <tr>
                        <td>
                            <div class="fw-semibold">
                                {{ $item->displayName() }}
                            </div>
                            @if($item->item_kit_id)
                                <small class="badge bg-primary-subtle text-primary ms-1">Kit</small>
                            @endif
                        </td>
                        <td class="text-center">{{ (float) $item->quantity_purchased }}</td>
                        <td class="text-center">{{ (float) $item->quantity_received }}</td>
                        <td class="text-end">${{ number_format($item->item_cost_price, 2) }}</td>
                        <td class="text-end">{{ (float) $item->discount_percent }}%</td>
                        <td class="text-end">${{ number_format($item->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="totals-area">
            <table class="totals-table">
                <tr>
                    <td>Subtotal</td>
                    <td class="amount">${{ number_format($receiving->subtotal, 2) }}</td>
                </tr>
                @if((float) $receiving->vat > 0)
                <tr>
                    <td class="text-muted">VAT</td>
                    <td class="amount text-muted">${{ number_format($receiving->vat, 2) }}</td>
                </tr>
                @endif
                <tr class="grand-total">
                    <td>Grand Total</td>
                    <td class="amount">${{ number_format($receiving->total, 2) }}</td>
                </tr>
            </table>
        </div>
        
        @if($receiving->comment)
        <div class="mt-4 pt-3 border-top">
            <h6 class="text-muted text-uppercase" style="font-size: 12px; letter-spacing: 0.5px;">Notes / Comments</h6>
            <p class="mb-0">{{ $receiving->comment }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
