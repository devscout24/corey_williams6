@extends('layouts.app')

@section('title', ($settings->title ?? 'Receipt') . ' #' . $sale->sale_id)
@section('page-title', 'Receipt')

@push('styles')
<style>
    .sidebar, .topbar, .page-header, .sidebar-overlay { display: none; }
    .main-content { margin-left: 0; }
    .toolbar { position: sticky; top: 0; background: #fff; border-bottom: 1px solid #d1d5db; padding: 10px 12px; display: flex; justify-content: space-between; }
    .toolbar a, .toolbar button { background: #0f766e; color: #fff; border: 0; border-radius: 8px; padding: 8px 12px; text-decoration: none; cursor: pointer; }
    .receipt-page { max-width: 980px; margin: 16px auto; background: #fff; border: 1px solid #d1d5db; border-radius: 12px; padding: 18px; font-family: "Calibri", sans-serif; }
    .receipt-header { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
    .receipt-section { margin-top: 18px; }
    .receipt-title { font-size: 1.2rem; font-weight: 700; margin: 0 0 6px; }
    .company-logo { max-width: 180px; max-height: 70px; }
    .receipt-muted { color: #64748b; font-size: 0.92rem; }
    .receipt-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .receipt-table th, .receipt-table td { border-bottom: 1px solid #e2e8f0; padding: 8px 6px; font-size: 0.95rem; }
    .receipt-table th { text-transform: uppercase; letter-spacing: .02em; font-size: 0.8rem; color: #475569; }
    .totals { display: grid; grid-template-columns: 1fr auto; gap: 6px 12px; max-width: 360px; margin-left: auto; }
    .totals strong { font-size: 1.1rem; }
    .border-top { border-top: 1px dashed #cbd5e1; margin-top: 14px; padding-top: 12px; }
    .receipt-footer { margin-top: 16px; text-align: center; color: #475569; }
    .receipt-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .return-grid { display: grid; grid-template-columns: 1fr 140px; gap: 8px; margin-bottom: 8px; }

    @media (max-width: 900px) {
        .receipt-header { grid-template-columns: 1fr; }
        .totals { margin-left: 0; }
    }

    @media print {
        .toolbar { display: none; }
        .sidebar, .topbar, .page-header, .sidebar-overlay { display: none; }
    }
</style>
@endpush

@section('content')
<div class="toolbar">
    <div class="receipt-actions">
        <a href="{{ route('sales.index') }}">Back to Sales</a>
        <a href="{{ route('sales.settings') }}">Receipt Settings</a>
    </div>
    <button onclick="window.print()">Print Receipt</button>
</div>

<main class="receipt-page">
    <section class="receipt-header">
        <div>
            @if($companyLogoUrl)
                <img src="{{ $companyLogoUrl }}" alt="Company logo" class="company-logo">
            @endif
            <div class="receipt-title">{{ $company ?: 'Company' }}</div>
            @if($taxId)
                <div class="receipt-muted">Tax ID: {{ $taxId }}</div>
            @endif
            <div>{{ $sale->location_name }}</div>
            <div class="receipt-muted">
                {{ trim($sale->address_1 . ' ' . $sale->address_2) }}
                @if($sale->city || $sale->state || $sale->zip)
                    <div>{{ trim($sale->city . ' ' . $sale->state . ' ' . $sale->zip) }}</div>
                @endif
                @if($sale->country)
                    <div>{{ $sale->country }}</div>
                @endif
            </div>
            @if($sale->location_phone)
                <div class="receipt-muted">{{ $sale->location_phone }}</div>
            @endif
            @if($website)
                <div class="receipt-muted">{{ $website }}</div>
            @endif
        </div>

        <div class="receipt-muted" style="text-align:center;">
            <div class="receipt-title">{{ $receiptTitle ?: ($settings->title ?? 'Sales Receipt') }}</div>
            <div>{{ \Illuminate\Support\Carbon::parse($sale->created_at)->format('Y-m-d H:i:s') }}</div>
            <div>Sale #{{ $sale->sale_id }}</div>
            @if($sale->sale_type && $sale->sale_type !== 'sale')
                <div>{{ ucfirst($sale->sale_type) }}</div>
            @endif
            @if(($settings->show_cashier ?? 1))
                <div>Employee: {{ $sale->first_name }} {{ $sale->last_name }}</div>
            @endif
        </div>

        <div>
            @if(($settings->show_customer ?? 1) && $sale->customer_name)
                <div class="receipt-title" style="font-size:1rem;">Invoice To</div>
                <div>{{ $sale->customer_name }}</div>
            @endif
        </div>
    </section>

    <section class="receipt-section">
        <table class="receipt-table">
            <thead>
                <tr>
                    <th style="text-align:left;">Item</th>
                    <th style="text-align:right;">Price</th>
                    <th style="text-align:right;">Qty</th>
                    <th style="text-align:right;">Returned</th>
                    <th style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lines as $line)
                    <tr>
                        <td>
                            <div>{{ $line['item_name'] }}</div>
                            @if($line['item_number'])
                                <div class="receipt-muted">#{{ $line['item_number'] }}</div>
                            @elseif($line['product_id'])
                                <div class="receipt-muted">{{ $line['product_id'] }}</div>
                            @endif
                            @if($line['size'])
                                <div class="receipt-muted">Size: {{ $line['size'] }}</div>
                            @endif
                            @if($line['description'])
                                <div class="receipt-muted">{{ $line['description'] }}</div>
                            @endif
                        </td>
                        <td style="text-align:right;">${{ number_format((float) $line['item_unit_price'], 2) }}</td>
                        <td style="text-align:right;">{{ rtrim(rtrim(number_format((float) $line['quantity_purchased'], 3, '.', ''), '0'), '.') }}</td>
                        <td style="text-align:right;">{{ rtrim(rtrim(number_format((float) $line['returned_qty'], 3, '.', ''), '0'), '.') }}</td>
                        <td style="text-align:right;">${{ number_format((float) $line['line_total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="receipt-section">
        <div class="totals">
            <div>Subtotal</div><div>${{ number_format((float) $sale->subtotal, 2) }}</div>
            <div>Total</div><div>${{ number_format((float) $sale->total, 2) }}</div>
            <div>Tendered</div><div>${{ number_format((float) $sale->amount_tendered, 2) }}</div>
            <div>Change</div><div>${{ number_format((float) $sale->change_due, 2) }}</div>
            <div>Items Sold</div><div>{{ rtrim(rtrim(number_format($itemsSold, 3, '.', ''), '0'), '.') }}</div>
            @if($itemsReturned > 0)
                <div>Items Returned</div><div>{{ rtrim(rtrim(number_format($itemsReturned, 3, '.', ''), '0'), '.') }}</div>
            @endif
        </div>
    </section>

    <section class="receipt-section border-top">
        <div class="receipt-title" style="font-size:1rem;">Payments</div>
        <table class="receipt-table">
            <tbody>
                @foreach($payments as $payment)
                    <tr>
                        <td>{{ $payment['payment_type'] }}</td>
                        <td style="text-align:right;">${{ number_format((float) $payment['payment_amount'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    @if($sale->comment)
        <section class="receipt-section">
            <div class="receipt-title" style="font-size:1rem;">Comment</div>
            <div class="receipt-muted">{{ $sale->comment }}</div>
        </section>
    @endif

    @if($returnPolicy)
        <section class="receipt-section">
            <div class="receipt-title" style="font-size:1rem;">Return Policy</div>
            <div class="receipt-muted">{!! nl2br(e($returnPolicy)) !!}</div>
        </section>
    @endif

    <section class="receipt-section border-top">
        <div class="receipt-title" style="font-size:1rem;">Process Return (Against This Sale)</div>
        <form method="post" action="{{ route('sales.return', ['sale' => $sale->sale_id]) }}">
            @csrf
            @foreach($lines as $idx => $line)
                <div class="return-grid">
                    <label>
                        {{ $line['item_name'] }} (max {{ rtrim(rtrim(number_format((float) $line['returnable_qty'], 3, '.', ''), '0'), '.') }})
                        <input type="hidden" name="returns[{{ $idx }}][sale_item_id]" value="{{ $line['id'] }}">
                    </label>
                    <label>
                        Qty
                        <input type="number" name="returns[{{ $idx }}][quantity]" min="0" step="0.001" value="0" class="form-control form-control-sm">
                    </label>
                </div>
            @endforeach
            <label class="w-100">
                Reason
                <input type="text" name="reason" maxlength="500" placeholder="Optional" class="form-control form-control-sm">
            </label>
            <button type="submit" class="btn btn-sm btn-primary mt-2">Post Return</button>
        </form>
    </section>

    <div class="receipt-footer">{{ $settings->footer ?? 'Thank you' }}</div>
</main>
@endsection
