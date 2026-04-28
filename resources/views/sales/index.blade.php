@extends('layouts.app')

@section('title', 'Sales Register')
@section('page-title', 'Sales Register')

@push('styles')
<style>
    .wrap { max-width: 1200px; margin: 18px auto; padding: 0 16px 24px; }
    .top { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
    .top a { background: #0e7490; color: #fff; text-decoration: none; padding: 8px 12px; border-radius: 8px; }
    .grid { display: grid; grid-template-columns: 1.2fr .8fr; gap: 14px; margin-top: 14px; }
    .card { background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(15,23,42,.08); padding: 14px; }
    label { display: block; margin-bottom: 8px; }
    input, select, textarea { width: 100%; box-sizing: border-box; border: 1px solid #d3deea; border-radius: 8px; padding: 8px; }
    .line { display: grid; grid-template-columns: 1fr 120px; gap: 8px; margin-bottom: 8px; }
    button { border: 0; background: #0f766e; color: #fff; border-radius: 8px; padding: 9px 12px; cursor: pointer; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border-bottom: 1px solid #e2e8f0; padding: 7px; text-align: left; font-size: .92rem; }
    .status { background: #dcfce7; border: 1px solid #86efac; color: #166534; border-radius: 8px; padding: 8px; margin-bottom: 10px; }
    .error { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 8px; padding: 8px; margin-bottom: 10px; }
    @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="wrap">
    <div class="top">
        <h1>Sales Register</h1>
        <a href="{{ route('modules.index') }}">Back to Modules</a>
    </div>

    @if(session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="error">
            @foreach($errors->all() as $message)
                <div>{{ $message }}</div>
            @endforeach
        </div>
    @endif

    <div class="grid">
        <section class="card">
            <h2>Create Sale</h2>
            <form method="post" action="{{ route('sales.store') }}">
                @csrf
                <label>Location
                    <select name="location_id" required>
                        @foreach($locations as $location)
                            <option value="{{ $location->location_id }}">{{ $location->name }} ({{ $location->location_id }})</option>
                        @endforeach
                    </select>
                </label>
                <label>Customer Name (optional)
                    <input type="text" name="customer_name" maxlength="255" value="{{ old('customer_name') }}">
                </label>

                @for($i = 0; $i < 4; $i++)
                    <div class="line">
                        <label>Item {{ $i + 1 }}
                            <select name="lines[{{ $i }}][item_id]">
                                @foreach($items as $item)
                                    <option value="{{ $item->item_id }}">{{ $item->name }} ({{ $item->item_number ?: 'ITEM-'.$item->item_id }})</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Qty
                            <input type="number" name="lines[{{ $i }}][quantity]" min="0" step="0.001" value="{{ $i === 0 ? '1' : '0' }}">
                        </label>
                    </div>
                @endfor

                <label>Comment
                    <textarea name="comment" maxlength="1000">{{ old('comment') }}</textarea>
                </label>
                <button type="submit">Complete Sale</button>
            </form>
        </section>

        <section class="card">
            <h2>Recent Sales</h2>
            <table>
                <thead>
                <tr>
                    <th>Sale #</th>
                    <th>Location</th>
                    <th>Total</th>
                    <th>Receipt</th>
                </tr>
                </thead>
                <tbody>
                @forelse($recentSales as $sale)
                    <tr>
                        <td>{{ $sale->sale_id }}</td>
                        <td>{{ $sale->location_id }}</td>
                        <td>${{ number_format((float) $sale->total, 2) }}</td>
                        <td><a href="{{ route('sales.receipt', ['sale' => $sale->sale_id]) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4">No sales yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
    </div>
</div>
@endsection
