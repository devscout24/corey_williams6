@extends('layouts.app')

@section('title', $supplier ? 'Edit Supplier' : 'New Supplier')
@section('page-title', $supplier ? 'Edit Supplier' : 'New Supplier')

@push('styles')
    <style>
        /* Dark Mode Form Fixes */
        [data-theme='dark'] .form-label,
        [data-theme='dark'] .form-check-label,
        [data-theme='dark'] .col-form-label,
        [data-theme='dark'] h6 {
            color: var(--gray-700) !important;
        }

        [data-theme='dark'] .nav-tabs .nav-link:not(.active) {
            color: var(--gray-400) !important;
        }

        [data-theme='dark'] .nav-tabs .nav-link:hover {
            background-color: var(--gray-200) !important;
            color: var(--gray-800) !important;
        }

        [data-theme='dark'] .input-group-text {
            background-color: var(--gray-200) !important;
            border-color: var(--gray-300) !important;
            color: var(--gray-800) !important;
        }

        [data-theme='dark'] .table-light {
            background-color: var(--gray-200) !important;
            color: var(--gray-900) !important;
        }

        /* Ensure card headers in dark mode aren't forced white */
        [data-theme='dark'] .card-header.bg-white,
        [data-theme='dark'] .card-footer.bg-white {
            background-color: var(--gray-100) !important;
            color: var(--gray-800) !important;
        }

        /* Dark Mode Placeholder Fix */
        [data-theme='dark'] ::placeholder {
            color: #0a0e13ff !important;
            /* light blue */
            opacity: 1;
        }

        [data-theme='dark'] .form-control::placeholder {
            color: #b6b6b6ff !important;
        }

        /* Fix tab text visibility */
        .nav-tabs .nav-link {
            color: #495057;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd !important;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <form method="post"
            action="{{ $supplier ? route('suppliers.update', $supplier->person_id) : route('suppliers.store') }}"
            class="needs-validation" enctype="multipart/form-data">
            @csrf
            @if($supplier)
                @method('put')
            @endif

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <ul class="nav nav-tabs" id="supplierFormTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic"
                                type="button" role="tab" aria-controls="basic" aria-selected="true">Basic Info</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="taxes-tab" data-bs-toggle="tab" data-bs-target="#taxes"
                                type="button" role="tab" aria-controls="taxes" aria-selected="false">Taxes</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="files-tab" data-bs-toggle="tab" data-bs-target="#files"
                                type="button" role="tab" aria-controls="files" aria-selected="false">Files</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced"
                                type="button" role="tab" aria-controls="advanced" aria-selected="false">Advanced</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="custom-fields-tab" data-bs-toggle="tab"
                                data-bs-target="#custom-fields" type="button" role="tab" aria-controls="custom-fields"
                                aria-selected="false">Custom Fields</button>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content" id="supplierFormTabsContent">

                        <!-- Basic Info Tab -->
                        <div class="tab-pane fade show active" id="basic" role="tabpanel" aria-labelledby="basic-tab">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="company_name">Company Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="company_name" name="company_name"
                                        value="{{ old('company_name', $supplier?->company_name) }}" required />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="account_number">Account Number</label>
                                    <input type="text" class="form-control" id="account_number" name="account_number"
                                        value="{{ old('account_number', $supplier?->account_number) }}" />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="image">Supplier Image</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*" />
                                    @if($supplier && $supplier->image_id)
                                        <div class="mt-2 text-muted small">Current image ID: {{ $supplier->image_id }}</div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="first_name">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name"
                                        value="{{ old('first_name', $person?->first_name) }}" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="last_name">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name"
                                        value="{{ old('last_name', $person?->last_name) }}" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="email">Email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="{{ old('email', $person?->email) }}" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="phone_number">Phone Number</label>
                                    <input type="text" class="form-control" id="phone_number" name="phone_number"
                                        value="{{ old('phone_number', $person?->phone_number) }}" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="address_1">Address 1</label>
                                    <input type="text" class="form-control" id="address_1" name="address_1"
                                        value="{{ old('address_1', $person?->address_1) }}" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="address_2">Address 2</label>
                                    <input type="text" class="form-control" id="address_2" name="address_2"
                                        value="{{ old('address_2', $person?->address_2) }}" />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="city">City</label>
                                    <input type="text" class="form-control" id="city" name="city"
                                        value="{{ old('city', $person?->city) }}" />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="state">State</label>
                                    <input type="text" class="form-control" id="state" name="state"
                                        value="{{ old('state', $person?->state) }}" />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="zip">Zip</label>
                                    <input type="text" class="form-control" id="zip" name="zip"
                                        value="{{ old('zip', $person?->zip) }}" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="country">Country</label>
                                    <input type="text" class="form-control" id="country" name="country"
                                        value="{{ old('country', $person?->country) }}" />
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label" for="comments">Comments</label>
                                    <textarea class="form-control" id="comments" name="comments"
                                        rows="3">{{ old('comments', $person?->comments) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Taxes Tab -->
                        <div class="tab-pane fade" id="taxes" role="tabpanel" aria-labelledby="taxes-tab">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                            id="override_default_tax" name="override_default_tax" value="1"
                                            @checked(old('override_default_tax', $supplier?->override_default_tax))>
                                        <label class="form-check-label" for="override_default_tax">Override Default
                                            Tax</label>
                                    </div>
                                </div>

                                <div id="tax_overrides_container"
                                    class="{{ old('override_default_tax', $supplier?->override_default_tax) ? '' : 'd-none' }}">
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label" for="tax_class_id">Tax Class</label>
                                        <select class="form-select" id="tax_class_id" name="tax_class_id">
                                            <option value="">— None —</option>
                                            @foreach($tax_classes as $tax_class)
                                                <option value="{{ $tax_class->id }}" @selected(old('tax_class_id', $supplier?->tax_class_id) == $tax_class->id)>{{ $tax_class->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <h6>Specific Tax Rates</h6>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm" id="taxes-table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Tax Name</th>
                                                    <th>Percent (%)</th>
                                                    <th>Cumulative</th>
                                                    <th width="50">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($supplier_taxes as $index => $tax)
                                                    <tr>
                                                        <td><input type="text" class="form-control" name="tax_names[]"
                                                                value="{{ old('tax_names.' . $index, $tax->name) }}"></td>
                                                        <td><input type="number" step="0.001" class="form-control"
                                                                name="tax_percents[]"
                                                                value="{{ old('tax_percents.' . $index, $tax->percent) }}"></td>
                                                        <td class="text-center">
                                                            <input type="checkbox" class="form-check-input"
                                                                name="tax_cumulatives[]" value="1"
                                                                @checked(old('tax_cumulatives.' . $index, $tax->cumulative))>
                                                        </td>
                                                        <td class="text-center"><button
                                                                class="btn btn-sm btn-outline-danger remove-row"
                                                                type="button"><i class="bi bi-trash"></i></button></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-tax-row"><i
                                            class="bi bi-plus"></i> Add Tax Rate</button>
                                </div>
                            </div>
                        </div>

                        <!-- Files Tab -->
                        <div class="tab-pane fade" id="files" role="tabpanel" aria-labelledby="files-tab">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <h6>Existing Files</h6>
                                    @if(count($files))
                                        <ul class="list-group mb-4">
                                            @foreach($files as $file)
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <span><i class="bi bi-file-earmark"></i> {{ $file->file_name }}</span>
                                                    <div class="btn-group">
                                                        <a href="{{ route('suppliers.files.download', $file->file_id) }}"
                                                            class="btn btn-sm btn-outline-primary" target="_blank"><i
                                                                class="bi bi-download"></i></a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger delete-file-btn"
                                                            data-id="{{ $file->file_id }}"><i class="bi bi-trash"></i></button>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="text-muted small">No files uploaded yet.</p>
                                    @endif

                                    <h6>Add Files</h6>
                                    <div id="file_uploads_container">
                                        @for($k = 1; $k <= 5; $k++)
                                            <div class="mb-2">
                                                <label class="form-label small">File {{ $k }}</label>
                                                <input type="file" name="files[]" class="form-control form-control-sm">
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Advanced Tab -->
                        <div class="tab-pane fade" id="advanced" role="tabpanel" aria-labelledby="advanced-tab">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="default_term_id">Default Invoice Term</label>
                                    <select class="form-select" id="default_term_id" name="default_term_id">
                                        <option value="">— None —</option>
                                        @foreach($invoice_terms as $term)
                                            <option value="{{ $term->id }}" @selected(old('default_term_id', $supplier?->default_term_id) == $term->id)>{{ $term->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="balance">Balance</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" step="0.01" class="form-control" id="balance" name="balance"
                                            value="{{ old('balance', $supplier?->balance ?? 0) }}" />
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label" for="internal_notes">Internal Notes</label>
                                    <textarea class="form-control" id="internal_notes" name="internal_notes"
                                        rows="4">{{ old('internal_notes', $supplier?->internal_notes) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Custom Fields Tab -->
                        <div class="tab-pane fade" id="custom-fields" role="tabpanel" aria-labelledby="custom-fields-tab">
                            <div class="row g-3">
                                @for($i = 1; $i <= 10; $i++)
                                    <div class="col-md-6">
                                        <label class="form-label" for="custom_field_{{ $i }}_value">Custom Field
                                            {{ $i }}</label>
                                        <input type="text" class="form-control" id="custom_field_{{ $i }}_value"
                                            name="custom_field_{{ $i }}_value"
                                            value="{{ old("custom_field_{$i}_value", $supplier?->{"custom_field_{$i}_value"}) }}">
                                    </div>
                                @endfor
                            </div>
                        </div>

                    </div>
                </div>
                <div class="card-footer bg-white border-top text-end py-3">
                    <a class="btn btn-outline-secondary me-2" href="{{ route('suppliers.index') }}">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save"></i> Save Supplier</button>
                </div>
            </div>
        </form>

        @if($supplier)
            <form id="delete-file-form" method="post" style="display:none">
                @csrf
                @method('delete')
            </form>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const overrideCheckbox = document.getElementById('override_default_tax');
            const container = document.getElementById('tax_overrides_container');

            if (overrideCheckbox) {
                overrideCheckbox.addEventListener('change', function () {
                    if (this.checked) {
                        container.classList.remove('d-none');
                    } else {
                        container.classList.add('d-none');
                    }
                });
            }

            // Add tax row
            document.getElementById('add-tax-row')?.addEventListener('click', function () {
                const tbody = document.querySelector('#taxes-table tbody');
                const tr = document.createElement('tr');
                tr.innerHTML = `
                        <td><input type="text" class="form-control" name="tax_names[]" value=""></td>
                        <td><input type="number" step="0.001" class="form-control" name="tax_percents[]" value=""></td>
                        <td class="text-center"><input type="checkbox" class="form-check-input" name="tax_cumulatives[]" value="1"></td>
                        <td class="text-center"><button class="btn btn-sm btn-outline-danger remove-row" type="button"><i class="bi bi-trash"></i></button></td>
                    `;
                tbody.appendChild(tr);
            });

            // Remove rows via event delegation
            document.addEventListener('click', function (e) {
                const removeBtn = e.target.closest('.remove-row');
                if (removeBtn) {
                    removeBtn.closest('tr').remove();
                }

                const deleteFileBtn = e.target.closest('.delete-file-btn');
                if (deleteFileBtn) {
                    if (confirm('Are you sure you want to delete this file?')) {
                        const fileId = deleteFileBtn.dataset.id;
                        const form = document.getElementById('delete-file-form');
                        form.action = `/suppliers/files/${fileId}`;
                        form.submit();
                    }
                }
            });
        });
    </script>
@endpush