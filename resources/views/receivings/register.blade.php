@extends('layouts.app')

@section('title', 'New purchase')
@section('page-title', 'New purchase')

@push('styles')
<style>
    .category-tabs { display: inline-flex; border: 1px solid #d7e0ea; border-radius: 10px; overflow: hidden; background: #fff; }
    .category-tabs .tab-btn { border: 0; background: transparent; padding: 8px 16px; font-weight: 600; color: #334155; }
    .category-tabs .tab-btn + .tab-btn { border-left: 1px solid #e2e8f0; }
    .category-tabs .tab-btn.is-active { background: #3b82f6; color: #fff; }
    .category-grid { display: grid; grid-template-columns: repeat(8, minmax(0, 1fr)); gap: 12px; }
    @media (max-width: 1600px) { .category-grid { grid-template-columns: repeat(6, minmax(0, 1fr)); } }
    @media (max-width: 1200px) { .category-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    @media (max-width: 992px) { .category-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 576px) { .category-grid { grid-template-columns: 1fr; } }
    .category-card { border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; text-align: left; padding: 0; overflow: hidden; transition: border-color .2s ease, box-shadow .2s ease; }
    .category-card:hover { border-color: #cbd5f5; box-shadow: 0 6px 14px rgba(15, 23, 42, 0.08); }
    .category-name { padding: 14px 12px; font-weight: 600; color: #0f172a; text-align: center; }
    .category-pagination { display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding-top: 12px; }
    .category-pagination .page-btn { border: 1px solid #d7e0ea; background: #fff; padding: 4px 10px; border-radius: 8px; font-weight: 600; color: #334155; }
    .category-pagination .page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .category-pagination .page-info { font-size: 0.9rem; color: #64748b; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <a href="{{ route('purchases.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to purchases
        </a>
    </div>
    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4" id="category-grid-card">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="category-tabs" role="tablist" aria-label="Category tabs">
                <button type="button" class="tab-btn is-active" role="tab" aria-selected="true">Categories</button>
                <button type="button" class="tab-btn" role="tab" aria-selected="false">Tags</button>
                <button type="button" class="tab-btn" role="tab" aria-selected="false">Suppliers</button>
                <button type="button" class="tab-btn" role="tab" aria-selected="false">Favorites</button>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="toggle-category-grid" data-show-text="Show Grid" data-hide-text="Hide Grid">Hide Grid</button>
        </div>
        <div class="card-body" id="category-grid-body">
            <div class="category-grid" id="category-grid">
                @forelse($categories as $category)
                    <button type="button" class="category-card" data-category-id="{{ $category->id }}" data-category-name="{{ $category->name }}">
                        <div class="category-name">{{ $category->name }}</div>
                    </button>
                @empty
                    <div class="text-muted">No categories available.</div>
                @endforelse
            </div>
            <div class="category-pagination" id="category-grid-pagination"></div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Side: Cart -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center position-relative">
                    <h6 class="m-0 font-weight-bold text-primary">Item Selection</h6>
                    <div class="input-group input-group-sm w-50">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="item_search" class="form-control bg-light border-start-0" placeholder='Search item or scan barcode... (prefix "#" for SKU)' autocomplete="off">
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="min-height: 400px;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4" width="50">#</th>
                                    <th>Item Name</th>
                                    <th width="120">Cost Price</th>
                                    <th width="100">Qty</th>
                                    <th width="100">Disc %</th>
                                    <th class="text-end" width="120">Total</th>
                                    <th class="text-end pe-4" width="50"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cart['items'] as $index => $item)
                                    <tr>
                                        <td class="ps-4">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="fw-bold text-primary">
                                                @if(($item['type'] ?? 'item') === 'kit')
                                                    <span class="badge bg-purple me-1" style="background:#7c3aed">KIT</span>
                                                @endif
                                                {{ $item['name'] }}
                                            </div>
                                            <small class="text-muted">
                                                ID: {{ ($item['type'] ?? 'item') === 'kit' ? ($item['item_kit_id'] ?? '') : ($item['item_id'] ?? '') }}
                                            </small>
                                        </td>
                                        <td>
                                            <form action="{{ route('receivings.item.edit', $index) }}" method="POST">
                                                @csrf
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" step="0.01" name="cost_price" class="form-control" value="{{ $item['cost_price'] }}" onchange="this.form.submit()">
                                                </div>
                                            </form>
                                        </td>
                                        <td>
                                            <form action="{{ route('receivings.item.edit', $index) }}" method="POST">
                                                @csrf
                                                <input type="number" step="0.001" name="quantity" class="form-control form-control-sm" value="{{ $item['quantity'] }}" onchange="this.form.submit()">
                                            </form>
                                        </td>
                                        <td>
                                            <form action="{{ route('receivings.item.edit', $index) }}" method="POST">
                                                @csrf
                                                <input type="number" step="0.1" name="discount" class="form-control form-control-sm" value="{{ $item['discount'] }}" onchange="this.form.submit()">
                                            </form>
                                        </td>
                                        <td class="text-end fw-bold">
                                            ${{ number_format($item['cost_price'] * $item['quantity'] * (1 - $item['discount'] / 100), 2) }}
                                        </td>
                                        <td class="text-end pe-4">
                                            <form action="{{ route('receivings.item.remove', $index) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm text-danger"><i class="bi bi-x-circle"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="bi bi-cart fs-1 d-block mb-2"></i>
                                            Your purchase cart is empty.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Controls -->
        <div class="col-lg-4">
            <!-- Mode Selection -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form action="{{ route('receivings.mode.set') }}" method="POST" id="mode-form">
                        @csrf
                        <label class="form-label small fw-bold text-uppercase text-muted">Purchase mode</label>
                        <select name="mode" class="form-select form-select-lg border-primary text-primary fw-bold" onchange="this.form.submit()">
                            <option value="receive" @selected($cart['mode'] == 'receive')>Receive (Stock In)</option>
                            <option value="return" @selected($cart['mode'] == 'return')>Return (Stock Out)</option>
                            <option value="transfer" @selected($cart['mode'] == 'transfer')>Transfer</option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- Supplier Selection -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form action="{{ route('receivings.supplier.set') }}" method="POST">
                        @csrf
                        <label class="form-label small fw-bold text-uppercase text-muted">Select Supplier</label>
                        <div class="input-group">
                            <select name="supplier_id" class="form-select select2-supplier" onchange="this.form.submit()">
                                <option value="">— Select Supplier —</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->person_id }}" @selected($cart['supplier_id'] == $supplier->person_id)>
                                        {{ $supplier->company_name }} ({{ $supplier->person->last_name }})
                                    </option>
                                @endforeach
                            </select>
                            @if($cart['supplier_id'])
                                <button type="submit" name="supplier_id" value="" class="btn btn-outline-danger"><i class="bi bi-x"></i></button>
                            @endif
                        </div>
                    </form>

                    @if($cart['supplier_id'])
                        @php $selectedSupplier = $suppliers->firstWhere('person_id', $cart['supplier_id']); @endphp
                        <div class="mt-3 p-3 bg-light rounded small">
                            <div class="fw-bold">{{ $selectedSupplier->company_name }}</div>
                            <div class="text-muted">{{ $selectedSupplier->person->phone_number }}</div>
                            <div class="mt-2 text-primary fw-bold">Current Balance: ${{ number_format($selectedSupplier->balance, 2) }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Summary & Complete -->
            <div class="card shadow-sm border-0 bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-uppercase small mb-4 opacity-75 fw-bold">Order Summary</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span class="fw-bold">${{ number_format($subtotal, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-4 fs-4 border-top pt-3 mt-3">
                        <span class="fw-bold">TOTAL</span>
                        <span class="fw-bold">${{ number_format($total, 2) }}</span>
                    </div>

                    <div class="mb-3">
                        <textarea name="comment" id="recv-comment" class="form-control form-control-sm bg-primary border-white border-opacity-25 text-white placeholder-white" placeholder="Add notes/comments..."></textarea>
                    </div>

                    <form action="{{ route('receivings.suspend') }}" method="POST">
                        @csrf
                        <input type="hidden" name="comment" id="suspend-comment">
                        <button type="submit" class="btn btn-light btn-lg w-100 text-primary fw-bold mb-2" {{ empty($cart['items']) ? 'disabled' : '' }} onclick="document.getElementById('suspend-comment').value=document.getElementById('recv-comment').value">
                            <i class="bi bi-pause-circle me-2"></i> Suspend
                        </button>
                    </form>

                    <form action="{{ route('receivings.complete') }}" method="POST">
                        @csrf
                        <input type="hidden" name="comment" id="complete-comment">
                        <button type="submit" class="btn btn-outline-light btn-lg w-100 fw-bold  mb-2" {{ empty($cart['items']) ? 'disabled' : '' }} onclick="document.getElementById('complete-comment').value=document.getElementById('recv-comment').value">
                            <i class="bi bi-check2-circle me-2"></i> Finish purchase
                        </button>
                    </form>


                    <form action="{{ route('receivings.cancel') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-link btn-sm w-100 text-white opacity-75 text-decoration-none mt-2" onclick="return confirm('Clear cart?')">
                            <i class="bi bi-trash"></i> Cancel
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal Placeholder or Search suggestions would go here -->
<div id="search_results" class="position-absolute shadow-sm bg-white rounded-bottom" style="display:none; z-index: 1000;"></div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.category-tabs').forEach((tabList) => {
        const tabs = tabList.querySelectorAll('.tab-btn');
        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                tabs.forEach((btn) => {
                    btn.classList.remove('is-active');
                    btn.setAttribute('aria-selected', 'false');
                });
                tab.classList.add('is-active');
                tab.setAttribute('aria-selected', 'true');
            });
        });
    });

    const toggleButton = document.getElementById('toggle-category-grid');
    const gridBody = document.getElementById('category-grid-body');
    const storageKey = 'receivingsCategoryGridHidden';

    const grid = document.getElementById('category-grid');
    const paginationEl = document.getElementById('category-grid-pagination');
    const browseUrl = "{{ route('receivings.categories') }}";
    const categoryStack = [];
    const defaultPageSize = 8;
    const childPageSize = 7;

    const renderBackTile = () => {
        if (!categoryStack.length) return '';
        return `
            <button type="button" class="category-card" data-action="back">
                <div class="category-name">Back</div>
            </button>
        `;
    };

    const renderCategoryTile = (category) => {
        return `
            <button type="button" class="category-card" data-category-id="${category.id}" data-category-name="${category.name}">
                <div class="category-name">${category.name}</div>
            </button>
        `;
    };

    const renderProductTile = (product) => {
        return `
            <button type="button" class="category-card" data-product-id="${product.id}" data-product-type="${product.type}">
                <div class="category-name">${product.name}</div>
            </button>
        `;
    };

    const renderPagination = (pagination) => {
        if (!paginationEl) return;
        if (!pagination || pagination.last_page <= 1) {
            paginationEl.innerHTML = '';
            return;
        }

        paginationEl.innerHTML = `
            <button type="button" class="page-btn" data-page="${pagination.page - 1}" ${pagination.page <= 1 ? 'disabled' : ''}>Prev</button>
            <span class="page-info">Page ${pagination.page} of ${pagination.last_page}</span>
            <button type="button" class="page-btn" data-page="${pagination.page + 1}" ${pagination.page >= pagination.last_page ? 'disabled' : ''}>Next</button>
        `;
    };

    const renderGrid = (data) => {
        if (!grid) return;
        const tiles = [];
        tiles.push(renderBackTile());

        if (data.level === 'categories') {
            if (data.categories.length) {
                data.categories.forEach((category) => tiles.push(renderCategoryTile(category)));
            } else {
                tiles.push('<div class="text-muted">No categories available.</div>');
            }
        } else {
            if (data.products.length) {
                data.products.forEach((product) => tiles.push(renderProductTile(product)));
            } else {
                tiles.push('<div class="text-muted">No products found.</div>');
            }
        }

        grid.innerHTML = tiles.join('');
        renderPagination(data.pagination);
    };

    const loadLevel = (categoryId, pushStack, page = 1) => {
        const params = new URLSearchParams();
        const perPage = categoryId ? childPageSize : defaultPageSize;
        params.set('page', String(page));
        params.set('per_page', String(perPage));
        if (categoryId) params.set('category_id', categoryId);
        const url = `${browseUrl}?${params.toString()}`;
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (pushStack && data.current) {
                    const last = categoryStack[categoryStack.length - 1];
                    if (!last || last.id !== data.current.id) {
                        categoryStack.push({ id: data.current.id, name: data.current.name, page: 1 });
                    }
                } else if (categoryStack.length) {
                    categoryStack[categoryStack.length - 1].page = data.pagination?.page || 1;
                }
                renderGrid(data);
            })
            .catch(() => {
                if (grid) grid.innerHTML = '<div class="text-muted">Unable to load categories.</div>';
            });
    };

    if (grid) {
        grid.addEventListener('click', (event) => {
            const backTile = event.target.closest('[data-action="back"]');
            if (backTile) {
                categoryStack.pop();
                const prev = categoryStack[categoryStack.length - 1];
                loadLevel(prev ? prev.id : null, false, prev?.page || 1);
                return;
            }

            const categoryTile = event.target.closest('[data-category-id]');
            if (categoryTile) {
                const categoryId = categoryTile.getAttribute('data-category-id');
                loadLevel(categoryId, true, 1);
                return;
            }

            const productTile = event.target.closest('[data-product-id]');
            if (productTile) {
                const productId = productTile.getAttribute('data-product-id');
                const productType = productTile.getAttribute('data-product-type');
                const itemId = productType === 'kit' ? `KIT ${productId}` : productId;
                addItem(itemId);
            }
        });
    }

    if (paginationEl) {
        paginationEl.addEventListener('click', (event) => {
            const button = event.target.closest('[data-page]');
            if (!button || button.disabled) return;
            const page = parseInt(button.getAttribute('data-page'), 10);
            const current = categoryStack[categoryStack.length - 1];
            loadLevel(current ? current.id : null, false, page);
        });
    }

    if (toggleButton && gridBody) {
        const showText = toggleButton.getAttribute('data-show-text') || 'Show Grid';
        const hideText = toggleButton.getAttribute('data-hide-text') || 'Hide Grid';

        const applyGridState = (isHidden) => {
            gridBody.style.display = isHidden ? 'none' : '';
            toggleButton.textContent = isHidden ? showText : hideText;
        };

        const initialHidden = localStorage.getItem(storageKey) === '1';
        applyGridState(initialHidden);

        toggleButton.addEventListener('click', () => {
            const isHidden = gridBody.style.display === 'none';
            const nextHidden = !isHidden;
            applyGridState(nextHidden);
            localStorage.setItem(storageKey, nextHidden ? '1' : '0');
        });
    }

    const searchInput = document.getElementById('item_search');
    const resultsDiv = document.getElementById('search_results');

    let timer;
    searchInput.addEventListener('input', function() {
        clearTimeout(timer);
        const term = this.value;
        if (term.length < 2) {
            resultsDiv.style.display = 'none';
            return;
        }

        timer = setTimeout(() => {
            fetch(`{{ route('receivings.search') }}?term=${term}`)
                .then(res => res.json())
                .then(data => {
                    resultsDiv.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'search-result-item';
                            const type = item.type || 'item';
                            const displayName = item.display_name || item.name;
                            const price = item.type === 'kit'
                                ? `$${parseFloat(item.cost_price || 0).toFixed(2)}`
                                : `$${parseFloat(item.cost_price || item.unit_price || 0).toFixed(2)}`;
                            div.innerHTML = `
                                <span class="search-type-badge ${type}">${type}</span>
                                <div class="search-result-info">
                                    <div class="name">${displayName}</div>
                                </div>
                                <div class="search-result-price">${price}</div>
                            `;
                            div.onclick = () => addItem(item.item_id);
                            resultsDiv.appendChild(div);
                        });

                        const rect = searchInput.getBoundingClientRect();
                        resultsDiv.style.top = (rect.bottom + window.scrollY) + 'px';
                        resultsDiv.style.left = rect.left + 'px';
                        resultsDiv.style.width = rect.width + 'px';
                        resultsDiv.style.display = 'block';
                    } else {
                        resultsDiv.style.display = 'none';
                    }
                });
        }, 300);
    });

    function addItem(itemId) {
        if (window.Swal) {
            Swal.fire({
                icon: 'question',
                title: 'Add item?',
                text: 'Do you want to add this item to the purchase cart?',
                showCancelButton: true,
                confirmButtonText: 'Add',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#3b82f6',
            }).then((result) => {
                if (result.isConfirmed) {
                    submitAddItem(itemId);
                }
            });
        } else {
            submitAddItem(itemId);
        }
    }

    function submitAddItem(itemId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = "{{ route('receivings.item.add') }}";
        form.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="item_id" value="${itemId}">`;
        document.body.appendChild(form);
        form.submit();
    }

    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
            resultsDiv.style.display = 'none';
        }
    });
});
</script>
<style>
    .hover-bg-light:hover { background-color: #f8f9fa; }
    #search_results {
        border: 1px solid #d1d9e6;
        border-radius: 0 0 10px 10px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        max-height: 360px;
        overflow-y: auto;
    }
    #search_results .search-result-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-bottom: 1px solid #eef2f6;
        cursor: pointer;
        transition: background 0.15s;
    }
    #search_results .search-result-item:last-child {
        border-bottom: none;
    }
    #search_results .search-result-item:hover {
        background: #f0f4ff;
    }
    .search-type-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        padding: 2px 7px;
        border-radius: 5px;
        text-transform: uppercase;
        flex-shrink: 0;
        min-width: 38px;
        text-align: center;
    }
    .search-type-badge.item {
        background: #e8f0fe;
        color: #1a73e8;
    }
    .search-type-badge.kit {
        background: #f3e8ff;
        color: #7c3aed;
    }
    .search-type-badge.variant {
        background: #fff3e0;
        color: #e65100;
    }
    .search-result-info {
        flex: 1;
        min-width: 0;
    }
    .search-result-info .name {
        font-weight: 600;
        color: #0f172a;
        font-size: 0.9rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .search-result-price {
        font-weight: 700;
        color: #0f172a;
        font-size: 0.9rem;
        white-space: nowrap;
    }
</style>
@endpush
