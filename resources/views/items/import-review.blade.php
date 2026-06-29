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
  .cell-edit {
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .cell-edit input[type="number"] {
    width: 90px;
    border: 1px solid var(--gray-200);
    border-radius: 4px;
    padding: 5px 8px;
    font-size: 13px;
    color: #1e293b;
    outline: none;
    background: #fff;
    transition: border-color 0.15s;
  }
  .cell-edit input[type="number"]:focus {
    border-color: var(--primary);
  }
  .cell-edit input[type="number"].using-existing {
    background: #fffbeb;
    border-color: #fbbf24;
  }
  .btn-keep-existing {
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s;
  }
  .btn-keep-existing:hover {
    background: #e2e8f0;
  }
  .btn-keep-existing.active {
    background: #fef3c7;
    border-color: #f59e0b;
    color: #92400e;
  }
  .existing-hint {
    font-size: 11px;
    color: #94a3b8;
    white-space: nowrap;
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
                        <button type="button" class="btn-accept-all" onclick="submitBulkAcceptAll()" onclick="return confirm('Accept all {{ $totalPending }} pending items?')">
                            <i class="bi bi-check-all" style="margin-right: 4px;"></i> Accept All
                        </button>
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
                                <th style="width: 140px;">Actions</th>
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
                                    <td>
                                        <div class="cell-edit">
                                            <input type="number" step="0.01" min="0"
                                                class="cost-input"
                                                data-existing="{{ $item->existing_cost_price }}"
                                                data-incoming="{{ $item->incoming_cost_price }}"
                                                value="{{ $item->incoming_cost_price }}">
                                            <button type="button" class="btn-keep-existing"
                                                onclick="toggleExisting(this, 'cost')"
                                                title="Toggle existing value">Keep</button>
                                        </div>
                                        <div class="existing-hint">was: {{ number_format($item->existing_cost_price, 2) }}</div>
                                    </td>
                                    <td>
                                        <div class="cell-edit">
                                            <input type="number" step="0.01" min="0"
                                                class="unit-input"
                                                data-existing="{{ $item->existing_unit_price }}"
                                                data-incoming="{{ $item->incoming_unit_price }}"
                                                value="{{ $item->incoming_unit_price }}">
                                            <button type="button" class="btn-keep-existing"
                                                onclick="toggleExisting(this, 'unit')"
                                                title="Toggle existing value">Keep</button>
                                        </div>
                                        <div class="existing-hint">was: {{ number_format($item->existing_unit_price, 2) }}</div>
                                    </td>
                                    <td>
                                        <div class="cell-edit">
                                            <input type="number" step="1" min="0"
                                                class="qty-input"
                                                data-existing="{{ $item->existing_quantity }}"
                                                data-incoming="{{ $item->incoming_quantity }}"
                                                value="{{ $item->incoming_quantity }}">
                                            <button type="button" class="btn-keep-existing"
                                                onclick="toggleExisting(this, 'qty')"
                                                title="Toggle existing value">Keep</button>
                                        </div>
                                        <div class="existing-hint">was: {{ number_format($item->existing_quantity, 0) }}</div>
                                    </td>
                                    <td>
                                        <div class="btn-action-row">
                                            <form method="post" action="{{ route('items.import.accept') }}" style="display:inline;" class="accept-form">
                                                @csrf
                                                <input type="hidden" name="batch" value="{{ $batch }}">
                                                <input type="hidden" name="queue_ids[]" value="{{ $item->id }}">
                                                <input type="hidden" name="cost_price_{{ $item->id }}" class="cost-hidden">
                                                <input type="hidden" name="unit_price_{{ $item->id }}" class="unit-hidden">
                                                <input type="hidden" name="quantity_{{ $item->id }}" class="qty-hidden">
                                                <button type="submit" class="btn-accept" onclick="syncRowValues(this)">
                                                    <i class="bi bi-check-lg"></i> Accept
                                                </button>
                                            </form>
                                            <form method="post" action="{{ route('items.import.skip') }}" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="batch" value="{{ $batch }}">
                                                <input type="hidden" name="queue_ids[]" value="{{ $item->id }}">
                                                <button type="submit" class="btn-skip">
                                                    <i class="bi bi-x-lg"></i> Skip
                                                </button>
                                            </form>
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

function toggleExisting(btn, type) {
    const row = btn.closest('tr');
    let input;
    if (type === 'cost') input = row.querySelector('.cost-input');
    else if (type === 'unit') input = row.querySelector('.unit-input');
    else input = row.querySelector('.qty-input');

    const existing = parseFloat(input.dataset.existing);
    const incoming = parseFloat(input.dataset.incoming);
    const usingExisting = btn.classList.toggle('active');

    if (usingExisting) {
        input.value = existing;
        input.classList.add('using-existing');
    } else {
        input.value = incoming;
        input.classList.remove('using-existing');
    }
}

function syncRowValues(btn) {
    const form = btn.closest('form');
    const row = btn.closest('tr');
    form.querySelector('.cost-hidden').value = row.querySelector('.cost-input').value;
    form.querySelector('.unit-hidden').value = row.querySelector('.unit-input').value;
    form.querySelector('.qty-hidden').value = row.querySelector('.qty-input').value;
}

function submitBulk(action) {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    const ids = Array.from(checked).map(cb => cb.value);
    if (ids.length === 0) return;

    if (action === 'skip') {
        const form = document.getElementById('bulkForm');
        form.action = '{{ route("items.import.skip") }}';
        const container = document.getElementById('bulkInputs');
        container.innerHTML = '';
        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'queue_ids[]';
            input.value = id;
            container.appendChild(input);
        });
        form.submit();
        return;
    }

    const form = document.getElementById('bulkForm');
    form.action = '{{ route("items.import.accept") }}';
    const container = document.getElementById('bulkInputs');
    container.innerHTML = '';

    ids.forEach(id => {
        const row = document.getElementById('row-' + id);
        const queueInput = document.createElement('input');
        queueInput.type = 'hidden';
        queueInput.name = 'queue_ids[]';
        queueInput.value = id;
        container.appendChild(queueInput);

        const costInput = document.createElement('input');
        costInput.type = 'hidden';
        costInput.name = 'cost_price_' + id;
        costInput.value = row.querySelector('.cost-input').value;
        container.appendChild(costInput);

        const unitInput = document.createElement('input');
        unitInput.type = 'hidden';
        unitInput.name = 'unit_price_' + id;
        unitInput.value = row.querySelector('.unit-input').value;
        container.appendChild(unitInput);

        const qtyInput = document.createElement('input');
        qtyInput.type = 'hidden';
        qtyInput.name = 'quantity_' + id;
        qtyInput.value = row.querySelector('.qty-input').value;
        container.appendChild(qtyInput);
    });

    form.submit();
}

function submitBulkAcceptAll() {
    if (!confirm('Accept all {{ $totalPending }} pending items with their current values?')) return;

    const form = document.getElementById('bulkForm');
    form.action = '{{ route("items.import.accept-all") }}';
    document.getElementById('bulkInputs').innerHTML = '';
    form.submit();
}
</script>
@endsection
