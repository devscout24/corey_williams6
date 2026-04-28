@extends('layouts.app')

@section('title', 'Receiving / Return / Transfer')
@section('page-title', 'Receiving / Return / Transfer')

@push('styles')
<style>
    .wrap { max-width: 1240px; margin: 18px auto; padding: 0 16px 20px; }
    .top { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; }
    .top a { background: #0e7490; color: #fff; text-decoration: none; padding: 8px 12px; border-radius: 8px; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(310px, 1fr)); gap: 14px; }
    .card { background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(15, 23, 42, .08); padding: 14px; }
    h2 { margin: 0 0 10px; font-size: 1.04rem; }
    label { display: block; margin: 8px 0; }
    input, select, textarea { width: 100%; box-sizing: border-box; padding: 8px; border: 1px solid #d2dce8; border-radius: 8px; }
    textarea { min-height: 64px; }
    button { border: 0; background: #0f766e; color: #fff; border-radius: 8px; padding: 9px 12px; cursor: pointer; }
    .status { background: #dcfce7; border: 1px solid #86efac; color: #166534; border-radius: 8px; padding: 10px; margin-bottom: 10px; }
    .error { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 8px; padding: 10px; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border-bottom: 1px solid #e2e8f0; padding: 7px; text-align: left; font-size: .92rem; }
    .small { color: #475569; font-size: .9rem; }
</style>
@endpush

@section('content')
<div class="wrap">
    <div class="top">
        <h1>Receiving, Return, Transfer Out/In</h1>
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
            <h2>Receiving (adds inventory)</h2>
            <form method="post" action="{{ route('inventory.receiving.store') }}">
                @csrf
                <label>Location
                    <select name="location_id" required>
                        @foreach($locations as $location)
                            <option value="{{ $location->location_id }}">{{ $location->name }} ({{ $location->location_id }})</option>
                        @endforeach
                    </select>
                </label>
                <label>Item
                    <select name="item_id" required>
                        @foreach($items as $item)
                            <option value="{{ $item->item_id }}">{{ $item->name }} ({{ $item->item_number ?: 'ITEM-'.$item->item_id }})</option>
                        @endforeach
                    </select>
                </label>
                <label>Quantity
                    <input type="number" name="quantity" min="0.001" step="0.001" value="1" required>
                </label>
                <label>Notes
                    <textarea name="notes" placeholder="Optional"></textarea>
                </label>
                <button type="submit">Post Receiving</button>
            </form>
        </section>

        <section class="card">
            <h2>Return (takes from inventory)</h2>
            <form method="post" action="{{ route('inventory.return.store') }}">
                @csrf
                <label>Location
                    <select name="location_id" required>
                        @foreach($locations as $location)
                            <option value="{{ $location->location_id }}">{{ $location->name }} ({{ $location->location_id }})</option>
                        @endforeach
                    </select>
                </label>
                <label>Item
                    <select name="item_id" required>
                        @foreach($items as $item)
                            <option value="{{ $item->item_id }}">{{ $item->name }} ({{ $item->item_number ?: 'ITEM-'.$item->item_id }})</option>
                        @endforeach
                    </select>
                </label>
                <label>Quantity
                    <input type="number" name="quantity" min="0.001" step="0.001" value="1" required>
                </label>
                <label>Notes
                    <textarea name="notes" placeholder="Optional"></textarea>
                </label>
                <button type="submit">Post Return</button>
            </form>
        </section>

        <section class="card">
            <h2>Transfer Out (auto Transfer In)</h2>
            <p class="small">Closing transfer out automatically creates and closes transfer in. Source stock decreases, destination stock increases.</p>
            <form method="post" action="{{ route('inventory.transfer-out.store') }}">
                @csrf
                <label>From Location
                    <select name="from_location_id" required>
                        @foreach($locations as $location)
                            <option value="{{ $location->location_id }}">{{ $location->name }} ({{ $location->location_id }})</option>
                        @endforeach
                    </select>
                </label>
                <label>To Location
                    <select name="to_location_id" required>
                        @foreach($locations as $location)
                            <option value="{{ $location->location_id }}">{{ $location->name }} ({{ $location->location_id }})</option>
                        @endforeach
                    </select>
                </label>

                @for($i = 0; $i < 3; $i++)
                    <div style="display:grid; grid-template-columns: 1fr 120px; gap:8px; margin-top:8px;">
                        <label>Item Line {{ $i + 1 }}
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

                <label>Notes
                    <textarea name="notes" placeholder="Optional"></textarea>
                </label>
                <button type="submit">Close Transfer Out (Auto In)</button>
            </form>
        </section>
    </div>

    <div class="grid" style="margin-top:14px;">
        <section class="card">
            <h2>Recent Movements</h2>
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Item</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Qty</th>
                </tr>
                </thead>
                <tbody>
                @forelse($recentMovements as $movement)
                    <tr>
                        <td>{{ $movement->id }}</td>
                        <td>{{ $movement->movement_type }}</td>
                        <td>{{ $movement->item_name }}</td>
                        <td>{{ $movement->from_location_name ?: '-' }}</td>
                        <td>{{ $movement->to_location_name ?: '-' }}</td>
                        <td>{{ $movement->quantity }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No movements yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>

        <section class="card">
            <h2>Recent Transfers</h2>
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Status</th>
                    <th>Parent</th>
                </tr>
                </thead>
                <tbody>
                @forelse($recentTransfers as $transfer)
                    <tr>
                        <td>{{ $transfer->id }}</td>
                        <td>{{ $transfer->transfer_type }}</td>
                        <td>{{ $transfer->from_location_id }}</td>
                        <td>{{ $transfer->to_location_id }}</td>
                        <td>{{ $transfer->status }}</td>
                        <td>{{ $transfer->parent_transfer_id ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No transfers yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
    </div>
</div>
@endsection
