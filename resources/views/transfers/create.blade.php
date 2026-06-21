@extends('layouts.app')

@section('title', 'New Transfer')
@section('page-title', 'Transfer / New Transfer')

@push('styles')
<style>
    .category-tabs { display: inline-flex; border: 1px solid var(--gray-200); border-radius: 10px; overflow: hidden; background: #fff; }
    .category-tabs .tab-btn { border: 0; background: transparent; padding: 8px 16px; font-weight: 600; color: var(--gray-700); }
    .category-tabs .tab-btn + .tab-btn { border-left: 1px solid var(--gray-200); }
    .category-tabs .tab-btn.is-active { background: var(--primary); color: #fff; }
    .category-grid { display: grid; grid-template-columns: repeat(8, minmax(0, 1fr)); gap: 12px; }
    @media (max-width: 1600px) { .category-grid { grid-template-columns: repeat(6, minmax(0, 1fr)); } }
    @media (max-width: 1200px) { .category-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    @media (max-width: 992px) { .category-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 576px) { .category-grid { grid-template-columns: 1fr; } }
    .category-card { border: 1px solid var(--gray-200); border-radius: 12px; background: #fff; text-align: left; padding: 0; overflow: hidden; transition: var(--transition); }
    .category-card:hover { border-color: var(--primary-light); box-shadow: var(--shadow-sm); }
    .category-name { padding: 14px 12px; font-weight: 600; color: var(--gray-900); text-align: center; }
    .category-pagination { display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding-top: 12px; }
    .category-pagination .page-btn { border: 1px solid var(--gray-200); background: #fff; padding: 4px 10px; border-radius: 8px; font-weight: 600; color: var(--gray-700); }
    .category-pagination .page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .category-pagination .page-info { font-size: 0.9rem; color: var(--gray-500); }

    [data-theme='dark'] .category-tabs { background: var(--gray-100); border-color: var(--gray-200); }
    [data-theme='dark'] .category-tabs .tab-btn { color: var(--gray-800); border-color: var(--gray-200); }
    [data-theme='dark'] .category-tabs .tab-btn.is-active { background: var(--primary); color: #fff; }
    [data-theme='dark'] .category-card { background: var(--gray-100); border-color: var(--gray-200); }
    [data-theme='dark'] .category-name { color: var(--gray-900); }
    [data-theme='dark'] .input-group-text.bg-light,
    [data-theme='dark'] input.bg-light {
        background-color: var(--gray-100) !important;
        border-color: var(--gray-200) !important;
        color: var(--gray-800) !important;
    }
    [data-theme='dark'] input.bg-light::placeholder {
        color: var(--gray-600) !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">
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
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Item Selection</h6>
                    <div class="input-group input-group-sm w-50">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="item_search" class="form-control bg-light border-start-0" placeholder="Search item or scan barcode..." autocomplete="off">
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="min-height: 400px;">
                        <table class="table table-hover align-middle mb-0 custom-table">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4" width="50">#</th>
                                    <th>Item Name</th>
                                    <th width="120">Cost Price</th>
                                    <th width="120">Qty</th>
                                    <th class="text-end" width="120">Total</th>
                                    <th class="text-end pe-4" width="50"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cart['items'] as $index => $item)
                                    @php $isKit = ($item['type'] ?? 'item') === 'kit'; @endphp
                                    <tr>
                                        <td class="ps-4">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="fw-bold text-primary">{{ $item['name'] }}</div>
                                            @if($isKit)
                                                <small class="badge bg-primary-subtle text-primary ms-1">Kit</small>
                                            @else
                                                <small class="text-muted">ID: {{ $item['item_id'] }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            ${{ number_format($item['cost_price'], 2) }}
                                        </td>
                                        <td>
                                            <form action="{{ route('transfers.item.edit', $index) }}" method="POST">
                                                @csrf
                                                <input type="number" step="0.001" name="quantity" class="form-control form-control-sm" value="{{ $item['quantity'] }}" onchange="this.form.submit()">
                                            </form>
                                        </td>
                                        <td class="text-end fw-bold">
                                            ${{ number_format($item['cost_price'] * $item['quantity'], 2) }}
                                        </td>
                                        <td class="text-end pe-4">
                                            <form action="{{ route('transfers.item.remove', $index) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm text-danger"><i class="bi bi-x-circle"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-arrow-left-right fs-1 d-block mb-2"></i>
                                            Your transfer cart is empty.
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
            
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form action="{{ route('transfers.location.set') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-muted">Source Location</label>
                            <select name="from_location_id" class="form-select border-primary" onchange="this.form.submit()">
                                @foreach($locations as $location)
                                    <option value="{{ $location->location_id }}" @selected($cart['from_location_id'] == $location->location_id)>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-muted">Destination Location</label>
                            <select name="to_location_id" class="form-select border-primary" onchange="this.form.submit()">
                                <option value="">— Select Destination —</option>
                                @foreach($locations as $location)
                                    @if($cart['from_location_id'] != $location->location_id)
                                        <option value="{{ $location->location_id }}" @selected($cart['to_location_id'] == $location->location_id)>
                                            {{ $location->name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-muted">Notes</label>
                            <textarea name="comment" class="form-control form-control-sm" placeholder="Optional comments" onchange="this.form.submit()">{{ $cart['comment'] ?? '' }}</textarea>
                        </div>
                    </form>

                    <form action="{{ route('transfers.supplier.set') }}" method="POST">
                        @csrf
                        <div class="mb-1">
                            <label class="form-label small fw-bold text-uppercase text-muted">Filter by Supplier</label>
                            <div class="input-group">
                                <select name="supplier_id" class="form-select border-primary" onchange="this.form.submit()">
                                    <option value="">— All Suppliers —</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->person_id }}" @selected($cart['supplier_id'] == $supplier->person_id)>
                                            {{ $supplier->person?->first_name }} {{ $supplier->person?->last_name }} {{ $supplier->company_name ? '('.$supplier->company_name.')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($cart['supplier_id'])
                                    <button type="submit" name="supplier_id" value="" class="btn btn-outline-danger"><i class="bi bi-x"></i></button>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary & Complete -->
            <div class="card shadow-sm border-0 bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-uppercase small mb-4 opacity-75 fw-bold">Transfer Summary</h6>
                    <div class="d-flex justify-content-between mb-4 fs-4 border-bottom pb-3">
                        <span class="fw-bold">Total Items</span>
                        <span class="fw-bold">{{ collect($cart['items'])->sum('quantity') }}</span>
                    </div>

                    <form action="{{ route('transfers.save') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-lg w-100 fw-bold mb-2" {{ empty($cart['items']) ? 'disabled' : '' }}>
                            <i class="bi bi-save me-2"></i> SAVE TRANSFER
                        </button>
                    </form>

                    <form action="{{ route('transfers.complete') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-light btn-lg w-100 fw-bold text-primary mb-2" {{ empty($cart['items']) ? 'disabled' : '' }}>
                            <i class="bi bi-check2-circle me-2"></i> COMPLETE TRANSFER
                        </button>
                    </form>
                    
                    <form action="{{ route('transfers.cancel') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-link btn-sm w-100 text-white opacity-75 text-decoration-none mt-2" onclick="return confirm('Clear cart?')">
                            <i class="bi bi-trash"></i> Cancel Transfer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="search_results" class="position-absolute shadow-sm bg-white rounded-bottom" style="display:none; z-index: 1000; width: 300px;"></div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Category tabs and grid toggle
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
    const storageKey = 'transfersCategoryGridHidden';

    const grid = document.getElementById('category-grid');
    const paginationEl = document.getElementById('category-grid-pagination');
    const browseUrl = "{{ route('transfers.categories') }}";
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
            fetch(`{{ route('transfers.search') }}?term=${term}`)
                .then(res => res.json())
                .then(data => {
                    resultsDiv.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'p-2 border-bottom cursor-pointer hover-bg-light';
                            div.style.cursor = 'pointer';
                            div.innerHTML = `<strong>${item.name}</strong> <small class="text-muted">($${item.cost_price})</small>`;
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
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = "{{ route('transfers.item.add') }}";
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
    .hover-bg-light:hover { background-color: var(--gray-50); }
    [data-theme='dark'] .hover-bg-light:hover { background-color: var(--gray-100); }
</style>
@endpush
