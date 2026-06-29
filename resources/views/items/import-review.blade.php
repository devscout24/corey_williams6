@extends('layouts.app')

@section('title', 'Import Review')
@section('page-title', 'Inventory / Items / Import Review')

@push('styles')
<style>
  .review-card {
    background: #fff;
    border-radius: var(--radius-lg);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow-xs);
    padding: 24px 30px;
  }
  .review-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
  }
  .review-header h5 {
    font-size: 16px;
    font-weight: 600;
    color: #475569;
    margin: 0;
  }
  .review-header .badge-count {
    background: #f1f5f9;
    color: #475569;
    font-size: 13px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
    border: 1px solid var(--gray-200);
  }
  .review-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
  }
  .review-table thead th {
    background: var(--gray-50);
    border-bottom: 2px solid var(--gray-200);
    padding: 10px 14px;
    font-size: 12.5px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    white-space: nowrap;
  }
  .review-table tbody td {
    padding: 12px 14px;
    font-size: 13.5px;
    color: #334155;
    border-bottom: 1px solid var(--gray-100);
    vertical-align: middle;
  }
  .review-table tbody tr:hover {
    background: #f8fafc;
  }
  .price-old {
    color: #94a3b8;
    text-decoration: line-through;
    font-size: 12.5px;
  }
  .price-new {
    color: #059669;
    font-weight: 600;
  }
  .price-same {
    color: #94a3b8;
    font-size: 12.5px;
  }
  .arrow-icon {
    color: #94a3b8;
    margin: 0 6px;
    font-size: 12px;
  }
  .btn-action-row {
    display: flex;
    gap: 6px;
  }
  .btn-accept {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
    border-radius: 6px;
    padding: 5px 14px;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s;
  }
  .btn-accept:hover {
    background: #bbf7d0;
  }
  .btn-skip {
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid var(--gray-200);
    border-radius: 6px;
    padding: 5px 14px;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s;
  }
  .btn-skip:hover {
    background: #e2e8f0;
  }
  .toolbar-actions {
    display: flex;
    gap: 10px;
    align-items: center;
  }
  .btn-accept-all {
    background: #059669;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 8px 20px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s;
  }
  .btn-accept-all:hover {
    background: #047857;
  }
  .btn-skip-all {
    background: #fff;
    color: #64748b;
    border: 1px solid var(--gray-200);
    border-radius: 6px;
    padding: 8px 20px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s;
  }
  .btn-skip-all:hover {
    background: var(--gray-50);
  }
  .empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
  }
  .empty-state i {
    font-size: 48px;
    margin-bottom: 16px;
    display: block;
  }
  .item-id-badge {
    background: #eff6ff;
    color: #3b82f6;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 4px;
    margin-left: 6px;
  }
  .price-cell {
    min-width: 200px;
  }
  .price-existing {
    font-size: 11.5px;
    color: #94a3b8;
    margin-bottom: 4px;
  }
  .price-input {
    width: 100%;
    max-width: 120px;
    padding: 5px 8px;
    border: 1px solid var(--gray-200);
    border-radius: 5px;
    font-size: 13px;
    color: #334155;
    background: #fff;
    transition: border-color 0.15s;
  }
  .price-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59,130,246,0.15);
  }
  .price-changed {
    border-color: #f59e0b;
    background: #fffbeb;
  }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="page-content-inner">
        <div class="review-card">
            <div class="review-header">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <h5><i class="bi bi-arrow-left-right" style="color: #8b5cf6; margin-right: 6px;"></i> Import Review</h5>
                    <span class="badge-count">{{ $totalPending }} pending</span>
                    @if($created > 0)
                        <span style="background: #dcfce7; color: #166534; font-size: 13px; font-weight: 600; padding: 4px 12px; border-radius: 20px; border: 1px solid #bbf7d0;">{{ $created }} new items created</span>
                    @endif
                </div>
                <div class="toolbar-actions">
                    @if($totalPending > 0)
                        <form method="post" action="{{ route('items.import.accept-all') }}" style="display:inline;">
                            @csrf
                            <input type="hidden" name="batch" value="{{ $batch }}">
                            <button type="submit" class="btn-accept-all" onclick="return confirm('Accept all {{ $totalPending }} pending items?')">
                                <i class="bi bi-check-all" style="margin-right: 4px;"></i> Accept All
                            </button>
                        </form>
                        <form method="post" action="{{ route('items.import.skip') }}" style="display:inline;" id="skipAllForm">
                            @csrf
                            <input type="hidden" name="batch" value="{{ $batch }}">
                            @foreach($items as $item)
                                <input type="hidden" name="queue_ids[]" value="{{ $item->id }}">
                            @endforeach
                            <button type="submit" class="btn-skip-all" onclick="return confirm('Skip all {{ $totalPending }} pending items?')">
                                <i class="bi bi-skip-forward" style="margin-right: 4px;"></i> Skip All
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('items.index') }}" style="font-size: 13.5px; font-weight: 600; color: #64748b; text-decoration: none; padding: 8px 16px; border: 1px solid var(--gray-200); border-radius: 6px;">
                        <i class="bi bi-arrow-left" style="margin-right: 4px;"></i> Back to Items
                    </a>
                </div>
            </div>

            @if(session('status'))
                <div class="alert alert-success" style="border-radius: 6px; font-size: 13.5px; margin-bottom: 16px;">{{ session('status') }}</div>
            @endif

            @if($totalPending === 0)
                <div class="empty-state">
                    <i class="bi bi-check-circle"></i>
                    <p style="font-size: 15px; font-weight: 600; color: #475569; margin-bottom: 4px;">All done!</p>
                    <p style="font-size: 13px;">No pending items to review. <a href="{{ route('items.import') }}" style="color: #3b82f6; text-decoration: none; font-weight: 600;">Import another file</a></p>
                </div>
            @else
                <div style="overflow-x: auto;">
                    <table class="review-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="selectAll" class="custom-checkbox" onclick="toggleSelectAll(this)">
                                </th>
                                <th>Name</th>
                                <th>Item #</th>
                                <th>Product ID</th>
                                <th>Cost Price</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th style="width: 160px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                                <tr id="row-{{ $item->id }}">
                                    <td>
                                        <input type="checkbox" name="selected_ids[]" value="{{ $item->id }}" class="custom-checkbox row-checkbox">
                                    </td>
                                    <td>
                                        <strong>{{ $item->name }}</strong>
                                        @if($item->item_id)
                                            <span class="item-id-badge">ID: {{ $item->item_id }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->item_number ?: '—' }}</td>
                                    <td>{{ $item->product_id ?: '—' }}</td>
                                    <td class="price-cell">
                                        <div class="price-existing">Current: {{ number_format($item->existing_cost_price, 2) }}</div>
                                        <input type="number" step="0.01" min="0"
                                            name="cost_price_{{ $item->id }}"
                                            value="{{ number_format($item->incoming_cost_price, 2, '.', '') }}"
                                            class="price-input"
                                            data-existing="{{ $item->existing_cost_price }}"
                                            onchange="markChanged(this)">
                                    </td>
                                    <td class="price-cell">
                                        <div class="price-existing">Current: {{ number_format($item->existing_unit_price, 2) }}</div>
                                        <input type="number" step="0.01" min="0"
                                            name="unit_price_{{ $item->id }}"
                                            value="{{ number_format($item->incoming_unit_price, 2, '.', '') }}"
                                            class="price-input"
                                            data-existing="{{ $item->existing_unit_price }}"
                                            onchange="markChanged(this)">
                                    </td>
                                    <td class="price-cell">
                                        <div class="price-existing">Current: {{ number_format($item->existing_quantity, 0) }}</div>
                                        <input type="number" step="1" min="0"
                                            name="quantity_{{ $item->id }}"
                                            value="{{ number_format($item->incoming_quantity, 0, '.', '') }}"
                                            class="price-input"
                                            data-existing="{{ $item->existing_quantity }}"
                                            onchange="markChanged(this)">
                                    </td>
                                    <td>
                                        <div class="btn-action-row">
                                            <button type="button" class="btn-accept" onclick="submitSingle({{ $item->id }}, 'accept')">
                                                <i class="bi bi-check-lg"></i> Accept
                                            </button>
                                            <button type="button" class="btn-skip" onclick="submitSingle({{ $item->id }}, 'skip')">
                                                <i class="bi bi-x-lg"></i> Skip
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 16px; display: flex; align-items: center; gap: 10px;">
                    <button type="button" class="btn-accept" onclick="submitBulk('accept')" id="bulkAcceptBtn" disabled>
                        <i class="bi bi-check2-square" style="margin-right: 4px;"></i> Accept Selected
                    </button>
                    <button type="button" class="btn-skip" onclick="submitBulk('skip')" id="bulkSkipBtn" disabled>
                        <i class="bi bi-x-square" style="margin-right: 4px;"></i> Skip Selected
                    </button>
                    <span id="selectedCount" style="font-size: 12.5px; color: #94a3b8;"></span>
                </div>
            @endif
        </div>
    </div>
