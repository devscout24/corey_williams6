@extends('layouts.app')

@section('title', $item ? 'Edit Item' : 'New Item')
@section('page-title', $item ? 'Edit Item' : 'New Item')
@section('page-description', $item ? "Editing $item->name" : 'Create a new inventory item')

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/css/selectize.default.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .nav-tabs .nav-link {
            color: #495057;
        }

        .nav-tabs .nav-link.active {
            color: #0d6efd !important;
        }

        .attr-assignment {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
        }

        .attr-assignment .form-check {
            margin-right: 0.5rem;
            margin-bottom: 0.15rem;
        }

        .attr-assignment .form-check-label {
            font-size: 0.85rem;
        }

        .selectize-control.multi .selectize-input {
            min-height: 38px;
            padding: 4px 8px;
        }

        .selectize-control.multi .selectize-input>div {
            font-size: 0.8rem;
            padding: 2px 6px;
        }

        .attr-select-dropdown .selectize-input {
            font-size: 0.85rem;
            min-height: 32px;
            padding: 2px 8px;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <form method="post" action="{{ $item ? route('items.update', $item->item_id) : route('items.store') }}"
            class="needs-validation" enctype="multipart/form-data">
            @csrf
            @if($item)
                @method('put')
            @endif

            <input type="hidden" name="redirect" value="{{ request('redirect', '') }}">
            <input type="hidden" name="progression" value="{{ request('progression', 0) }}">
            <input type="hidden" name="quick_edit" value="{{ request('quick_edit', 0) }}">

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <ul class="nav nav-tabs" id="itemFormTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic"
                                type="button" role="tab" aria-controls="basic" aria-selected="true">Basic Info</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="dimensions-tab" data-bs-toggle="tab" data-bs-target="#dimensions"
                                type="button" role="tab" aria-controls="dimensions"
                                aria-selected="false">Dimensions</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings"
                                type="button" role="tab" aria-controls="settings" aria-selected="false">Settings</button>
                        </li>
                        {{-- <li class="nav-item" role="presentation">
                            <button class="nav-link" id="variations-tab" data-bs-toggle="tab" data-bs-target="#variations"
                                type="button" role="tab" aria-controls="variations" aria-selected="false">Variants</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced"
                                type="button" role="tab" aria-controls="advanced" aria-selected="false">Advanced</button>
                        </li> --}}
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="custom-fields-tab" data-bs-toggle="tab"
                                data-bs-target="#custom-fields" type="button" role="tab" aria-controls="custom-fields"
                                aria-selected="false">Custom Fields</button>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content" id="itemFormTabsContent">

                        <!-- Basic Info Tab -->
                        <div class="tab-pane fade show active" id="basic" role="tabpanel" aria-labelledby="basic-tab">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="name">Item Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="{{ old('name', $item?->name) }}" required />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="barcode_name">Barcode Name</label>
                                    <input type="text" class="form-control" id="barcode_name" name="barcode_name"
                                        value="{{ old('barcode_name', $item?->barcode_name) }}" />
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="category_id">Category <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="category_id" name="category_id">
                                        <option value="">— Select Category —</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" @selected(old('category_id', $item?->category_id) == $category->id)>{!! $category->label !!}</option>
                                        @endforeach
                                    </select>
                                    @if(auth()->user()->hasModulePermission('items', 'manage_categories'))
                                        <div class="mt-1">
                                            <a href="javascript:void(0);" id="add_category" data-bs-toggle="modal"
                                                data-bs-target="#addCategoryModal">+ Add Category</a>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="supplier_id">Supplier</label>
                                    <select class="form-select" id="supplier_id" name="supplier_id">
                                        <option value="">— Select Supplier —</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->person_id }}" @selected(old('supplier_id', $item?->supplier_id) == $supplier->person_id)>{{ $supplier->company_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="tax_class_id">Tax Class</label>
                                    <select class="form-select" id="tax_class_id" name="tax_class_id">
                                        <option value="">Use Store
                                            Default{{ $defaultTaxClass ? ' (' . $defaultTaxClass->name . ')' : '' }}
                                        </option>
                                        @foreach($taxClasses as $taxClass)
                                            <option value="{{ $taxClass->id }}" @selected(old('tax_class_id', $item?->tax_class_id) == $taxClass->id)>{{ $taxClass->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Secondary Categories -->
                                <div class="col-md-12">
                                    <label class="form-label">Secondary Categories</label>
                                    <div id="secondary-categories-container">
                                        @if(isset($secondary_categories) && count($secondary_categories))
                                            @foreach($secondary_categories as $secCat)
                                                <div class="input-group mb-2 secondary-cat-row">
                                                    <select class="form-select" name="secondary_categories[]">
                                                        <option value="">— Select Category —</option>
                                                        @foreach($categories as $category)
                                                            <option value="{{ $category->id }}"
                                                                @selected($secCat->category_id == $category->id)>{!! $category->label !!}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button class="btn btn-outline-danger remove-row" type="button"><i
                                                            class="bi bi-trash"></i></button>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2"
                                        id="add-secondary-category"><i class="bi bi-plus"></i> Add Secondary
                                        Category</button>
                                </div>

                                <!-- Secondary Suppliers -->
                                <div class="col-md-12">
                                    <label class="form-label">Secondary Suppliers</label>
                                    <div id="secondary-suppliers-container">
                                        @if(isset($secondary_suppliers) && count($secondary_suppliers))
                                            @foreach($secondary_suppliers as $secSup)
                                                <div class="input-group mb-2 secondary-sup-row">
                                                    <select class="form-select" name="secondary_suppliers[]">
                                                        <option value="">— Select Supplier —</option>
                                                        @foreach($suppliers as $supplier)
                                                            <option value="{{ $supplier->person_id }}"
                                                                @selected($secSup->supplier_id == $supplier->person_id)>
                                                                {{ $supplier->company_name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button class="btn btn-outline-danger remove-row" type="button"><i
                                                            class="bi bi-trash"></i></button>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2"
                                        id="add-secondary-supplier"><i class="bi bi-plus"></i> Add Secondary
                                        Supplier</button>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="item_number">Item Number (SKU) <span
                                            class="text-danger">*</span></label>
                                    <input type="text" required class="form-control" id="item_number" name="item_number"
                                        value="{{ old('item_number', $item?->item_number) }}" />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="product_id">Product ID</label>
                                    <input type="text" class="form-control" id="product_id" name="product_id"
                                        value="{{ old('product_id', $item?->product_id) }}" />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="tags">Tags (Comma Separated)</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="tags" name="tags"
                                            value="{{ old('tags', $tags) }}" />
                                        @if(auth()->user()->hasModulePermission('items', 'manage_tags'))
                                            <a href="{{ route('tags.index') }}" class="btn btn-primary">Manage Tags</a>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="size">Size</label>
                                    <input type="text" class="form-control" id="size" name="size"
                                        value="{{ old('size', $item?->size) }}" />
                                </div>

                                @if(!$hideItemImageUpload)
                                <div class="col-md-12">
                                    <label class="form-label" for="item_images">Item Images</label>
                                    <input type="file" class="form-control" id="item_images" name="images[]"
                                        accept="image/png,image/jpeg,image/gif" multiple />
                                    <div class="form-text">Upload one or more images (JPG, PNG, GIF).</div>

                                    @if(!empty($item_files))
                                        <div class="mt-2 d-flex flex-wrap gap-2">
                                            @foreach($item_files as $file)
                                                <a href="{{ route('app_files.view', $file->file_id) }}" target="_blank"
                                                    class="d-inline-flex align-items-center justify-content-center border rounded"
                                                    style="width: 72px; height: 72px; overflow: hidden; background: #f8fafc;">
                                                    <img src="{{ route('app_files.view', $file->file_id) }}"
                                                        alt="{{ $file->file_name }}" style="max-width: 100%; max-height: 100%;" />
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                @endif

                                <div class="col-md-6">
                                    <label class="form-label" for="cost_price">Cost Price</label>
                                    <div class="input-group">
                                        <span class="input-group-text">{{ $baseCurrencySymbol }}</span>
                                        <input type="number" step="{{ 10 ** -$baseDecimals }}" class="form-control" id="cost_price"
                                            name="cost_price" value="{{ old('cost_price', $item?->cost_price) }}" />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="markup_type">Markup Type</label>
                                    <select class="form-control" id="markup_type" name="markup_type">
                                        <option value="percentage" {{ old('markup_type', $item?->markup_type) == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                        <option value="flat" {{ old('markup_type', $item?->markup_type) == 'flat' ? 'selected' : '' }}>Flat</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="markup">Markup</label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="markup-symbol">{{ $baseCurrencySymbol }}</span>
                                        <input type="number" step="{{ 10 ** -$baseDecimals }}" class="form-control" id="markup" name="markup"
                                            value="{{ old('markup', $item?->markup) }}" />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="unit_price">Unit Price</label>
                                    <div class="input-group">
                                        <span class="input-group-text">{{ $baseCurrencySymbol }}</span>
                                        <input type="number" step="{{ 10 ** -$baseDecimals }}" class="form-control" id="unit_price"
                                            name="unit_price" value="{{ old('unit_price', $item?->unit_price) }}" />
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="default_quantity">Default Quantity</label>
                                    <input type="number" step="1" class="form-control" id="default_quantity"
                                        name="default_quantity"
                                        value="{{ old('default_quantity', $item?->default_quantity) }}" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="reorder_level">Threshold</label>
                                    <input type="number" step="any" class="form-control" id="reorder_level"
                                        name="reorder_level" value="{{ old('reorder_level', $item?->reorder_level) }}" />
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label" for="description">Short Description</label>
                                    <textarea class="form-control" id="description" name="description"
                                        rows="2">{{ old('description', $item?->description) }}</textarea>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label" for="long_description">Long Description</label>
                                    <textarea class="form-control" id="long_description" name="long_description"
                                        rows="4">{{ old('long_description', $item?->long_description) }}</textarea>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label" for="info_popup">Info Popup Notes</label>
                                    <textarea class="form-control" id="info_popup" name="info_popup"
                                        rows="2">{{ old('info_popup', $item?->info_popup) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Dimensions Tab -->
                        <div class="tab-pane fade" id="dimensions" role="tabpanel" aria-labelledby="dimensions-tab">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="weight">Weight</label>
                                    <input type="number" step="0.01" class="form-control" id="weight" name="weight"
                                        value="{{ old('weight', $item?->weight) }}" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="weight_unit">Weight Unit</label>
                                    <select class="form-select" id="weight_unit" name="weight_unit">
                                        <option value="">— None —</option>
                                        @foreach(['lb' => 'lb', 'oz' => 'oz', 'kg' => 'kg', 'g' => 'g', 'l' => 'l', 'ml' => 'ml', 'cf' => 'cf'] as $unit => $label)
                                            <option value="{{ $unit }}" @selected(old('weight_unit', $item?->weight_unit) === $unit)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="length">Length</label>
                                    <input type="number" step="0.01" class="form-control" id="length" name="length"
                                        placeholder="Length" value="{{ old('length', $item?->length) }}" />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="width">Width</label>
                                    <input type="number" step="0.01" class="form-control" id="width" name="width"
                                        placeholder="Width" value="{{ old('width', $item?->width) }}" />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="height">Height</label>
                                    <input type="number" step="0.01" class="form-control" id="height" name="height"
                                        placeholder="Height" value="{{ old('height', $item?->height) }}" />
                                </div>


                            </div>
                        </div>

                        <!-- Settings Tab -->
                        <div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" role="switch" id="item_inactive"
                                            name="item_inactive" value="1" @checked(old('item_inactive', $item?->item_inactive))>
                                        <label class="form-check-label" for="item_inactive">Item Inactive</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" role="switch" id="is_service"
                                            name="is_service" value="1" @checked(old('is_service', $item?->is_service))>
                                        <label class="form-check-label" for="is_service">Is Service (Non-Inventory)</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" role="switch" id="tax_included"
                                            name="tax_included" value="1" @checked(true)>
                                        <label class="form-check-label" for="tax_included">Prices Include Tax</label>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" role="switch" id="is_barcoded"
                                            name="is_barcoded" value="1" @checked(old('is_barcoded', $item?->item_id ? $item->is_barcoded : true))>
                                        <label class="form-check-label" for="is_barcoded">Is Barcoded</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" role="switch" id="is_favorite"
                                            name="is_favorite" value="1" @checked(old('is_favorite', $item?->is_favorite))>
                                        <label class="form-check-label" for="is_favorite">Is Favorite</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                            id="allow_alt_description" name="allow_alt_description" value="1"
                                            @checked(old('allow_alt_description', $item?->allow_alt_description))>
                                        <label class="form-check-label" for="allow_alt_description">Allow Alt
                                            Description</label>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" role="switch" id="is_ecommerce"
                                            name="is_ecommerce" value="1" @checked(old('is_ecommerce', $item?->is_ecommerce))>
                                        <label class="form-check-label" for="is_ecommerce">Is Ecommerce Item</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" role="switch" id="is_ebt_item"
                                            name="is_ebt_item" value="1" @checked(old('is_ebt_item', $item?->is_ebt_item))>
                                        <label class="form-check-label" for="is_ebt_item">Is EBT Item</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" role="switch" id="is_serialized"
                                            name="is_serialized" value="1" @checked(old('is_serialized', $item?->is_serialized))>
                                        <label class="form-check-label" for="is_serialized">Is Serialized</label>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" role="switch" id="is_serialized"
                                            name="discountable" value="1" @checked(old('discountable', $item?->discountable))>
                                        <label class="form-check-label" for="discountable">Is Discountable</label>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <div class="col-md-4">
                                    <div class="form-check form-switch mt-4 mb-2">
                                        <input class="form-check-input" type="checkbox" role="switch" id="verify_age"
                                            name="verify_age" value="1" @checked(old('verify_age', $item?->verify_age))>
                                        <label class="form-check-label" for="verify_age">Requires Age Verification</label>
                                    </div>
                                    <label class="form-label text-muted small" for="required_age">Required Age</label>
                                    <input type="number" class="form-control" id="required_age" name="required_age"
                                        value="{{ old('required_age', $item?->required_age) }}" />
                                </div>

                                <div class="col-md-4">
                                    <div class="form-check form-switch mt-4 mb-2">
                                        <input class="form-check-input" type="checkbox" role="switch" id="is_series_package"
                                            name="is_series_package" value="1" @checked(old('is_series_package', $item?->is_series_package))>
                                        <label class="form-check-label" for="is_series_package">Sold in a series</label>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label text-muted small" for="series_quantity">Series
                                                Qty</label>
                                            <input type="number" class="form-control" id="series_quantity"
                                                name="series_quantity"
                                                value="{{ old('series_quantity', $item?->series_quantity) }}" />
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label text-muted small" for="series_days_to_use_within">Days
                                                to use</label>
                                            <input type="number" class="form-control" id="series_days_to_use_within"
                                                name="series_days_to_use_within"
                                                value="{{ old('series_days_to_use_within', $item?->series_days_to_use_within) }}" />
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-check form-switch mt-4 mb-2">
                                        <input class="form-check-input" type="checkbox" role="switch" id="disable_loyalty"
                                            name="disable_loyalty" value="1" @checked(old('disable_loyalty', $item?->disable_loyalty))>
                                        <label class="form-check-label" for="disable_loyalty">Disable Loyalty</label>
                                    </div>
                                    <label class="form-label text-muted small" for="loyalty_multiplier">Loyalty
                                        Multiplier</label>
                                    <input type="number" step="0.01" class="form-control" id="loyalty_multiplier"
                                        name="loyalty_multiplier"
                                        value="{{ old('loyalty_multiplier', $item?->loyalty_multiplier) }}" />
                                </div>

                            </div>
                        </div>

                        {{-- <!-- Variants Tab -->
                        <div class="tab-pane fade" id="variations" role="tabpanel" aria-labelledby="variations-tab">
                            <div class="row g-4">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">Item Variants</h5>
                                        <div>
                                            <a href="{{ route('attributes.index') }}"
                                                class="btn btn-outline-secondary btn-sm me-2" target="_blank">
                                                <i class="bi bi-gear"></i> Manage Attributes
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-variation">
                                                <i class="bi bi-plus"></i> Add Variation
                                            </button>
                                        </div>
                                    </div>

                                    @if(count($attributes) === 0)
                                    <div class="alert alert-info">
                                        No attributes defined.
                                        <a href="{{ route('attributes.index') }}" target="_blank">Create attributes</a>
                                        first (e.g., Color, Size) to set up variants.
                                    </div>
                                    @endif

                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm align-middle" id="variations-table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="min-width:140px">Name</th>
                                                    <th style="min-width:110px">SKU</th>
                                                    <th style="min-width:220px">Attribute Values</th>
                                                    <th style="min-width:90px">Cost</th>
                                                    <th style="min-width:80px">Markup Type</th>
                                                    <th style="min-width:80px">Markup</th>
                                                    <th style="min-width:90px">Unit Price</th>
                                                    <th style="min-width:140px">Suppliers</th>
                                                    <th style="width:50px">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="variations-tbody">
                                                @php $varIndex = 0; @endphp
                                                @foreach(old('variations', $variations) as $varIndex => $variation)
                                                @php
                                                $vName = is_array($variation) ? ($variation['name'] ?? '') :
                                                ($variation->name ?? '');
                                                $vSku = is_array($variation) ? ($variation['item_number'] ?? '') :
                                                ($variation->item_number ?? '');
                                                $vCost = is_array($variation) ? ($variation['cost_price'] ?? '') :
                                                ($variation->cost_price ?? '');
                                                $vMarkup = is_array($variation) ? ($variation['markup'] ?? '') :
                                                ($variation->markup ?? '');
                                                $vMarkupType = is_array($variation) ? ($variation['markup_type'] ?? 'flat')
                                                : ($variation->markup_type ?? 'flat');
                                                $vPrice = is_array($variation) ? ($variation['unit_price'] ?? '') :
                                                ($variation->unit_price ?? '');
                                                $vId = is_array($variation) ? ($variation['id'] ?? '') : ($variation->id ??
                                                '');
                                                $avIds = is_array($variation) ? ($variation['attribute_value_ids'] ?? []) :
                                                ($variation->attributeValues->pluck('id')->toArray() ?? []);
                                                $supIds = is_array($variation) ? ($variation['supplier_ids'] ?? []) :
                                                ($variation->suppliers->pluck('person_id')->toArray() ?? []);
                                                @endphp
                                                @include('items._variation_row', [
                                                'varIndex' => $varIndex,
                                                'vId' => $vId,
                                                'vName' => $vName,
                                                'vSku' => $vSku,
                                                'vCost' => $vCost,
                                                'vMarkup' => $vMarkup,
                                                'vMarkupType' => $vMarkupType,
                                                'vPrice' => $vPrice,
                                                'avIds' => $avIds,
                                                'supIds' => $supIds,
                                                'attributes' => $attributes,
                                                'suppliers' => $suppliers,
                                                'baseCurrencySymbol' => $baseCurrencySymbol,
                                                ])
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Advanced Tab -->
                        <div class="tab-pane fade" id="advanced" role="tabpanel" aria-labelledby="advanced-tab">
                            <div class="row g-4">
                                <!-- Additional Item Numbers -->
                                <div class="col-md-12">
                                    <h5 class="mb-3">Additional Item Numbers</h5>
                                    <div id="additional-numbers-container">
                                        @if(old('additional_item_numbers', $additional_item_numbers))
                                        @foreach(old('additional_item_numbers', $additional_item_numbers) as $addNum)
                                        <div class="input-group mb-2 additional-number-row">
                                            <input type="text" class="form-control" name="additional_item_numbers[]"
                                                value="{{ $addNum }}">
                                            <button class="btn btn-outline-danger remove-row" type="button"><i
                                                    class="bi bi-trash"></i></button>
                                        </div>
                                        @endforeach
                                        @endif
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2"
                                        id="add-additional-number"><i class="bi bi-plus"></i> Add Item Number</button>
                                </div>
                            </div>
                        </div> --}}

                        <!-- Custom Fields Tab -->
                        <div class="tab-pane fade" id="custom-fields" role="tabpanel" aria-labelledby="custom-fields-tab">
                            <div class="row g-3">
                                @for($i = 1; $i <= 10; $i++)
                                    @php
                                        $fieldName = "Custom Field $i";
                                        $fieldType = 'text';
                                        $required = false;
                                        $choices = [];
                                    @endphp

                                    <div class="col-md-6">
                                        <label class="form-label" for="custom_field_{{ $i }}_value">
                                            {{ $fieldName }} @if($required)<span class="text-danger">*</span>@endif
                                        </label>

                                        @if($fieldType == 'checkbox')
                                            <div class="form-check form-switch mt-2">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    id="custom_field_{{ $i }}_value" name="custom_field_{{ $i }}_value" value="1"
                                                    @checked(old("custom_field_{$i}_value", $item?->{"custom_field_{$i}_value"}))>
                                                <label class="form-check-label"
                                                    for="custom_field_{{ $i }}_value">{{ $fieldName }}</label>
                                            </div>
                                        @elseif($fieldType == 'date')
                                            <input type="date" class="form-control" id="custom_field_{{ $i }}_value"
                                                name="custom_field_{{ $i }}_value"
                                                value="{{ old("custom_field_{$i}_value", $item?->{"custom_field_{$i}_value"} ? date('Y-m-d', $item->{"custom_field_{$i}_value"}) : '') }}">
                                        @elseif($fieldType == 'dropdown')
                                            <select class="form-select" id="custom_field_{{ $i }}_value"
                                                name="custom_field_{{ $i }}_value">
                                                <option value="">— Select —</option>
                                                @foreach($choices as $choice)
                                                    <option value="{{ $choice }}" @selected(old("custom_field_{$i}_value", $item?->{"custom_field_{$i}_value"}) == $choice)>{{ $choice }}</option>
                                                @endforeach
                                            </select>
                                        @elseif($fieldType == 'image')
                                            <input type="file" class="form-control" id="custom_field_{{ $i }}_value"
                                                name="custom_field_{{ $i }}_value" accept=".png,.jpg,.jpeg,.gif">
                                            @if($item && $item->{"custom_field_{$i}_value"})
                                                <img width="100" src="{{ asset('storage/' . $item->{"custom_field_{$i}_value"}) }}"
                                                    class="mt-2">
                                            @endif
                                        @elseif($fieldType == 'file')
                                            <input type="file" class="form-control" id="custom_field_{{ $i }}_value"
                                                name="custom_field_{{ $i }}_value">
                                            @if($item && $item->{"custom_field_{$i}_value"})
                                                <a href="{{ asset('storage/' . $item->{"custom_field_{$i}_value"}) }}"
                                                    target="_blank">Download File</a>
                                            @endif
                                        @else
                                            <input type="text" class="form-control" id="custom_field_{{ $i }}_value"
                                                name="custom_field_{{ $i }}_value"
                                                value="{{ old("custom_field_{$i}_value", $item?->{"custom_field_{$i}_value"}) }}">
                                        @endif
                                    </div>
                                @endfor
                            </div>
                        </div>

                    </div>
                </div>
                <div class="card-footer bg-white border-top text-end py-3">
                    <a class="btn btn-outline-secondary me-2" href="{{ route('items.index') }}">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save"></i> Save Item</button>
                </div>
            </div>
        </form>
    </div>

    @if(auth()->user()->hasModulePermission('items', 'manage_categories'))
        <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="post" action="{{ route('categories.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Add Category</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="category_parent_id">Parent Category</label>
                                <select class="form-select" id="category_parent_id" name="parent_id">
                                    <option value="">— None —</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{!! $category->label !!}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="category_name">Category Name</label>
                                <input type="text" class="form-control" id="category_name" name="name" required />
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    {{-- Templates for dynamic rows --}}
    <template id="secondary-category-row">
        <div class="input-group mb-2 secondary-cat-row">
            <select class="form-select" name="secondary_categories[]">
                <option value="">— Select Category —</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{!! $category->label !!}</option>
                @endforeach
            </select>
            <button class="btn btn-outline-danger remove-row" type="button"><i class="bi bi-trash"></i></button>
        </div>
    </template>

    <template id="secondary-supplier-row">
        <div class="input-group mb-2 secondary-sup-row">
            <select class="form-select" name="secondary_suppliers[]">
                <option value="">— Select Supplier —</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->person_id }}">{{ $supplier->company_name }}</option>
                @endforeach
            </select>
            <button class="btn btn-outline-danger remove-row" type="button"><i class="bi bi-trash"></i></button>
        </div>
    </template>

    <template id="additional-number-row">
        <div class="input-group mb-2 additional-number-row">
            <input type="text" class="form-control" name="additional_item_numbers[]" value="">
            <button class="btn btn-outline-danger remove-row" type="button"><i class="bi bi-trash"></i></button>
        </div>
    </template>

    <template id="variation-row-template">
        <tr class="variation-row" data-index="__INDEX__">
            <td>
                <input type="hidden" name="variations[__INDEX__][id]" value="">
                <input type="text" class="form-control form-control-sm" name="variations[__INDEX__][name]" value="">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="variations[__INDEX__][item_number]" value="">
            </td>
            <td>
                <div class="attr-assignments" data-index="__INDEX__">
                    <button type="button" class="btn btn-sm btn-outline-primary add-attr-assignment">
                        <i class="bi bi-plus"></i> Attribute
                    </button>
                </div>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">{{ $baseCurrencySymbol }}</span>
                    <input type="number" step="0.001" class="form-control variation-cost"
                        name="variations[__INDEX__][cost_price]" value="">
                </div>
            </td>
            <td>
                <select class="form-select form-select-sm" name="variations[__INDEX__][markup_type]">
                    <option value="flat">Flat</option>
                    <option value="percentage">%</option>
                </select>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">{{ $baseCurrencySymbol }}</span>
                    <input type="number" step="0.001" class="form-control variation-markup"
                        name="variations[__INDEX__][markup]" value="">
                </div>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">{{ $baseCurrencySymbol }}</span>
                    <input type="number" step="0.001" class="form-control variation-unit-price"
                        name="variations[__INDEX__][unit_price]" value="">
                </div>
            </td>
            <td>
                <select class="form-select form-select-sm supplier-select" name="variations[__INDEX__][supplier_ids][]"
                    multiple>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->person_id }}">{{ $supplier->company_name }}</option>
                    @endforeach
                </select>
            </td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-danger remove-row" type="button"><i class="bi bi-trash"></i></button>
            </td>
        </tr>
    </template>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/js/selectize.min.js" crossorigin="anonymous"
        referrerpolicy="no-referrer"></script>

    @php
        $attributesData = $attributes->map(fn($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'values' => $a->values->map(fn($v) => ['id' => $v->id, 'name' => $v->name]),
        ])->values();
    @endphp
    <script>
        // Attributes data for dynamic population
        const ATTRIBUTES_DATA = @json($attributesData);

        function escapeAttr(str) {
            return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        function readSelectedIds($container) {
            var raw = $container.attr('data-selected-ids');
            if (!raw) return [];
            try { return JSON.parse(raw); } catch (e) { return []; }
        }

        function populateAttrValues($container, attrId, selectedIds) {
            const $valuesDiv = $container.find('.attr-values');
            const idx = $container.closest('.attr-assignments').data('index');
            $valuesDiv.empty();

            if (!attrId) return;

            const attr = ATTRIBUTES_DATA.find(function (a) { return a.id == attrId; });
            if (!attr) return;

            attr.values.forEach(function (v) {
                var checked = selectedIds && selectedIds.indexOf(v.id) !== -1 ? ' checked' : '';
                var id = 'var_attr_val_' + idx + '_' + v.id;
                $valuesDiv.append(
                    '<div class="form-check form-check-inline">' +
                    '<input class="form-check-input attr-value-cb" type="checkbox" ' +
                    'name="variations[' + idx + '][attribute_value_ids][]" ' +
                    'value="' + v.id + '" id="' + id + '"' + checked + '>' +
                    '<label class="form-check-label" for="' + id + '">' + v.name + '</label>' +
                    '</div>'
                );
            });
        }

        function initAttrSelect($select) {
            if ($select[0] && $select[0].selectize) {
                $select[0].selectize.destroy();
            }
            var $container = $select.closest('.attr-assignment');
            var initialSelectedIds = readSelectedIds($container);
            var initialAttrId = $select.val();
            var initialized = false;

            $select.selectize({
                placeholder: 'Select Attribute...',
                onInitialize: function () {
                    this.$wrapper.addClass('attr-select-dropdown');
                },
                onChange: function (value) {
                    if (!initialized) return;
                    var $c = $(this.$input).closest('.attr-assignment');
                    var prev = this.$input.data('prev-attr-id');
                    var selIds = prev == value ? (readSelectedIds($c) || []) : [];
                    populateAttrValues($c, value, selIds);
                    this.$input.data('prev-attr-id', value);
                }
            });

            if (initialAttrId) {
                populateAttrValues($container, initialAttrId, initialSelectedIds);
                $select.data('prev-attr-id', initialAttrId);
            }
            initialized = true;
        }

        function initSupplierSelect($select) {
            if ($select[0] && $select[0].selectize) {
                $select[0].selectize.destroy();
            }
            $select.selectize({
                placeholder: 'Search suppliers...',
                plugins: ['remove_button'],
            });
        }

        $(document).ready(function () {

            let variationIndex = {{ count(old('variations', $variations)) }};

            function addRowFromTemplate(containerId, templateId, isTable) {
                if (isTable === undefined) isTable = false;
                const $container = isTable ? $('#' + containerId + ' tbody') : $('#' + containerId);
                const $template = $('#' + templateId);
                if ($container.length && $template.length) {
                    const clone = $template.html();
                    $container.append(clone);
                }
            }

            function initRowSelectize($row) {
                $row.find('.supplier-select').each(function () {
                    initSupplierSelect($(this));
                });
                $row.find('.attr-select').each(function () {
                    initAttrSelect($(this));
                });
            }

            function addVariationRow(data) {
                data = data || {};
                const $tbody = $('#variations-tbody');
                const $template = $('#variation-row-template');
                if (!$tbody.length || !$template.length) return;

                const idx = variationIndex++;
                const html = $template.html().replace(/__INDEX__/g, idx);
                const $tr = $(html);

                if (data.id) $tr.find('[name="variations[' + idx + '][id]"]').val(data.id);
                if (data.name) $tr.find('[name="variations[' + idx + '][name]"]').val(data.name);
                if (data.item_number) $tr.find('[name="variations[' + idx + '][item_number]"]').val(data.item_number);
                if (data.cost_price) $tr.find('[name="variations[' + idx + '][cost_price]"]').val(data.cost_price);
                if (data.markup) $tr.find('[name="variations[' + idx + '][markup]"]').val(data.markup);
                if (data.markup_type) $tr.find('[name="variations[' + idx + '][markup_type]"]').val(data.markup_type);
                if (data.unit_price) $tr.find('[name="variations[' + idx + '][unit_price]"]').val(data.unit_price);

                $tbody.append($tr);
                initRowSelectize($tr);

                // If data has attribute assignments, add them
                if (data.attribute_value_ids && data.attribute_value_ids.length) {
                    const $assignments = $tr.find('.attr-assignments');
                    // Group attribute_value_ids by attribute
                    var attrMap = {};
                    data.attribute_value_ids.forEach(function (avId) {
                        ATTRIBUTES_DATA.forEach(function (attr) {
                            attr.values.forEach(function (v) {
                                if (v.id == avId) {
                                    if (!attrMap[attr.id]) {
                                        attrMap[attr.id] = { attr: attr, values: [] };
                                    }
                                    attrMap[attr.id].values.push(v);
                                }
                            });
                        });
                    });

                    Object.values(attrMap).forEach(function (group) {
                        var assignmentHtml = buildAttrAssignmentHtml(idx, group.attr.id, group.values);
                        $assignments.append(assignmentHtml);
                        var $lastAssignment = $assignments.find('.attr-assignment').last();
                        $lastAssignment.find('.attr-select').each(function () {
                            initAttrSelect($(this));
                        });
                    });
                }

                // If data has suppliers, set them
                if (data.supplier_ids) {
                    const selectize = $tr.find('.supplier-select')[0]?.selectize;
                    if (selectize) {
                        selectize.setValue(data.supplier_ids.map(String));
                    }
                }
            }

            function buildAttrAssignmentHtml(idx, selectedAttrId, selectedValues) {
                selectedValues = selectedValues || [];
                var selectedIds = selectedValues.map(function (sv) { return sv.id; });
                var optionsHtml = '<option value="">— Select Attribute —</option>';
                ATTRIBUTES_DATA.forEach(function (attr) {
                    optionsHtml += '<option value="' + attr.id + '"' +
                        (attr.id == selectedAttrId ? ' selected' : '') + '>' +
                        attr.name + '</option>';
                });

                return '<div class="attr-assignment mb-2 p-2 border rounded bg-light"' +
                    (selectedIds.length ? ' data-selected-ids=&quot;' + escapeAttr(JSON.stringify(selectedIds)) + '&quot;' : '') + '>' +
                    '<div class="d-flex align-items-center gap-2 mb-1">' +
                    '<select class="form-select form-select-sm attr-select flex-grow-1">' +
                    optionsHtml +
                    '</select>' +
                    '<button class="btn btn-sm btn-outline-danger remove-attr-assignment" type="button">&times;</button>' +
                    '</div>' +
                    '<div class="attr-values d-flex flex-wrap gap-2"></div>' +
                    '</div>';
            }

            // Initialize existing variation rows
            $('#variations-tbody .variation-row').each(function () {
                initRowSelectize($(this));
            });

            // Add additional item number row
            $('#add-additional-number').on('click', function () {
                addRowFromTemplate('additional-numbers-container', 'additional-number-row');
            });

            // Add secondary category row
            $('#add-secondary-category').on('click', function () {
                addRowFromTemplate('secondary-categories-container', 'secondary-category-row');
            });

            // Add secondary supplier row
            $('#add-secondary-supplier').on('click', function () {
                addRowFromTemplate('secondary-suppliers-container', 'secondary-supplier-row');
            });

            // Add variation row
            $('#add-variation').on('click', function () {
                addVariationRow({});
            });

            // Add attribute assignment to a variation row
            $(document).on('click', '.add-attr-assignment', function () {
                const $assignments = $(this).closest('.attr-assignments');
                const idx = $assignments.data('index');
                const html = buildAttrAssignmentHtml(idx, null, []);
                $assignments.append(html);
                const $newAssignment = $assignments.find('.attr-assignment').last();
                $newAssignment.find('.attr-select').each(function () {
                    initAttrSelect($(this));
                });
                // Move the "Add Attribute" button to the end
                $assignments.append($(this));
            });

            // Remove attribute assignment
            $(document).on('click', '.remove-attr-assignment', function () {
                $(this).closest('.attr-assignment').remove();
            });

            function updateMarkupSymbol() {
                var markupType = $('#markup_type').val();
                $('#markup-symbol').text(markupType === 'percentage' ? '%' : '{{ $baseCurrencySymbol }}');
            }

            // Markup calculation for item-level
            $('#markup, #cost_price, #unit_price, #markup_type').on('input change', function () {
                var cost = parseFloat($('#cost_price').val()) || 0;
                var markup = parseFloat($('#markup').val()) || 0;
                var unitPrice = parseFloat($('#unit_price').val()) || 0;
                var markupType = $('#markup_type').val();

                updateMarkupSymbol();

                if ($(this).is('#unit_price') && cost > 0) {
                    if (markupType === 'flat') {
                        $('#markup').val((unitPrice - cost).toFixed(3));
                    } else {
                        $('#markup').val(((unitPrice - cost) / cost * 100).toFixed(3));
                    }
                } else {
                    if (markupType === 'flat') {
                        $('#unit_price').val((cost + markup).toFixed(3));
                    } else {
                        $('#unit_price').val((cost + (cost * markup / 100)).toFixed(3));
                    }
                }
            });

            updateMarkupSymbol();

            // Variation inline pricing calculation
            $(document).on('input', '.variation-markup, .variation-cost', function () {
                var $row = $(this).closest('tr.variation-row');
                var cost = parseFloat($row.find('.variation-cost').val()) || 0;
                var markup = parseFloat($row.find('.variation-markup').val()) || 0;
                var markupType = $row.find('[name*="[markup_type]"]').val() || 'flat';
                var $unitPrice = $row.find('.variation-unit-price');
                if (markupType === 'flat') {
                    $unitPrice.val((cost + markup).toFixed(3));
                } else if (markupType === 'percentage') {
                    $unitPrice.val((cost + (cost * markup / 100)).toFixed(3));
                }
            });

            $(document).on('change', '[name*="[markup_type]"]', function () {
                var $row = $(this).closest('tr.variation-row');
                var $symbol = $row.find('.variation-markup').closest('.input-group').find('.input-group-text');
                $symbol.text($(this).val() === 'percentage' ? '%' : '{{ $baseCurrencySymbol }}');
                $row.find('.variation-markup').trigger('input');
            });

            // Remove rows via event delegation
            $(document).on('click', '.remove-row', function () {
                var $target = $(this).closest('.input-group, tr.variation-row, tr');
                if ($target.length) $target.remove();
            });
        });
    </script>
@endpush