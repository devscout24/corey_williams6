@extends('layouts.app')

@section('title', 'Purchases')
@section('page-title', 'Purchases')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/purchases-index.css') }}">
@endpush

@section('content')
    <div class="container-fluid pidx-page">
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="recv-card mb-3">
            <div class="history-header">
                <div class="history-title">
                    <i class="bi bi-truck"></i>
                    <span>Purchases &amp; returns</span>
                </div>
                <a href="{{ route('purchases.create') }}" class="btn-add-Purchases text-decoration-none">
                    <i class="bi bi-plus-lg"></i> Add purchases
                </a>
            </div>
            <p class="pidx-list-meta px-1 mb-2">Receiving (stock in), transfers, and supplier returns (stock out) share one register — history is split below like the POS HTML Dashboard.</p>

            <form method="get" action="{{ route('purchases.index') }}" class="history-search-bar">
                <select class="history-criteria-select" name="criteria" aria-label="Search field">
                    <option value="id" @selected($criteria === 'id')>ID</option>
                    <option value="supplier" @selected($criteria === 'supplier')>Supplier</option>
                    <option value="date" @selected($criteria === 'date')>Date</option>
                    <option value="status" @selected($criteria === 'status')>Status</option>
                </select>
                <input type="text" name="q" class="history-search-input" value="{{ $q }}"
                    placeholder="Search both lists…" />
                <button type="submit" class="btn-history-search">
                    <i class="bi bi-search"></i> Search
                </button>
                <a href="{{ route('purchases.index') }}" class="btn-recv-clear text-decoration-none">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
            </form>
        </div>

        <div class="recv-card mb-3" id="purchases-list">
            <div class="history-header">
                <div class="history-title">
                    <i class="bi bi-cart"></i>
                    <span>Purchases / receiving</span>
                </div>
                <span class="pidx-list-meta">{{ $purchases->count() }} record(s)</span>
            </div>
            <div class="table-responsive">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th class="text-center">Items</th>
                            <th>Total</th>
                            <th>Mode</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchases as $receiving)
                            <tr>
                                <td><span class="history-id">#{{ $receiving->receiving_id }}</span></td>
                                <td>{{ \Carbon\Carbon::parse($receiving->receiving_time)->format('M d, Y h:i A') }}</td>
                                <td>{{ $receiving->supplier->company_name ?? '—' }}</td>
                                <td class="text-center">{{ $receiving->items->count() }}</td>
                                <td>${{ number_format((float) $receiving->total, 2) }}</td>
                                <td>
                                    @if ($receiving->mode === 'transfer')
                                        <span class="badge-pidx-transfer">Transfer</span>
                                    @else
                                        <span class="badge-pidx-closed">Receive</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($receiving->is_po)
                                        <span class="badge-pidx-open">PO</span>
                                    @elseif ($receiving->suspended)
                                        <span class="badge-pidx-open">Suspended</span>
                                    @else
                                        <span class="badge-pidx-closed">Completed</span>
                                    @endif
                                </td>
                                <td class="text-end text-muted small">—</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No purchases yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="recv-card" id="returns-list">
            <div class="history-header">
                <div class="history-title">
                    <i class="bi bi-arrow-return-left"></i>
                    <span>Returns</span>
                </div>
                <span class="pidx-list-meta">{{ $returns->count() }} record(s)</span>
            </div>
            <div class="table-responsive">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th class="text-center">Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($returns as $receiving)
                            <tr>
                                <td><span class="history-id">#{{ $receiving->receiving_id }}</span></td>
                                <td>{{ \Carbon\Carbon::parse($receiving->receiving_time)->format('M d, Y h:i A') }}</td>
                                <td>{{ $receiving->supplier->company_name ?? '—' }}</td>
                                <td class="text-center">{{ $receiving->items->count() }}</td>
                                <td>${{ number_format((float) $receiving->total, 2) }}</td>
                                <td>
                                    @if ($receiving->suspended)
                                        <span class="badge-pidx-open">Suspended</span>
                                    @else
                                        <span class="badge-pidx-return">Returned</span>
                                    @endif
                                </td>
                                <td class="text-end text-muted small">—</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No returns yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
