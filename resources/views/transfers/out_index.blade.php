@extends('layouts.app')

@section('title', 'Transfer Outs')
@section('page-title', 'Transfer / Transfer Outs')

@push('styles')
<style>
    .transfer-toolbar {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 20px;
    }
    .btn-add-transfer {
        background-color: var(--primary);
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: var(--radius-sm);
        font-weight: 600;
        font-size: 13.5px;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: var(--transition);
        text-decoration: none;
    }
    .btn-add-transfer:hover {
        background-color: var(--primary-dark);
        color: #fff;
    }
    .table-container-card {
        background: #fff;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-xs);
        border: 1px solid var(--gray-200);
        padding: 24px;
        overflow-x: auto;
    }
    .bulk-actions-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .bulk-btns {
        display: flex;
        gap: 10px;
    }
    .btn-bulk-delete, .btn-bulk-clear {
        background: var(--gray-50);
        border: 1px solid var(--gray-200);
        padding: 8px 16px;
        border-radius: var(--radius-sm);
        font-size: 13px;
        font-weight: 600;
        color: var(--gray-700);
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        transition: var(--transition);
    }
    .btn-bulk-delete:hover {
        background: #FEE2E2;
        color: var(--danger);
        border-color: #FCA5A5;
    }
    .btn-bulk-clear:hover {
        background: var(--gray-100);
    }
    .search-entries {
        font-size: 13.5px;
        color: var(--gray-600);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .entries-select {
        border: 1px solid var(--gray-200);
        border-radius: 4px;
        padding: 4px 8px;
        outline: none;
    }
    .search-box {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13.5px;
        color: var(--gray-600);
    }
    .search-input-sm {
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-sm);
        padding: 6px 12px;
        font-size: 13px;
        outline: none;
        width: 200px;
    }
    .search-input-sm:focus {
        border-color: var(--primary);
    }

    .transfer-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
    }
    .transfer-table th {
        background: var(--gray-50);
        padding: 12px 16px;
        font-size: 13px;
        font-weight: 600;
        color: var(--gray-500);
        border-bottom: 1.5px solid var(--gray-200);
        text-align: left;
        white-space: nowrap;
    }
    .transfer-table td {
        padding: 14px 16px;
        font-size: 13.5px;
        color: var(--gray-800);
        border-bottom: 1px solid var(--gray-100);
        vertical-align: middle;
    }
    .transfer-table tr:hover {
        background: var(--gray-50);
    }

    /* Dark Mode Overrides */
    [data-theme='dark'] .table-container-card {
        background: var(--gray-100) !important;
        border-color: var(--gray-200) !important;
    }
    [data-theme='dark'] .transfer-table th {
        background: var(--gray-200) !important;
        border-bottom-color: var(--gray-300) !important;
        color: var(--gray-800) !important;
    }
    [data-theme='dark'] .transfer-table td {
        border-bottom-color: var(--gray-200) !important;
        color: var(--gray-900) !important;
    }
    [data-theme='dark'] .transfer-table tr:hover {
        background: var(--gray-50) !important;
    }
    [data-theme='dark'] .search-input-sm, [data-theme='dark'] .entries-select {
        background-color: var(--gray-50);
        border-color: var(--gray-200);
        color: var(--gray-900);
    }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="transfer-toolbar">
        <a href="{{ route('transfers.create') }}" class="btn-add-transfer"><i class="bi bi-plus-lg"></i> Add Transfer</a>
    </div>

    <div class="table-container-card">
        <div class="bulk-actions-row">
            <div class="bulk-btns">
                <button class="btn-bulk-delete"><i class="bi bi-trash"></i> Delete</button>
            </div>
            <div class="d-flex gap-4">
                <div class="search-box">
                    <span>Search:</span>
                    <input type="text" class="search-input-sm" id="searchTransfers">
                </div>
            </div>
        </div>

        <table class="transfer-table">
            <thead>
                <tr>
                    <th style="width: 40px;"><input type="checkbox"></th>
                    <th>ID</th>
                    <th>Date</th>
                    <th>From Location</th>
                    <th>To Location</th>
                    <th>Notes</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transfers as $transfer)
                <tr>
                    <td><input type="checkbox"></td>
                    <td class="fw-bold">TRN-OUT {{ $transfer->id }}</td>
                    <td>{{ \Carbon\Carbon::parse($transfer->created_at)->format('m/d/Y @ h:i a') }}</td>
                    <td>{{ $transfer->from_location_name }}</td>
                    <td>{{ $transfer->to_location_name }}</td>
                    <td>{{ $transfer->notes ?? '-' }}</td>
                    <td><span class="badge {{ $transfer->status === 'open' ? 'bg-warning text-dark' : 'bg-success' }}">{{ ucfirst($transfer->status) }}</span></td>
                    <td>
                        @if($transfer->status === 'open')
                            <a href="{{ route('transfers.edit', $transfer->id) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No transfer outs found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $transfers->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
