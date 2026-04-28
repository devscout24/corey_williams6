@extends('layouts.app')

@section('title', 'Item Labels')
@section('page-title', 'Inventory / Labels')

@push('styles')
<style>
        .labels-wrap { max-width: 1100px; margin: 0 auto; }
        .labels-card {
            background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--gray-200);
            padding: 24px; box-shadow: var(--shadow-xs); max-width: 820px;
            margin: 0 auto; transition: max-width 0.3s ease;
        }
        .labels-tabs { display: flex; gap: 12px; }
        .btn-tab {
            padding: 8px 16px; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600;
            border: none; cursor: pointer; transition: var(--transition);
        }
        .btn-tab.active { background: var(--primary); color: #fff; }
        .btn-tab:not(.active) { background: #EEF2FF; color: var(--primary); }
        .btn-tab:not(.active):hover { background: #E0E7FF; }

        .form-group { margin-bottom: 18px; }
        .form-label {
            font-size: 13.5px; font-weight: 600; color: var(--gray-700);
            margin-bottom: 8px; display: block;
        }
        .form-control {
            background: #fff; border: 1px solid var(--gray-300); border-radius: var(--radius-sm);
            padding: 10px 16px; font-size: 14px; color: var(--gray-900); transition: var(--transition);
            box-shadow: none; width: 100%;
        }
        .form-control:focus { border-color: var(--primary); outline: none; }

        .btn-primary-action {
            background: var(--primary); color: #fff; border: none; border-radius: var(--radius-sm);
            padding: 12px; font-size: 15px; font-weight: 600; width: 100%; transition: var(--transition);
            margin-top: 10px;
        }
        .btn-primary-action:hover { background: var(--primary-dark); }

        .btn-add-row {
            background: var(--primary); color: #fff; border: none; border-radius: var(--radius-sm);
            padding: 8px 14px; font-size: 13px; font-weight: 600; transition: var(--transition);
        }
        .btn-add-row:hover { background: var(--primary-dark); }

        .search-results {
            margin-top: 8px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            background: #fff;
            box-shadow: var(--shadow-xs);
            max-height: 220px;
            overflow: auto;
            display: none;
        }
        .search-result-item {
            padding: 8px 12px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 14px;
        }
        .search-result-item:hover { background: var(--gray-100); }
        .search-result-meta { color: var(--gray-500); font-size: 12px; }

        .shelf-container { display: grid; grid-template-columns: 1fr 300px; gap: 24px; }
        .logo-preview-card { border: 1px solid var(--gray-300); border-radius: var(--radius-sm); padding: 12px; }
        .logo-preview-card img { width: 100%; height: auto; border-radius: var(--radius-xs); }
        .btn-add-logo {
            background: var(--primary); color: #fff; border: none; padding: 8px 16px;
            font-size: 13px; font-weight: 600; border-radius: var(--radius-sm);
        }

        .item-list-wrap { margin-top: 18px; }
        .item-print-row {
            display: flex; justify-content: space-between; align-items: center; gap: 12px;
            padding: 10px 14px; border: 1px solid var(--gray-200); border-radius: var(--radius-sm);
            margin-bottom: 10px; font-size: 14px; color: var(--gray-800);
        }
        .item-print-row span { flex: 1; }
        .btn-remove-item {
            color: var(--primary); background: transparent; border: 1px solid var(--primary-soft);
            padding: 4px 12px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 600;
        }
        .muted { color: var(--gray-500); font-size: 13px; }
        .empty-state { border: 1px dashed var(--gray-200); border-radius: var(--radius-sm); padding: 16px; text-align: center; }

        @media (max-width: 900px) {
            .labels-card { max-width: 100%; }
            .shelf-container { grid-template-columns: 1fr; }
        }
</style>
@endpush

@section('content')
<div class="labels-wrap">
        <form class="labels-card" method="post" action="{{ route('labels.print') }}" target="_blank" enctype="multipart/form-data" id="labelsForm">
                @csrf
                <input type="hidden" name="mode" id="labelMode" value="barcode">

                <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="labels-tabs">
                                <button class="btn-tab active" id="btnBarcode" type="button">Barcode</button>
                                <button class="btn-tab" id="btnSheet" type="button">Sheet</button>
                        </div>
                        <button class="btn-add-logo" id="btnAddBackground" type="button" style="display: none;">
                                <i class="bi bi-plus-lg"></i> Upload Background
                        </button>
                        <input type="file" id="sheetBackgroundInput" name="sheet_background" accept="image/*" style="display: none;" />
                </div>

                <div id="barcodeContent">
                        <div class="form-group">
                        <label class="form-label" for="itemSearch">Item or Kit</label>
                        <input type="text" class="form-control" id="itemSearch" placeholder="Search items or kits...">
                        <div class="search-results" id="searchResults"></div>
                        <input type="hidden" id="selectedId">
                        <input type="hidden" id="selectedType">
                        <input type="hidden" id="selectedName">
                        <input type="hidden" id="selectedPrice">
                        </div>
                        <div class="form-group">
                                <label class="form-label" for="itemQty">Quantity</label>
                                <input type="number" class="form-control" id="itemQty" min="1" value="1">
                        </div>
                        <button class="btn-add-row" type="button" id="addItemBtn">Add Item</button>
                </div>

                <div id="sheetContent" style="display: none;">
                        <div class="shelf-container">
                                <div>
                                        <div class="form-group">
                                                <label class="form-label">Logo Width (mm)</label>
                                                <input type="number" name="logo_width_mm" class="form-control" step="0.01" min="5" max="200" placeholder="30">
                                        </div>
                                        <div class="form-group">
                                                <label class="form-label">Logo Height (mm)</label>
                                                <input type="number" name="logo_height_mm" class="form-control" step="0.01" min="5" max="200" placeholder="15">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Background Opacity (0 - 1)</label>
                                            <input type="number" name="sheet_opacity" class="form-control" step="0.05" min="0" max="1" placeholder="0.25">
                                        </div>
                                        <p class="muted">Upload a background image for sheet labels. This background will apply to each label.</p>
                                </div>
                                <div>
                                        <label class="form-label">Background Preview</label>
                                        <div class="logo-preview-card">
                                        <img
                                            src="{{ $sheetBackground ? route('app_files.view', ['fileId' => $sheetBackground]) : 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==' }}"
                                            alt="Sheet Background"
                                            id="backgroundPreview"
                                        >
                                        @unless($sheetBackground)
                                            <div class="muted" id="backgroundEmpty" style="margin-top: 8px; text-align: center;">No background uploaded.</div>
                                        @endunless
                                        </div>
                                </div>
                        </div>
                </div>

                <div class="item-list-wrap">
                        <label class="form-label">Items to print</label>
                        <div id="itemList"></div>
                        <div class="empty-state muted" id="emptyState">No items added yet.</div>
                </div>

                <div id="itemsFields"></div>

                <button class="btn-primary-action" type="submit">Generate Print View</button>
        </form>
</div>
@endsection

@push('scripts')
<script>
    const btnBarcode = document.getElementById('btnBarcode');
    const btnSheet = document.getElementById('btnSheet');
    const barcodeContent = document.getElementById('barcodeContent');
    const sheetContent = document.getElementById('sheetContent');
    const btnAddBackground = document.getElementById('btnAddBackground');
    const sheetBackgroundInput = document.getElementById('sheetBackgroundInput');
    const backgroundPreview = document.getElementById('backgroundPreview');
    const backgroundEmpty = document.getElementById('backgroundEmpty');
    const labelMode = document.getElementById('labelMode');
    const labelsCard = document.querySelector('.labels-card');

    const itemSearch = document.getElementById('itemSearch');
    const searchResults = document.getElementById('searchResults');
    const selectedId = document.getElementById('selectedId');
    const selectedType = document.getElementById('selectedType');
    const selectedName = document.getElementById('selectedName');
    const selectedPrice = document.getElementById('selectedPrice');
    const itemQty = document.getElementById('itemQty');
    const addItemBtn = document.getElementById('addItemBtn');
    const itemList = document.getElementById('itemList');
    const itemsFields = document.getElementById('itemsFields');
    const emptyState = document.getElementById('emptyState');
    const labelsForm = document.getElementById('labelsForm');

    const addedItems = [];
    let searchTimeout = null;
    let lastResults = [];

    const setMode = (mode) => {
        labelMode.value = mode;
        if (mode === 'barcode') {
            btnBarcode.classList.add('active');
            btnSheet.classList.remove('active');
            barcodeContent.style.display = 'block';
            sheetContent.style.display = 'none';
            btnAddBackground.style.display = 'none';
            labelsCard.style.maxWidth = '820px';
        } else {
            btnSheet.classList.add('active');
            btnBarcode.classList.remove('active');
            barcodeContent.style.display = 'block';
            sheetContent.style.display = 'block';
            btnAddBackground.style.display = 'block';
            labelsCard.style.maxWidth = '1000px';
        }
    };

    btnBarcode.addEventListener('click', () => setMode('barcode'));
    btnSheet.addEventListener('click', () => setMode('sheet'));

    btnAddBackground.addEventListener('click', () => {
        sheetBackgroundInput.click();
    });

    sheetBackgroundInput.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (!file) {
            return;
        }
        const reader = new FileReader();
        reader.onload = (e) => {
            backgroundPreview.src = e.target.result;
            if (backgroundEmpty) {
                backgroundEmpty.style.display = 'none';
            }
        };
        reader.readAsDataURL(file);
    });

    const renderItems = () => {
        itemList.innerHTML = '';
        itemsFields.innerHTML = '';

        if (addedItems.length === 0) {
            emptyState.style.display = 'block';
            return;
        }

        emptyState.style.display = 'none';
        addedItems.forEach((item, index) => {
            const row = document.createElement('div');
            row.className = 'item-print-row';
            row.innerHTML = `
                <span>${item.name} - $${item.price}</span>
                <input type="number" class="form-control item-qty-input" min="1" value="${item.quantity}" data-index="${index}" style="max-width: 90px;">
                <button type="button" class="btn-remove-item" data-index="${index}">Remove</button>
            `;
            itemList.appendChild(row);

            const typeInput = document.createElement('input');
            typeInput.type = 'hidden';
            typeInput.name = `items[${index}][type]`;
            typeInput.value = item.type || 'item';
            itemsFields.appendChild(typeInput);

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            if ((item.type || 'item') === 'kit') {
                idInput.name = `items[${index}][item_kit_id]`;
            } else {
                idInput.name = `items[${index}][item_id]`;
            }
            idInput.value = item.id;
            itemsFields.appendChild(idInput);

            const qtyInput = document.createElement('input');
            qtyInput.type = 'hidden';
            qtyInput.name = `items[${index}][quantity]`;
            qtyInput.value = item.quantity;
            itemsFields.appendChild(qtyInput);
        });
    };

    itemList.addEventListener('click', (event) => {
        const target = event.target;
        if (!target.classList.contains('btn-remove-item')) {
            return;
        }
        const index = Number(target.getAttribute('data-index'));
        if (Number.isNaN(index)) {
            return;
        }
        addedItems.splice(index, 1);
        renderItems();
    });

    itemList.addEventListener('change', (event) => {
        const target = event.target;
        if (!target.classList.contains('item-qty-input')) {
            return;
        }
        const index = Number(target.getAttribute('data-index'));
        const nextValue = Number(target.value || 1);
        if (Number.isNaN(index) || !addedItems[index]) {
            return;
        }
        addedItems[index].quantity = Math.max(1, nextValue);
        renderItems();
    });

    const selectSearchItem = (item) => {
        selectedId.value = item.id;
        selectedType.value = item.type;
        selectedName.value = item.label;
        selectedPrice.value = item.price;
        itemSearch.value = item.label;
        searchResults.style.display = 'none';
    };

    const renderSearchResults = (results) => {
        searchResults.innerHTML = '';
        if (results.length === 0) {
            searchResults.style.display = 'none';
            return;
        }

        results.forEach((result) => {
            const row = document.createElement('div');
            row.className = 'search-result-item';
            row.innerHTML = `
                <span>${result.label}</span>
                <span class="search-result-meta">$${result.price}</span>
            `;
            row.addEventListener('click', () => selectSearchItem(result));
            searchResults.appendChild(row);
        });

        searchResults.style.display = 'block';
    };

    itemSearch.addEventListener('input', () => {
        selectedId.value = '';
        selectedType.value = '';
        selectedName.value = '';
        selectedPrice.value = '';

        clearTimeout(searchTimeout);
        const term = itemSearch.value.trim();
        if (term.length < 2) {
            searchResults.style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(async () => {
            const response = await fetch(`{{ route('labels.search') }}?q=${encodeURIComponent(term)}`);
            if (!response.ok) {
                searchResults.style.display = 'none';
                return;
            }
            const results = await response.json();
            lastResults = results;
            renderSearchResults(results);
        }, 200);
    });

    itemSearch.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
            return;
        }
        event.preventDefault();
        if (selectedId.value !== '') {
            addItemBtn.click();
            return;
        }
        if (lastResults.length > 0) {
            selectSearchItem(lastResults[0]);
        }
    });

    addItemBtn.addEventListener('click', () => {
        const itemId = Number(selectedId.value || 0);
        const quantity = Number(itemQty.value || 0);
        const type = selectedType.value || 'item';
        const name = selectedName.value || itemSearch.value.trim();
        const price = selectedPrice.value || '0.00';

        if (!itemId || quantity < 1) {
            alert('Select an item or kit and quantity first.');
            return;
        }

        const existing = addedItems.find((entry) => entry.id === itemId && entry.type === type);
        if (existing) {
            existing.quantity += quantity;
        } else {
            addedItems.push({
                id: itemId,
                type: type,
                name: name,
                price: price,
                quantity: quantity,
            });
        }

        itemQty.value = 1;
        selectedId.value = '';
        selectedType.value = '';
        selectedName.value = '';
        selectedPrice.value = '';
        itemSearch.value = '';
        searchResults.style.display = 'none';
        renderItems();
    });

    labelsForm.addEventListener('submit', (event) => {
        if (addedItems.length === 0) {
            const itemId = Number(selectedId.value || 0);
            const quantity = Number(itemQty.value || 0);
            const type = selectedType.value || 'item';
            const name = selectedName.value || itemSearch.value.trim();
            const price = selectedPrice.value || '0.00';

            if (itemId && quantity > 0) {
                addedItems.push({
                    id: itemId,
                    type: type,
                    name: name || 'Item',
                    price: price,
                    quantity: quantity,
                });
                renderItems();
                return;
            }

            event.preventDefault();
            alert('Add at least one item before printing.');
        }
    });

    renderItems();
</script>
@endpush
