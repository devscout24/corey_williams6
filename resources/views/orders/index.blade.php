@extends('layouts.app')

@section('title', 'Orders')
@section('page-title', 'Inventory / Orders')

@push('styles')
<style>
  .customers-toolbar {
    background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--gray-200);
    padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px; box-shadow: var(--shadow-xs);
  }
  .search-wrap { display: flex; gap: 12px; flex: 1; max-width: 640px; }
  .search-input {
    flex: 1; background: var(--gray-50); border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm); padding: 8px 16px; font-size: 13.5px;
    outline: none; transition: var(--transition);
  }
  .search-input:focus { border-color: var(--primary); background: #fff; }
  .btn-search {
    background: var(--primary); color: #fff; border: none; border-radius: var(--radius-sm);
    padding: 8px 20px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 8px;
    transition: var(--transition);
  }
  .btn-search:hover { background: var(--primary-dark); }
  .btn-new-order {
    background: var(--primary); color: #fff; border: none; border-radius: var(--radius-sm);
    padding: 8px 16px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 8px;
    transition: var(--transition);
  }
  .btn-new-order:hover { background: var(--primary-dark); }

  .order-tabs { display: flex; gap: 10px; margin-bottom: 16px; }
  .btn-tab {
    border: 1px solid var(--gray-200); background: #fff; padding: 6px 14px; border-radius: 999px;
    font-size: 13px; font-weight: 600; color: var(--gray-600); transition: var(--transition);
  }
  .btn-tab.active { background: var(--primary); color: #fff; border-color: var(--primary); }

  .table-card {
    background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--gray-200);
    box-shadow: var(--shadow-xs); overflow: hidden;
  }
  .custom-table { width: 100%; border-collapse: collapse; min-width: 900px; }
  .custom-table th {
    background: #F8F9FE; color: #475569; font-size: 13px; font-weight: 700;
    padding: 16px 20px; text-align: left; white-space: nowrap;
    border-bottom: 1px solid #E2E8F0;
  }
  .custom-table td {
    padding: 14px 20px; border-bottom: 1px solid var(--gray-100); font-size: 13.5px;
    color: var(--gray-700); vertical-align: middle; font-weight: 500;
  }
  .custom-table tr:last-child td { border-bottom: none; }
  .custom-table tr:hover { background: var(--gray-50); }
  .status-badge {
    padding: 6px 16px; border-radius: 999px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; min-width: 80px;
  }
  .status-open { background: #E6F9EE; color: #008A3D; }
  .status-closed { background: #F1F5F9; color: #475569; }

  .row-actions { display: flex; align-items: center; gap: 8px; justify-content: flex-end; }
  .btn-action-icon {
    width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
    border-radius: 8px; border: 1px solid var(--gray-200); background: #fff;
    color: var(--gray-500); transition: all 0.2s; cursor: pointer; text-decoration: none;
  }
  .btn-action-icon:hover { background: var(--gray-50); color: var(--primary); border-color: var(--primary-soft); }
  
  .dropdown-toggle-nocaret::after { display: none; }
  .action-dropdown-menu {
    border: none; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
    padding: 8px; min-width: 180px;
  }
  .action-dropdown-item {
    display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px;
    font-size: 13.5px; font-weight: 500; color: var(--gray-600); transition: all 0.2s;
    cursor: pointer; border: none; background: transparent; width: 100%; text-align: left;
  }
  .action-dropdown-item:hover { background: var(--gray-50); color: var(--primary); }
  .action-dropdown-item i { font-size: 16px; }
  .action-dropdown-item.text-danger:hover { background: #FEF2F2; color: #DC2626; }
  .dropdown-divider { margin: 8px 0; border-top: 1px solid var(--gray-100); }

  .modal-content-custom { border-radius: 16px; border: 1px solid var(--gray-200); }
  .modal-header-custom { display: flex; align-items: center; justify-content: space-between; padding: 18px 22px; border-bottom: 1px solid var(--gray-100); }
  .modal-title-custom { font-size: 16px; font-weight: 700; margin: 0; }
  .btn-close-custom { border: 0; background: transparent; font-size: 18px; color: var(--gray-500); }
  .supplier-search-input {
    width: 100%; border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 10px 12px;
    font-size: 13.5px; outline: none; background: var(--gray-50); transition: var(--transition);
  }
  .supplier-search-input:focus { border-color: var(--primary); background: #fff; }
  .supplier-list { margin-top: 14px; display: flex; flex-direction: column; gap: 10px; }
  .supplier-item {
    border: 1px solid var(--gray-200); border-radius: 12px; padding: 12px 14px; display: flex;
    align-items: center; justify-content: space-between; cursor: pointer; transition: var(--transition);
  }
  .supplier-item:hover { border-color: var(--primary); background: var(--primary-soft); }
  .supplier-name { font-weight: 700; font-size: 13.5px; color: var(--gray-800); }
  .supplier-email { font-size: 12px; color: var(--gray-500); }

  .start-mode-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
  .start-mode-card {
    border: 1px solid var(--gray-200); border-radius: 14px; padding: 16px; text-align: left; cursor: pointer;
    transition: var(--transition); background: #fff;
  }
  .start-mode-card:hover { border-color: var(--primary); box-shadow: var(--shadow-xs); }
  .start-mode-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; }
  .icon-blue { background: #DBEAFE; color: #1D4ED8; }
  .icon-green { background: #DCFCE7; color: #166534; }
  .start-mode-title { font-weight: 700; font-size: 14px; color: var(--gray-800); }
  .start-mode-desc { font-size: 12.5px; color: var(--gray-500); }

  .pull-list-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
  .pull-list-actions { display: flex; gap: 10px; align-items: center; }
  .btn-cancel-custom {
    border: 1px solid var(--gray-200); background: #fff; color: var(--gray-600); border-radius: var(--radius-sm);
    padding: 8px 14px; font-weight: 600; font-size: 13px;
  }
  .btn-primary-custom {
    border: 0; background: var(--primary); color: #fff; border-radius: var(--radius-sm);
    padding: 8px 16px; font-weight: 700; font-size: 13px;
  }
  .pull-list-search { display: flex; gap: 10px; margin-top: 14px; }
  .pull-list-search input {
    flex: 1; border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 8px 12px; font-size: 13px;
  }
  .pull-results { margin-top: 10px; max-height: 200px; overflow: auto; border: 1px solid var(--gray-100); border-radius: 10px; }
  .pull-result-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-bottom: 1px solid var(--gray-100); }
  .pull-result-row:last-child { border-bottom: 0; }
  .pull-result-title { font-weight: 600; font-size: 13px; color: var(--gray-800); }
  .pull-result-meta { font-size: 12px; color: var(--gray-500); }

  .pull-table { width: 100%; border-collapse: collapse; }
  .pull-table th {
    background: var(--gray-50); font-size: 12px; font-weight: 700; color: var(--gray-600);
    padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--gray-100);
  }
  .pull-table td { padding: 10px 12px; border-bottom: 1px solid var(--gray-100); font-size: 13px; }
  .pull-table input[type="number"] { width: 90px; }

  @media (max-width: 900px) {
    .customers-toolbar { flex-direction: column; align-items: stretch; gap: 16px; }
    .search-wrap { max-width: 100%; flex-wrap: wrap; }
    .start-mode-grid { grid-template-columns: 1fr; }
  }

  @media (max-width: 768px) {
    .custom-table th:nth-child(3), .custom-table td:nth-child(3) { display: none; } /* Hide Created Date */
    .custom-table td, .custom-table th { padding: 12px 14px; font-size: 13px; }
  }

  @media (max-width: 576px) {
    .custom-table th:nth-child(4), .custom-table td:nth-child(4) { display: none; } /* Hide Items */
    .status-badge { padding: 4px 10px; min-width: 60px; font-size: 12px; }
  }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="customers-toolbar">
        <div class="search-wrap">
            <input type="text" class="search-input" placeholder="Search orders by supplier / order #" />
            <button class="btn-search"><i class="bi bi-search"></i> Search</button>
        </div>
        <button class="btn-new-order" data-bs-toggle="modal" data-bs-target="#newOrderModal">
            <i class="bi bi-plus-lg"></i> New Order
        </button>
    </div>

    <div class="order-tabs">
        <a href="{{ route('orders.index', ['status' => 'open']) }}" class="btn-tab {{ $currentStatus === 'open' ? 'active' : '' }}" style="text-decoration:none;">Open</a>
        <a href="{{ route('orders.index', ['status' => 'closed']) }}" class="btn-tab {{ $currentStatus === 'closed' ? 'active' : '' }}" style="text-decoration:none;">Closed</a>
        <a href="{{ route('orders.index', ['status' => 'all']) }}" class="btn-tab {{ $currentStatus === 'all' ? 'active' : '' }}" style="text-decoration:none;">All</a>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Supplier</th>
                        <th>Created Date</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th style="width: 120px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>{{ $order->internal_code ?? 'PO-'.str_pad($order->receiving_id, 8, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $order->supplier->company_name ?? '—' }}</td>
                            <td>{{ \Carbon\Carbon::parse($order->receiving_time)->format('m/d/Y') }}</td>
                            <td>{{ str_pad($order->items->count(), 2, '0', STR_PAD_LEFT) }}</td>
                            <td>
                                <span class="status-badge {{ $order->suspended ? 'status-closed' : 'status-open' }}">
                                    {{ $order->suspended ? 'Closed' : 'Open' }}
                                </span>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <button type="button" class="btn-action-icon" title="Edit" data-id="{{ $order->receiving_id }}" data-items="{{ json_encode($order->items->map(fn($i) => ['item_id' => $i->item_id, 'name' => $i->item->name ?? $i->description ?? 'Unknown', 'quantity' => (float)$i->quantity_purchased])) }}" onclick="editOrder(this.dataset.id, JSON.parse(this.dataset.items))">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <div class="dropdown">
                                        <button class="btn-action-icon dropdown-toggle-nocaret" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end action-dropdown-menu">
                                            @if(!$order->suspended)
                                            <li>
                                                <button class="action-dropdown-item" onclick="closeOrder({{ $order->receiving_id }}, '{{ $order->internal_code ?? 'PO-'.str_pad($order->receiving_id, 8, '0', STR_PAD_LEFT) }}')">
                                                    <i class="bi bi-check-circle text-success"></i> Close Order
                                                </button>
                                            </li>
                                            @endif
                                            <li>
                                                <a href="{{ route('orders.show', $order->receiving_id) }}" class="action-dropdown-item">
                                                    <i class="bi bi-eye text-primary"></i> View Details
                                                </a>
                                            </li>
                                            <li>
                                                <a href="{{ route('orders.print', $order->receiving_id) }}" target="_blank" class="action-dropdown-item">
                                                    <i class="bi bi-printer text-info"></i> Print Order
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button class="action-dropdown-item text-danger" onclick="deleteOrder({{ $order->receiving_id }})">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No orders yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="newOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header-custom">
                <h5 class="modal-title-custom">New Order - Select Supplier</h5>
                <button type="button" class="btn-close-custom" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body p-4">
                <input type="text" class="supplier-search-input" id="supplierSearch" placeholder="Search supplier...">

                <div class="supplier-list" id="supplierList">
                    @forelse($suppliers as $supplier)
                        <div class="supplier-item" data-supplier-id="{{ $supplier->person_id }}" data-supplier-name="{{ $supplier->company_name }}" data-supplier-email="{{ $supplier->email ?? '' }}">
                            <div class="supplier-info">
                                <div class="supplier-name">{{ $supplier->company_name }}</div>
                                <div class="supplier-email">{{ $supplier->email ?? '—' }}</div>
                            </div>
                            <i class="bi bi-chevron-right supplier-arrow"></i>
                        </div>
                    @empty
                        <div class="text-muted">No suppliers available.</div>
                    @endforelse
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="button" class="btn-cancel-custom" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="startModeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 650px;">
        <div class="modal-content modal-content-custom">
            <div class="modal-header-custom">
                <h5 class="modal-title-custom">New Order - Start Mode</h5>
                <button type="button" class="btn-close-custom" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body p-4">
                <p style="font-size: 14px; color: #334155;">Would you like to start from scratch or let the system pull items?</p>

                <div class="start-mode-grid">
                    <button type="button" class="start-mode-card" id="startScratch">
                        <div class="start-mode-icon icon-blue"><i class="bi bi-pencil"></i></div>
                        <div class="start-mode-title">Start from Scratch</div>
                        <div class="start-mode-desc">Manually add items for this supplier.</div>
                    </button>

                    <button type="button" class="start-mode-card" id="startAuto">
                        <div class="start-mode-icon icon-green"><i class="bi bi-lightning-charge-fill"></i></div>
                        <div class="start-mode-title">Auto Pull Items</div>
                        <div class="start-mode-desc">Pull items below reorder level.</div>
                    </button>
                </div>

                <div class="d-flex justify-content-between mt-5 pt-3 border-top">
                    <button type="button" class="btn-cancel-custom" data-bs-toggle="modal" data-bs-target="#newOrderModal">Back</button>
                    <button type="button" class="btn-cancel-custom" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="pullListModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content modal-content-custom">
            <div class="modal-header-custom">
                <h5 class="modal-title-custom">New Order - Pull List</h5>
                <button type="button" class="btn-close-custom" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body p-4">
                <div class="pull-list-header">
                    <div>
                        <div class="fw-bold" id="selectedSupplierName">Select a supplier</div>
                        <div class="text-muted" style="font-size: 12px;" id="selectedSupplierEmail"></div>
                    </div>
                    <div class="pull-list-actions">
                        <label class="d-flex align-items-center gap-2" style="font-size: 13px;">
                            <input type="checkbox" id="onlyBelowCheckbox" checked />
                            Only items at/below reorder level
                        </label>
                        <button type="button" class="btn-primary-custom" id="refreshPullList">Refresh</button>
                    </div>
                </div>

                <div class="pull-list-search" id="scratchSearchWrap">
                    <input type="text" id="scratchSearchInput" placeholder="Search items and item kits..." />
                    <button type="button" class="btn-primary-custom" id="scratchSearchBtn">Search</button>
                </div>
                <div class="pull-results" id="scratchResults"></div>

                <div class="table-responsive mt-4">
                    <table class="pull-table" id="pullListTable">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Item</th>
                                <th>SKU</th>
                                <th>Current Qty</th>
                                <th>Reorder Level</th>
                                <th>Order Qty</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <div class="text-muted small" id="pullListStatus"></div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn-cancel-custom" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn-primary-custom" id="savePullList">Save Order</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-content-custom">
            <div class="modal-header-custom">
                <h5 class="modal-title-custom">Edit Order Items</h5>
                <button type="button" class="btn-close-custom" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="editOrderId" />
                <div class="table-responsive">
                    <table class="pull-table" id="editOrderTable">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Quantity</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between mt-4">
                    <div class="text-muted small" id="editOrderStatus"></div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn-cancel-custom" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn-primary-custom" id="saveEditOrderBtn">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const supplierSearch = document.getElementById('supplierSearch');
    const supplierList = document.getElementById('supplierList');
    const startModeModal = new bootstrap.Modal(document.getElementById('startModeModal'));
    const pullListModal = new bootstrap.Modal(document.getElementById('pullListModal'));
    const newOrderModal = document.getElementById('newOrderModal');
    const selectedSupplierName = document.getElementById('selectedSupplierName');
    const selectedSupplierEmail = document.getElementById('selectedSupplierEmail');
    const onlyBelowCheckbox = document.getElementById('onlyBelowCheckbox');
    const refreshPullList = document.getElementById('refreshPullList');
    const scratchSearchWrap = document.getElementById('scratchSearchWrap');
    const scratchSearchInput = document.getElementById('scratchSearchInput');
    const scratchSearchBtn = document.getElementById('scratchSearchBtn');
    const scratchResults = document.getElementById('scratchResults');
    const pullListTableBody = document.querySelector('#pullListTable tbody');
    const pullListStatus = document.getElementById('pullListStatus');
    const savePullList = document.getElementById('savePullList');

    const pullListEndpoint = '{{ route('orders.pull-list') }}';
    const searchEndpoint = '{{ route('orders.search-items') }}';
    const storeEndpoint = '{{ route('orders.store') }}';

    let selectedSupplier = null;
    let mode = 'auto';
    const pullListItems = new Map();

    const setSupplier = (supplier) => {
        selectedSupplier = supplier;
        selectedSupplierName.textContent = supplier.name;
        selectedSupplierEmail.textContent = supplier.email || '';
    };

    const addToPullList = (item, qty = 1) => {
        const key = `${item.type}-${item.id}`;
        if (!pullListItems.has(key)) {
            pullListItems.set(key, {
                ...item,
                qty: qty,
            });
        }
        renderPullList();
    };

    const renderPullList = () => {
        pullListTableBody.innerHTML = '';
        if (!pullListItems.size) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="7" class="text-center text-muted">No items in pull list.</td>';
            pullListTableBody.appendChild(row);
            return;
        }

        pullListItems.forEach((item, key) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.type === 'kit' ? 'Item Kit' : 'Item'}</td>
                <td>${item.name}</td>
                <td>${item.sku || '—'}</td>
                <td>${item.current_quantity ?? '—'}</td>
                <td>${item.reorder_level ?? '—'}</td>
                <td><input type="number" step="0.001" class="form-control form-control-sm" value="${item.qty}" data-key="${key}" /></td>
                <td><button class="btn btn-sm btn-outline-danger" data-remove="${key}"><i class="bi bi-trash"></i></button></td>
            `;
            pullListTableBody.appendChild(row);
        });
    };

    const fetchPullList = async () => {
        if (!selectedSupplier) return;
        const params = new URLSearchParams({
            supplier_id: selectedSupplier.id,
            only_below: onlyBelowCheckbox.checked ? '1' : '0',
        });
        const response = await fetch(`${pullListEndpoint}?${params.toString()}`);
        const data = await response.json();
        pullListItems.clear();

        [...data.items, ...data.kits].forEach((item) => {
            let qty = 1;
            if (item.reorder_level !== null && item.current_quantity !== null) {
                qty = Math.max(1, item.reorder_level - item.current_quantity);
            }
            addToPullList(item, qty);
        });
    };

    const fetchScratchResults = async () => {
        if (!selectedSupplier) {
            console.warn('No supplier selected');
            return;
        }
        const term = scratchSearchInput.value.trim();
        if (!term) {
            scratchResults.innerHTML = '<div class="text-muted p-3">Please enter a search term.</div>';
            return;
        }
        
        scratchResults.innerHTML = '<div class="text-muted p-3">Searching...</div>';
        
        const params = new URLSearchParams({
            supplier_id: selectedSupplier.id,
            q: term,
        });
        
        try {
            const response = await fetch(`${searchEndpoint}?${params.toString()}`);
            if (!response.ok) throw new Error('Network response was not ok');
            const data = await response.json();
            scratchResults.innerHTML = '';

            const combined = [...data.items, ...data.kits];
            if (!combined.length) {
                scratchResults.innerHTML = '<div class="text-muted p-3">No matches found.</div>';
                return;
            }

            combined.forEach((item) => {
                const row = document.createElement('div');
                row.className = 'pull-result-row';
                row.innerHTML = `
                    <div>
                        <div class="pull-result-title">${item.name}</div>
                        <div class="pull-result-meta">${item.type === 'kit' ? 'Item Kit' : 'Item'} | ${item.sku || '—'}</div>
                    </div>
                    <button class="btn btn-sm btn-outline-primary" data-add="${item.type}-${item.id}">Add</button>
                `;
                row.querySelector('[data-add]').addEventListener('click', () => {
                    addToPullList(item, 1);
                });
                scratchResults.appendChild(row);
            });
        } catch (error) {
            console.error('Search error:', error);
            scratchResults.innerHTML = '<div class="text-danger p-3">Error performing search.</div>';
        }
    };

    let searchTimeout;
    scratchSearchInput?.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetchScratchResults();
        }, 400);
    });

    scratchSearchBtn?.addEventListener('click', fetchScratchResults);
    scratchSearchInput?.addEventListener('keypress', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            clearTimeout(searchTimeout);
            fetchScratchResults();
        }
    });

    supplierList?.addEventListener('click', (event) => {
        const item = event.target.closest('.supplier-item');
        if (!item) return;
        const supplier = {
            id: item.dataset.supplierId,
            name: item.dataset.supplierName,
            email: item.dataset.supplierEmail,
        };
        setSupplier(supplier);
        const modal = bootstrap.Modal.getInstance(newOrderModal);
        modal?.hide();
        startModeModal.show();
    });

    supplierSearch?.addEventListener('input', () => {
        const term = supplierSearch.value.toLowerCase();
        supplierList.querySelectorAll('.supplier-item').forEach((item) => {
            const name = item.dataset.supplierName.toLowerCase();
            const email = (item.dataset.supplierEmail || '').toLowerCase();
            item.style.display = name.includes(term) || email.includes(term) ? '' : 'none';
        });
    });

    document.getElementById('startScratch')?.addEventListener('click', () => {
        mode = 'scratch';
        scratchSearchWrap.style.display = '';
        onlyBelowCheckbox.checked = false;
        pullListItems.clear();
        renderPullList();
        startModeModal.hide();
        pullListModal.show();
    });

    document.getElementById('startAuto')?.addEventListener('click', async () => {
        mode = 'auto';
        scratchSearchWrap.style.display = 'none';
        scratchResults.innerHTML = '';
        onlyBelowCheckbox.checked = true;
        startModeModal.hide();
        pullListModal.show();
        await fetchPullList();
    });

    refreshPullList?.addEventListener('click', async () => {
        if (mode === 'auto') {
            await fetchPullList();
        } else {
            await fetchScratchResults();
        }
    });

    pullListTableBody?.addEventListener('input', (event) => {
        const input = event.target.closest('input[data-key]');
        if (!input) return;
        const key = input.dataset.key;
        const value = parseFloat(input.value || '0');
        const item = pullListItems.get(key);
        if (item) {
            item.qty = value;
        }
    });

    pullListTableBody?.addEventListener('click', (event) => {
        const removeBtn = event.target.closest('[data-remove]');
        if (!removeBtn) return;
        const key = removeBtn.dataset.remove;
        pullListItems.delete(key);
        renderPullList();
    });

    savePullList?.addEventListener('click', async () => {
        if (!selectedSupplier) {
            pullListStatus.textContent = 'Select a supplier first.';
            return;
        }

        if (pullListItems.size === 0) {
            pullListStatus.textContent = 'Add items before saving.';
            return;
        }

        savePullList.disabled = true;
        pullListStatus.textContent = 'Saving...';

        const items = Array.from(pullListItems.values()).map(item => ({
            type: item.type,
            item_id: item.id,
            quantity: item.qty
        }));

        try {
            const response = await fetch(storeEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    supplier_id: selectedSupplier.id,
                    items: items
                })
            });

            const data = await response.json();

            if (data.success) {
                pullListStatus.textContent = 'Order saved successfully!';
                setTimeout(() => {
                    pullListModal.hide();
                    window.location.reload();
                }, 1500);
            } else {
                pullListStatus.textContent = data.message || 'Error occurred.';
                savePullList.disabled = false;
            }
        } catch (error) {
            pullListStatus.textContent = 'Server error occurred.';
            savePullList.disabled = false;
        }
    });

    document.getElementById('pullListModal')?.addEventListener('hidden.bs.modal', () => {
        pullListItems.clear();
        renderPullList();
        scratchResults.innerHTML = '';
        scratchSearchInput.value = '';
        pullListStatus.textContent = '';
        mode = 'auto';
    });

    window.closeOrder = async (id, code) => {
        const { value: confirmClose } = await Swal.fire({
            title: 'Close Order?',
            text: `Are you sure you want to close order ${code}? This will generate a receiving and update inventory based on received quantities.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, close it!'
        });

        if (!confirmClose) return;

        try {
            const response = await fetch(`/orders/${id}/close`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            const data = await response.json();
            if (data.success) {
                await Swal.fire('Closed!', data.message, 'success');
                window.location.reload();
            } else {
                Swal.fire('Error', data.message || 'Error closing order', 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'Server error occurred.', 'error');
        }
    };

    const editOrderModal = new bootstrap.Modal(document.getElementById('editOrderModal'));
    const editOrderTableBody = document.querySelector('#editOrderTable tbody');
    const editOrderIdInput = document.getElementById('editOrderId');
    const saveEditOrderBtn = document.getElementById('saveEditOrderBtn');
    const editOrderStatus = document.getElementById('editOrderStatus');

    window.editOrder = (id, items) => {
        editOrderIdInput.value = id;
        editOrderTableBody.innerHTML = '';
        
        items.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.name}</td>
                <td><input type="number" step="0.001" class="form-control form-control-sm edit-item-qty" data-id="${item.item_id}" value="${item.quantity}" /></td>
            `;
            editOrderTableBody.appendChild(row);
        });

        editOrderStatus.textContent = '';
        editOrderModal.show();
    };

    saveEditOrderBtn.addEventListener('click', async () => {
        const id = editOrderIdInput.value;
        const inputs = editOrderTableBody.querySelectorAll('.edit-item-qty');
        const items = Array.from(inputs).map(input => ({
            item_id: input.dataset.id,
            quantity: parseFloat(input.value || '0')
        }));

        saveEditOrderBtn.disabled = true;
        editOrderStatus.textContent = 'Saving...';

        try {
            const response = await fetch(`/orders/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ items })
            });

            const data = await response.json();
            if (data.success) {
                editOrderStatus.textContent = 'Updated successfully!';
                setTimeout(() => window.location.reload(), 1000);
            } else {
                editOrderStatus.textContent = data.message || 'Error updating order.';
                saveEditOrderBtn.disabled = false;
            }
        } catch (error) {
            editOrderStatus.textContent = 'Server error occurred.';
            saveEditOrderBtn.disabled = false;
        }
    });

    window.deleteOrder = async (id) => {
        if (!confirm('Are you sure you want to delete this order?')) return;
        try {
            const response = await fetch(`/orders/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            const data = await response.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message || 'Error deleting order');
            }
        } catch (error) {
            alert('Server error occurred.');
        }
    };
})();
</script>
@endpush
