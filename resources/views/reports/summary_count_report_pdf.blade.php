<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; margin: 20px; }
        h2 { margin: 0 0 4px; font-size: 16px; }
        .subtitle { color: #666; font-size: 11px; margin-bottom: 16px; }
        .summary-cards { margin-bottom: 16px; }
        .summary-cards table { width: 100%; border-collapse: collapse; }
        .summary-cards td { padding: 8px 12px; border: 1px solid #ddd; text-align: center; width: 50%; }
        .summary-cards .value { font-size: 14px; font-weight: bold; }
        .summary-cards .label { font-size: 8px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #f5f5f5; padding: 6px 8px; border: 1px solid #ddd; font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; white-space: nowrap; }
        table.data td { padding: 5px 8px; border: 1px solid #ddd; white-space: nowrap; }
        table.data tr:nth-child(even) td { background: #fafafa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h2>{{ $title }}</h2>
    <div class="subtitle">Range: {{ $startDate }} to {{ $endDate }}</div>

    @if(isset($overallSummary) && count($overallSummary) > 0)
    <div class="summary-cards">
        <table>
            <tr>
                @foreach($overallSummary as $name => $value)
                <td>
                    <div class="value">
                        @if(in_array($name, ['number_items_counted']))
                            {{ number_format($value) }}
                        @else
                            {{ number_format($value, 2) }}
                        @endif
                    </div>
                    <div class="label">{{ $summaryLabels[$name] ?? ucwords(str_replace('_', ' ', $name)) }}</div>
                </td>
                @endforeach
            </tr>
        </table>
    </div>
    @endif

    <table class="data">
        <thead>
            <tr>
                @foreach($headers as $label => $key)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                <tr>
                    @foreach($headers as $label => $key)
                        <td>
                            @php
                                $val = $row->$key ?? 0;
                            @endphp
                            @if(in_array($key, ['difference', 'cost_price_difference']) && is_numeric($val))
                                {{ $val > 0 ? '+' : '' }}{{ number_format((float)$val, 2) }}
                            @elseif(is_numeric($val) && str_contains((string)$val, '.'))
                                {{ number_format($val, 2) }}
                            @elseif(is_numeric($val))
                                {{ number_format($val) }}
                            @else
                                {{ $val }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" style="text-align:center;color:#999;padding:20px;">
                        No data found for the selected period.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
