<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #333; margin: 20px; }
        h2 { margin: 0 0 4px; font-size: 16px; }
        .subtitle { color: #666; font-size: 11px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f5f5f5; padding: 5px 6px; border: 1px solid #ddd; font-size: 7px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; white-space: nowrap; }
        td { padding: 4px 6px; border: 1px solid #ddd; white-space: nowrap; }
        tr:nth-child(even) td { background: #fafafa; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h2>{{ $title }}</h2>
    <div class="subtitle">Range: {{ $startDate }} to {{ $endDate }}</div>

    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
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
                    <td>{{ number_format(max(0, ($item->effective_replenish_level ?? 0) - ($item->quantity ?? 0)), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" style="text-align:center;color:#999;padding:20px;">
                        No items found matching the criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>