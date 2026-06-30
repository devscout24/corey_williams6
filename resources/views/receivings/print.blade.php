<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print: {{ $receiving->internal_code ?? 'RCV-' . str_pad($receiving->receiving_id, 8, '0', STR_PAD_LEFT) }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0 0 5px 0;
            font-size: 24px;
        }
        .header p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        .info-grid {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .info-block {
            flex: 1;
        }
        .info-block strong {
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 5px;
        }
        .info-block p {
            margin: 0;
            font-size: 14px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
            font-size: 14px;
        }
        th {
            background: #f9f9f9;
            font-weight: bold;
            color: #333;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .totals-table {
            width: 300px;
            margin-left: auto;
        }
        .totals-table td {
            padding: 8px 0;
            border: none;
        }
        .totals-table .amount {
            text-align: right;
            font-weight: bold;
        }
        .grand-total td {
            border-top: 2px solid #333;
            font-size: 18px;
            padding-top: 12px;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body onload="window.print();">

    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print();" style="padding: 8px 16px; background: #007bff; color: #fff; border: none; cursor: pointer; border-radius: 4px;">Print Now</button>
        <button onclick="window.close();" style="padding: 8px 16px; background: #ccc; color: #333; border: none; cursor: pointer; border-radius: 4px; margin-left: 10px;">Close</button>
    </div>

    <div class="header">
        <h1>
            @if($receiving->is_po) Purchase Order @else Receiving @endif
            #{{ $receiving->internal_code ?? 'RCV-' . str_pad($receiving->receiving_id, 8, '0', STR_PAD_LEFT) }}
        </h1>
        <p>Created on {{ \Carbon\Carbon::parse($receiving->receiving_time)->format('F d, Y h:i A') }}</p>
    </div>

    <div class="info-grid">
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
        <div class="info-block text-end">
            <strong>Status</strong>
            <p>{{ $receiving->suspended ? 'Closed / Suspended' : 'Open' }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                @if($receiving->is_po)
                <th class="text-center">Qty on Hand</th>
                <th class="text-center">Qty Ordered</th>
                @else
                <th class="text-center">Qty Purchased</th>
                <th class="text-center">Qty Received</th>
                <th class="text-end">Cost Price</th>
                <th class="text-end">Tax</th>
                <th class="text-end">Total</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($receiving->items as $item)
            <tr>
                <td>
                    {{ $item->displayName() }}
                    @if($item->item_kit_id && ! $item->item_id) <small style="background:#e0e7ff;color:#3730a3;padding:1px 6px;border-radius:4px;font-size:11px;margin-left:4px;">Kit</small> @endif
                </td>
                @if($receiving->is_po)
                <td class="text-center">{{ $qtyOnHand[$item->item_id] ?? $qtyOnHand['kit_' . $item->item_kit_id] ?? 0 }}</td>
                <td class="text-center">{{ (float) $item->quantity_purchased }}</td>
                @else
                <td class="text-center">{{ (float) $item->quantity_purchased }}</td>
                <td class="text-center">{{ (float) $item->quantity_received }}</td>
                <td class="text-end">${{ number_format($item->item_cost_price, 2) }}</td>
                <td class="text-end">{{ $item->item?->taxClass?->name ?? ($item->kit?->taxClass?->name ?? '—') }}</td>
                <td class="text-end">${{ number_format($item->total, 2) }}</td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>

    @unless($receiving->is_po)
    <table class="totals-table">
        <tr>
            <td>Subtotal</td>
            <td class="amount">${{ number_format($receiving->subtotal, 2) }}</td>
        </tr>
        @if((float) $receiving->vat > 0)
        <tr>
            <td style="color:#666;">VAT</td>
            <td class="amount" style="color:#666;">${{ number_format($receiving->vat, 2) }}</td>
        </tr>
        @endif
        <tr class="grand-total">
            <td>Grand Total</td>
            <td class="amount">${{ number_format($receiving->total, 2) }}</td>
        </tr>
    </table>
    @endunless
    
    @if($receiving->comment)
    <div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 15px;">
        <strong style="font-size: 12px; color: #666; text-transform: uppercase;">Notes / Comments</strong>
        <p style="margin: 5px 0 0; font-size: 14px;">{{ $receiving->comment }}</p>
    </div>
    @endif

    <div style="margin-top: 40px; text-align: center;">
        @php
            $generator = new Picqer\Barcode\BarcodeGeneratorSVG();
            $barcodeData = $receiving->internal_code ?? $receiving->receiving_id;
        @endphp
        <div style="display: inline-block; padding: 10px; background: white;">
            {!! $generator->getBarcode($barcodeData, $generator::TYPE_CODE_128, 2, 60, 'black') !!}
        </div>
        <p style="margin: 5px 0 0; font-size: 14px; letter-spacing: 2px;">{{ $barcodeData }}</p>
    </div>

</body>
</html>