</div>

<form id="bulkForm" method="post" style="display:none;">
    @csrf
    <input type="hidden" name="batch" value="{{ $batch }}">
    <div id="bulkInputs"></div>
</form>

<script>
function toggleSelectAll(el) {
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = el.checked);
    updateBulkButtons();
}

document.querySelectorAll('.row-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBulkButtons);
});

function updateBulkButtons() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    const count = checked.length;
    document.getElementById('bulkAcceptBtn').disabled = count === 0;
    document.getElementById('bulkSkipBtn').disabled = count === 0;
    document.getElementById('selectedCount').textContent = count > 0 ? count + ' selected' : '';
}

function markChanged(input) {
    const existing = parseFloat(input.dataset.existing);
    const current = parseFloat(input.value);
    if (Math.abs(current - existing) > 0.001) {
        input.classList.add('price-changed');
    } else {
        input.classList.remove('price-changed');
    }
}

function submitSingle(id, action) {
    const form = document.getElementById('bulkForm');
    form.action = action === 'accept'
        ? '{{ route("items.import.accept") }}'
        : '{{ route("items.import.skip") }}';

    const container = document.getElementById('bulkInputs');
    container.innerHTML = '';

    const row = document.getElementById('row-' + id);

    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'queue_ids[]';
    idInput.value = id;
    container.appendChild(idInput);

    ['cost_price', 'unit_price', 'quantity'].forEach(field => {
        const inp = row.querySelector('input[name="' + field + '_' + id + '"]');
        if (inp) {
            const h = document.createElement('input');
            h.type = 'hidden';
            h.name = field + '_' + id;
            h.value = inp.value;
            container.appendChild(h);
        }
    });

    form.submit();
}

