@extends('layouts.app')

@section('title', $kit ? 'Edit Item Kit' : 'New Item Kit')
@section('page-title', $kit ? 'Edit Item Kit' : 'New Item Kit')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/css/selectize.bootstrap5.min.css" />
@endpush

@section('content')
<div class="container-fluid">
    <form method="post" action="{{ $kit ? route('item-kits.update', $kit->id) : route('item-kits.store') }}" class="needs-validation" enctype="multipart/form-data">
        @csrf
        @if($kit)
            @method('put')
        @endif

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <ul class="nav nav-tabs" id="kitFormTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">General Info</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="items-tab" data-bs-toggle="tab" data-bs-target="#items" type="button" role="tab" aria-controls="items" aria-selected="false">Items & Kits</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pricing-tab" data-bs-toggle="tab" data-bs-target="#pricing" type="button" role="tab" aria-controls="pricing" aria-selected="false">Pricing & Taxes</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab" aria-controls="settings" aria-selected="false">Settings</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="custom-fields-tab" data-bs-toggle="tab" data-bs-target="#custom-fields" type="button" role="tab" aria-controls="custom-fields" aria-selected="false">Custom Fields</button>
                    </li>
                </ul>
            </div>
            
            <div class="card-body">
                <div class="tab-content" id="kitFormTabsContent">
                    
                    <!-- General Info Tab -->
                    <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="name">Kit Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $kit?->name) }}" required />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="item_kit_number">Kit Number</label>
                                <input type="text" class="form-control" id="item_kit_number" name="item_kit_number" value="{{ old('item_kit_number', $kit?->item_kit_number) }}" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="product_id">Product ID</label>
                                <input type="text" class="form-control" id="product_id" name="product_id" value="{{ old('product_id', $kit?->product_id) }}" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="category_id">Category</label>
                                <select class="form-select searchable-dropdown" id="category_id" name="category_id">
                                    <option value="">— Select Category —</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected(old('category_id', $kit?->category_id) == $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="supplier_id">Supplier</label>
                                <select class="form-select searchable-dropdown" id="supplier_id" name="supplier_id">
                                    <option value="">— Select Supplier —</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->person_id }}" @selected(old('supplier_id', $kit?->supplier_id) == $supplier->person_id)>{{ $supplier->company_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="tags">Tags (comma separated)</label>
                                <input type="text" class="form-control" id="tags" name="tags" value="{{ old('tags', $tags) }}" />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Secondary Suppliers</label>
                                <div id="secondary-suppliers-container">
                                    @if(isset($secondary_suppliers) && count($secondary_suppliers))
                                        @foreach($secondary_suppliers as $secSup)
                                            <div class="input-group mb-2 secondary-sup-row">
                                                <select class="form-select searchable-dropdown" name="secondary_suppliers[]">
                                                    <option value="">— Select Supplier —</option>
                                                    @foreach($suppliers as $supplier)
                                                        <option value="{{ $supplier->person_id }}" @selected($secSup->supplier_id == $supplier->person_id)>{{ $supplier->company_name }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="btn btn-outline-danger remove-row" type="button"><i class="bi bi-trash"></i></button>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-secondary-supplier"><i class="bi bi-plus"></i> Add Secondary Supplier</button>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="barcode_name">Barcode Name</label>
                                <input type="text" class="form-control" id="barcode_name" name="barcode_name" value="{{ old('barcode_name', $kit?->barcode_name) }}" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="image">Kit Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*" />
                                @if($kit && $kit->main_image_id)
                                    <div class="mt-2 text-muted small">Current image ID: {{ $kit->main_image_id }}</div>
                                @endif
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $kit?->description) }}</textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="info_popup">Info Popup</label>
                                <textarea class="form-control" id="info_popup" name="info_popup" rows="2">{{ old('info_popup', $kit?->info_popup) }}</textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Items & Kits Tab -->
                    <div class="tab-pane fade" id="items" role="tabpanel" aria-labelledby="items-tab">
                        <div class="row">
                            <div class="col-md-6 border-end">
                                <h6>Items in Kit</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm" id="kit-items-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Item</th>
                                                <th width="100">Qty</th>
                                                <th width="50"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($kitItems as $index => $kitItem)
                                                <tr>
                                                    <td>
                                                        <select class="form-select form-select-sm" name="kit_items[{{ $index }}][item_id]">
                                                            @foreach($allItems as $item)
                                                                <option value="{{ $item->item_id }}" @selected($kitItem->item_id == $item->item_id)>{{ $item->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td><input type="number" step="0.001" class="form-control form-control-sm" name="kit_items[{{ $index }}][quantity]" value="{{ $kitItem->quantity }}"></td>
                                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-kit-item"><i class="bi bi-plus"></i> Add Item</button>
                            </div>
                            
                            <div class="col-md-6">
                                <h6>Nested Item Kits</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm" id="nested-kits-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nested Kit</th>
                                                <th width="100">Qty</th>
                                                <th width="50"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($nestedKits as $index => $nested)
                                                <tr>
                                                    <td>
                                                        <select class="form-select form-select-sm" name="nested_kits[{{ $index }}][item_kit_id]">
                                                            @foreach($allKits as $ak)
                                                                <option value="{{ $ak->id }}" @selected($nested->item_kit_item_kit == $ak->id)>{{ $ak->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td><input type="number" step="0.001" class="form-control form-control-sm" name="nested_kits[{{ $index }}][quantity]" value="{{ $nested->quantity }}"></td>
                                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-nested-kit"><i class="bi bi-plus"></i> Add Nested Kit</button>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing & Taxes Tab -->
                    <div class="tab-pane fade" id="pricing" role="tabpanel" aria-labelledby="pricing-tab">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="unit_price">Unit Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.001" class="form-control" id="unit_price" name="unit_price" value="{{ old('unit_price', $kit?->unit_price) }}" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="cost_price">Cost Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.001" class="form-control" id="cost_price" name="cost_price" value="{{ old('cost_price', $kit?->cost_price) }}" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch mt-4 mt-md-5">
                                    <input class="form-check-input" type="checkbox" role="switch" id="tax_included" name="tax_included" value="1" @checked(old('tax_included', $kit?->tax_included))>
                                    <label class="form-check-label" for="tax_included">Prices Include Tax</label>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <hr>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="override_default_tax" name="override_default_tax" value="1" @checked(old('override_default_tax', $kit?->override_default_tax))>
                                    <label class="form-check-label" for="override_default_tax">Override Default Tax</label>
                                </div>
                            </div>
                            
                            <div id="tax_overrides_container" class="{{ old('override_default_tax', $kit?->override_default_tax) ? '' : 'd-none' }}">
                                <div class="row g-3">
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label" for="tax_class_id">Tax Class</label>
                                        <select class="form-select" id="tax_class_id" name="tax_class_id">
                                            <option value="">— None —</option>
                                            @foreach($taxClasses as $taxClass)
                                                <option value="{{ $taxClass->id }}" @selected(old('tax_class_id', $kit?->tax_class_id) == $taxClass->id)>{{ $taxClass->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <h6>Specific Tax Rates</h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm" id="taxes-table">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Tax Name</th>
                                                        <th>Percent (%)</th>
                                                        <th>Cumulative</th>
                                                        <th width="50"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($kit?->taxes ?? [] as $index => $tax)
                                                        <tr>
                                                            <td><input type="text" class="form-control" name="tax_names[]" value="{{ $tax->name }}"></td>
                                                            <td><input type="number" step="0.001" class="form-control" name="tax_percents[]" value="{{ $tax->percent }}"></td>
                                                            <td class="text-center">
                                                                <input type="checkbox" class="form-check-input" name="tax_cumulatives[]" value="1" @checked($tax->cumulative)>
                                                            </td>
                                                            <td class="text-center"><button class="btn btn-sm btn-outline-danger remove-row" type="button"><i class="bi bi-trash"></i></button></td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-tax-row"><i class="bi bi-plus"></i> Add Tax Rate</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Settings Tab -->
                    <div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_ebt_item" id="is_ebt_item" value="1" @checked(old('is_ebt_item', $kit?->is_ebt_item))>
                                    <label class="form-check-label" for="is_ebt_item">EBT Item</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="verify_age" id="verify_age" value="1" @checked(old('verify_age', $kit?->verify_age))>
                                    <label class="form-check-label" for="verify_age">Verify Age</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small" for="required_age">Required Age</label>
                                <input type="number" class="form-control form-control-sm" name="required_age" id="required_age" value="{{ old('required_age', $kit?->required_age) }}" {{ old('verify_age', $kit?->verify_age) ? '' : 'disabled' }}>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="item_kit_inactive" id="item_kit_inactive" value="1" @checked(old('item_kit_inactive', $kit?->item_kit_inactive))>
                                    <label class="form-check-label" for="item_kit_inactive">Inactive</label>
                                </div>
                            </div>
                            
                            <div class="col-md-12"><hr></div>
                            
                            <div class="col-md-4">
                                <label class="form-label" for="commission_percent">Commission %</label>
                                <input type="number" step="0.01" class="form-control" name="commission_percent" id="commission_percent" value="{{ old('commission_percent', $kit?->commission_percent) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="commission_percent_type">Commission Type</label>
                                <select class="form-select" name="commission_percent_type">
                                    <option value="profit" @selected(old('commission_percent_type', $kit?->commission_percent_type) == 'profit')>Profit</option>
                                    <option value="selling_price" @selected(old('commission_percent_type', $kit?->commission_percent_type) == 'selling_price')>Selling Price</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="commission_fixed">Fixed Commission</label>
                                <input type="number" step="0.01" class="form-control" name="commission_fixed" id="commission_fixed" value="{{ old('commission_fixed', $kit?->commission_fixed) }}">
                            </div>
                            
                            <div class="col-md-12"><hr></div>
                            
                            <div class="col-md-4">
                                <label class="form-label" for="max_discount_percent">Max Discount %</label>
                                <input type="number" step="0.01" class="form-control" name="max_discount_percent" id="max_discount_percent" value="{{ old('max_discount_percent', $kit?->max_discount_percent) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="min_edit_price">Min Edit Price</label>
                                <input type="number" step="0.01" class="form-control" name="min_edit_price" id="min_edit_price" value="{{ old('min_edit_price', $kit?->min_edit_price) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="max_edit_price">Max Edit Price</label>
                                <input type="number" step="0.01" class="form-control" name="max_edit_price" id="max_edit_price" value="{{ old('max_edit_price', $kit?->max_edit_price) }}">
                            </div>
                            
                            <div class="col-md-12"><hr></div>
                            
                            <div class="col-md-4">
                                <label class="form-label" for="default_quantity">Default Quantity</label>
                                <input type="number" step="0.001" class="form-control" name="default_quantity" id="default_quantity" value="{{ old('default_quantity', $kit?->default_quantity) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="reorder_level">Threshold</label>
                                <input type="number" step="0.001" class="form-control" name="reorder_level" id="reorder_level" value="{{ old('reorder_level', $kit?->reorder_level) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="loyalty_multiplier">Loyalty Multiplier</label>
                                <input type="number" step="0.01" class="form-control" name="loyalty_multiplier" id="loyalty_multiplier" value="{{ old('loyalty_multiplier', $kit?->loyalty_multiplier) }}">
                            </div>
                            <div class="col-md-4 pt-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_favorite" id="is_favorite" value="1" @checked(old('is_favorite', $kit?->is_favorite))>
                                    <label class="form-check-label" for="is_favorite">Favorite</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Custom Fields Tab -->
                    <div class="tab-pane fade" id="custom-fields" role="tabpanel" aria-labelledby="custom-fields-tab">
                        <div class="row g-3">
                            @for($i = 1; $i <= 10; $i++)
                                <div class="col-md-6">
                                    <label class="form-label" for="custom_field_{{ $i }}_value">Custom Field {{ $i }}</label>
                                    <input type="text" class="form-control" id="custom_field_{{ $i }}_value" name="custom_field_{{ $i }}_value" value="{{ old("custom_field_{$i}_value", $kit?->{"custom_field_{$i}_value"}) }}">
                                </div>
                            @endfor
                        </div>
                    </div>
                    
                </div>
            </div>
            <div class="card-footer bg-white border-top text-end py-3">
                <a class="btn btn-outline-secondary me-2" href="{{ route('item-kits.index') }}">Cancel</a>
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save"></i> Save Item Kit</button>
            </div>
        </div>
    </form>
</div>

{{-- Templates for dynamic rows --}}
<template id="kit-item-row">
    <tr>
        <td>
            <select class="form-select form-select-sm searchable-dropdown" name="kit_items[INDEX][item_id]">
                <option value="">Select Item</option>
                @foreach($allItems as $item)
                    <option value="{{ $item->item_id }}">{{ $item->name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.001" class="form-control form-control-sm" name="kit_items[INDEX][quantity]" value="1"></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button></td>
    </tr>
</template>

<template id="nested-kit-row">
    <tr>
        <td>
            <select class="form-select form-select-sm searchable-dropdown" name="nested_kits[INDEX][item_kit_id]">
                <option value="">Select Kit</option>
                @foreach($allKits as $ak)
                    <option value="{{ $ak->id }}">{{ $ak->name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.001" class="form-control form-control-sm" name="nested_kits[INDEX][quantity]" value="1"></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button></td>
    </tr>
</template>

<template id="tax-row">
    <tr>
        <td><input type="text" class="form-control" name="tax_names[]" value=""></td>
        <td><input type="number" step="0.001" class="form-control" name="tax_percents[]" value=""></td>
        <td class="text-center"><input type="checkbox" class="form-check-input" name="tax_cumulatives[]" value="1"></td>
        <td class="text-center"><button class="btn btn-sm btn-outline-danger remove-row" type="button"><i class="bi bi-trash"></i></button></td>
    </tr>
</template>

<template id="secondary-supplier-row">
    <div class="input-group mb-2 secondary-sup-row">
        <select class="form-select searchable-dropdown" name="secondary_suppliers[]">
            <option value="">— Select Supplier —</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->person_id }}">{{ $supplier->company_name }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline-danger remove-row" type="button"><i class="bi bi-trash"></i></button>
    </div>
</template>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/js/selectize.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let kitItemIndex = {{ count($kitItems) }};
    let nestedKitIndex = {{ count($nestedKits) }};

    function initSelectize(selector) {
        if (!selector) {
            return;
        }
        $(selector).selectize({
            create: false,
            sortField: 'text',
            placeholder: $(selector).find('option:first').text() || 'Select an option'
        });
    }

    document.querySelectorAll('.searchable-dropdown').forEach((select) => {
        initSelectize(select);
    });

    // Toggle containers
    const overrideCheckbox = document.getElementById('override_default_tax');
    const taxContainer = document.getElementById('tax_overrides_container');
    if (overrideCheckbox) {
        overrideCheckbox.addEventListener('change', function() {
            taxContainer.classList.toggle('d-none', !this.checked);
        });
    }

    const verifyAgeCheckbox = document.getElementById('verify_age');
    const ageInput = document.getElementById('required_age');
    if (verifyAgeCheckbox) {
        verifyAgeCheckbox.addEventListener('change', function() {
            ageInput.disabled = !this.checked;
        });
    }

    // Add rows
    document.getElementById('add-kit-item')?.addEventListener('click', function() {
        const row = addRow('kit-items-table', 'kit-item-row', kitItemIndex++);
        initSelectize(row.querySelector('select'));
    });

    document.getElementById('add-nested-kit')?.addEventListener('click', function() {
        const row = addRow('nested-kits-table', 'nested-kit-row', nestedKitIndex++);
        initSelectize(row.querySelector('select'));
    });

    document.getElementById('add-tax-row')?.addEventListener('click', function() {
        addRow('taxes-table', 'tax-row', null);
    });

    document.getElementById('add-secondary-supplier')?.addEventListener('click', function() {
        const container = document.getElementById('secondary-suppliers-container');
        const template = document.getElementById('secondary-supplier-row').innerHTML;
        container.insertAdjacentHTML('beforeend', template);
        const row = container.lastElementChild;
        if (row) {
            initSelectize(row.querySelector('select'));
        }
    });

    function addRow(tableId, templateId, index) {
        const tbody = document.querySelector(`#${tableId} tbody`);
        const template = document.getElementById(templateId).innerHTML;
        const html = index !== null ? template.replace(/INDEX/g, index) : template;
        tbody.insertAdjacentHTML('beforeend', html);
        return tbody.lastElementChild;
    }

    // Remove rows
    document.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.remove-row');
        if (removeBtn) {
            const row = removeBtn.closest('.input-group') || removeBtn.closest('tr');
            if (row) {
                const select = row.querySelector('select');
                if (select && select.selectize) {
                    select.selectize.destroy();
                }
                row.remove();
            }
        }
    });
});
</script>
@endpush
