@extends('layouts.app')

@section('title', ($settings->title ?? 'Store Receipt') . ' #' . $sale->sale_id)
@section('page-title', 'Receipt')

@push('styles')
<style>
    .sidebar, .topbar, .page-header, .sidebar-overlay { display: none; }
    .main-content { margin-left: 0; }
    .toolbar { position: sticky; top: 0; background: #fff; border-bottom: 1px solid #d1d5db; padding: 10px 12px; display: flex; justify-content: space-between; }
    .toolbar a, .toolbar button { background: #0f766e; color: #fff; border: 0; border-radius: 8px; padding: 8px 12px; text-decoration: none; cursor: pointer; }
    .receipt { width: min(420px, calc(100% - 20px)); margin: 14px auto; background: #fff; border: 1px solid #d1d5db; padding: 14px; font-family: "Courier New", monospace; }
    .center { text-align: center; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th, td { border-bottom: 1px dashed #cbd5e1; padding: 6px 2px; font-size: 13px; }
    .totals td { border: 0; }

    @media print {
        .toolbar { display: none; }
        .sidebar, .topbar, .page-header, .sidebar-overlay { display: none; }
    }
</style>
@endpush

@section('content')
<div class="toolbar">
    <a href="{{ route('sales.index') }}">Back to Sales</a>
    <a href="{{ route('sales.settings') }}">Receipt Settings</a>
    <button onclick="window.print()">Print Receipt</button>
</div>

<main class="receipt">
    <div class="center">
        <h2 style="margin:0;">{{ $settings->title ?? 'Store Receipt' }}</h2>
        <div>{{ $sale->location_name }}</div>
        <div>Sale #{{ $sale->sale_id }}</div>
        <div>{{ \Illuminate\Support\Carbon::parse($sale->created_at)->format('Y-m-d H:i:s') }}</div>
        @if(($settings->show_cashier ?? 1))
            <div>Cashier: {{ $sale->first_name }} {{ $sale->last_name }}</div>
        @endif
        @if(($settings->show_customer ?? 1) && $sale->customer_name)
            <div>Customer: {{ $sale->customer_name }}</div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="text-align:left;">Item</th>
                <th style="text-align:right;">Qty</th>
                <th style="text-align:right;">Returned</th>
                <th style="text-align:right;">Price</th>
                <th style="text-align:right;">Line</th>
            </tr>
        </thead>
        <tbody>
        @foreach($lines as $saleLine)
            <tr>
                <td>{{ $saleLine['item_name'] }}</td>
                <td style="text-align:right;">{{ rtrim(rtrim(number_format((float) $saleLine['quantity_purchased'], 3, '.', ''), '0'), '.') }}</td>
                <td style="text-align:right;">{{ rtrim(rtrim(number_format((float) $saleLine['returned_qty'], 3, '.', ''), '0'), '.') }}</td>
                <td style="text-align:right;">${{ number_format((float) $saleLine['item_unit_price'], 2) }}</td>
                <td style="text-align:right;">${{ number_format((float) $saleLine['line_total'], 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals" style="margin-top:10px;">
        <tr><td>Subtotal</td><td style="text-align:right;">${{ number_format((float) $sale->subtotal, 2) }}</td></tr>
        <tr><td>Total</td><td style="text-align:right;">${{ number_format((float) $sale->total, 2) }}</td></tr>
        <tr><td>Tendered</td><td style="text-align:right;">${{ number_format((float) $sale->amount_tendered, 2) }}</td></tr>
        <tr><td>Change</td><td style="text-align:right;">${{ number_format((float) $sale->change_due, 2) }}</td></tr>
    </table>

    <h4 style="margin:10px 0 6px;">Payments</h4>
    <table>
        <tbody>
        @foreach($payments as $payment)
            <tr>
                <td>{{ $payment['payment_type'] }}</td>
                <td style="text-align:right;">${{ number_format((float) $payment['payment_amount'], 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @if($sale->comment)
        <p style="margin-top:10px;"><strong>Comment:</strong> {{ $sale->comment }}</p>
    @endif

    <hr style="margin-top:10px; border:none; border-top:1px dashed #cbd5e1;">
    <h4 style="margin:8px 0;">Process Return (Against This Sale)</h4>
    <form method="post" action="{{ route('sales.return', ['sale' => $sale->sale_id]) }}">
        @csrf
        @foreach($lines as $idx => $saleLine)
            <div style="display:grid; grid-template-columns: 1fr 120px; gap:8px; margin-bottom:6px;">
                <label>
                    {{ $saleLine['item_name'] }} (max {{ rtrim(rtrim(number_format((float) $saleLine['returnable_qty'], 3, '.', ''), '0'), '.') }})
                    <input type="hidden" name="returns[{{ $idx }}][sale_item_id]" value="{{ $saleLine['id'] }}">
                </label>
                <label>
                    Qty
                    <input type="number" name="returns[{{ $idx }}][quantity]" min="0" step="0.001" value="0">
                </label>
            </div>
        @endforeach
        <label>
            Reason
            <input type="text" name="reason" maxlength="500" placeholder="Optional">
        </label>
        <button type="submit" style="margin-top:6px;">Post Return</button>
    </form>

    <p class="center" style="margin-top:12px;">{{ $settings->footer ?? 'Thank you' }}</p>
</main>
@endsection
