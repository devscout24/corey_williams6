@extends('layouts.app')

@section('title', 'Transfer Ins')
@section('page-title', 'Transfer / Transfer Ins')

@push('styles')
<style>
    .transfer-toolbar {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 20px;
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
    }
    .transfer-table td {
        padding: 14px 16px;
        font-size: 13.5px;
        color: var(--gray-800);
        border-bottom: 1px solid var(--gray-100);
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
    [data-theme='dark'] .search-input-sm {
        background-color: var(--gray-50);
        border-color: var(--gray-200);
        color: var(--gray-900);
    }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">

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
                    <th style="width: 40px;"><input type="checkbox" id="selectAll"></th>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Parent Transfer</th>
                    <th>From Location</th>
                    <th>To Location</th>
                    <th>Notes</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transfers as $transfer)
                <tr>
                    <td><input type="checkbox" class="transfer-checkbox" data-id="{{ $transfer->id }}"></td>
                    <td class="fw-bold">TRN-IN {{ $transfer->id }}</td>
                    <td>{{ \Carbon\Carbon::parse($transfer->created_at)->format('m/d/Y @ h:i a') }}</td>
                    <td>TRN-OUT {{ $transfer->parent_transfer_id }}</td>
                    <td>{{ $transfer->from_location_name }}</td>
                    <td>{{ $transfer->to_location_name }}</td>
                    <td>{{ $transfer->notes ?? '-' }}</td>
                    <td><span class="badge badge-success">{{ ucfirst($transfer->status) }}</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">No transfer ins found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $transfers->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<form id="bulkDeleteForm" action="{{ route('transfers.bulk-delete') }}" method="POST" style="display:none">
    @csrf
    @method('DELETE')
    <input type="hidden" name="ids" id="bulkDeleteIds">
</form>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.transfer-checkbox');
        const bulkDeleteBtn = document.querySelector('.btn-bulk-delete');

        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        checkboxes.forEach(cb => {
            cb.addEventListener('change', function () {
                if (!this.checked) {
                    selectAll.checked = false;
                } else {
                    const allChecked = Array.from(checkboxes).every(c => c.checked);
                    selectAll.checked = allChecked;
                }
            });
        });

        bulkDeleteBtn.addEventListener('click', function () {
            const selected = Array.from(checkboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.dataset.id);

            if (selected.length === 0) {
                alert('Please select at least one transfer.');
                return;
            }

            if (!confirm('Delete ' + selected.length + ' selected transfer(s)?')) {
                return;
            }

            document.getElementById('bulkDeleteIds').value = JSON.stringify(selected);
            document.getElementById('bulkDeleteForm').submit();
        });
    });
</script>
@endpush
@endsection