function submitBulk(action) {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    if (checked.length === 0) return;

    const form = document.getElementById('bulkForm');
    form.action = action === 'accept'
        ? '{{ route("items.import.accept") }}'
        : '{{ route("items.import.skip") }}';

    const container = document.getElementById('bulkInputs');
    container.innerHTML = '';

    checked.forEach(cb => {
        const id = cb.value;
        const row = cb.closest('tr');

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'queue_ids[]';
        idInput.value = id;
        container.appendChild(idInput);

        const costInput = row.querySelector('input[name="cost_price_' + id + '"]');
        if (costInput) {
            const h = document.createElement('input');
            h.type = 'hidden';
            h.name = 'cost_price_' + id;
            h.value = costInput.value;
            container.appendChild(h);
        }

        const unitInput = row.querySelector('input[name="unit_price_' + id + '"]');
        if (unitInput) {
            const h = document.createElement('input');
            h.type = 'hidden';
            h.name = 'unit_price_' + id;
            h.value = unitInput.value;
            container.appendChild(h);
        }

        const qtyInput = row.querySelector('input[name="quantity_' + id + '"]');
        if (qtyInput) {
            const h = document.createElement('input');
            h.type = 'hidden';
            h.name = 'quantity_' + id;
            h.value = qtyInput.value;
            container.appendChild(h);
        }
    });

    form.submit();
}
</script>
@endsection
