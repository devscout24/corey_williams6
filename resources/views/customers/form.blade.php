@extends('layouts.app')

@section('title', $customer ? 'Edit Customer' : 'New Customer')
@section('page-title', $customer ? 'Edit Customer' : 'New Customer')

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
    <form method="post" action="{{ $customer ? route('customers.update', $customer->person_id) : route('customers.store') }}" class="needs-validation" enctype="multipart/form-data">
        @csrf
        @if($customer)
            @method('put')
        @endif

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <ul class="nav nav-tabs" id="customerFormTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab" aria-controls="basic" aria-selected="true">Basic Info</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="taxes-tab" data-bs-toggle="tab" data-bs-target="#taxes" type="button" role="tab" aria-controls="taxes" aria-selected="false">Taxes</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="files-tab" data-bs-toggle="tab" data-bs-target="#files" type="button" role="tab" aria-controls="files" aria-selected="false">Files</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced" type="button" role="tab" aria-controls="advanced" aria-selected="false">Advanced</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="custom-fields-tab" data-bs-toggle="tab" data-bs-target="#custom-fields" type="button" role="tab" aria-controls="custom-fields" aria-selected="false">Custom Fields</button>
                    </li>
                </ul>
            </div>
            
            <div class="card-body">
                <div class="tab-content" id="customerFormTabsContent">
                    
                    <!-- Basic Info Tab -->
                    <div class="tab-pane fade show active" id="basic" role="tabpanel" aria-labelledby="basic-tab">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="first_name">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="{{ old('first_name', $customer?->person?->first_name) }}" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="last_name">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="{{ old('last_name', $customer?->person?->last_name) }}" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $customer?->person?->email) }}" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone_number">Phone Number</label>
                                <input type="text" class="form-control" id="phone_number" name="phone_number" value="{{ old('phone_number', $customer?->person?->phone_number) }}" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="company_name">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" value="{{ old('company_name', $customer?->company_name) }}" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="account_number">Account #</label>
                                <input type="text" class="form-control" id="account_number" name="account_number" value="{{ old('account_number', $customer?->account_number) }}" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="address_1">Address 1</label>
                                <input type="text" class="form-control" id="address_1" name="address_1" value="{{ old('address_1', $customer?->person?->address_1) }}" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="address_2">Address 2</label>
                                <input type="text" class="form-control" id="address_2" name="address_2" value="{{ old('address_2', $customer?->person?->address_2) }}" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="city">City</label>
                                <input type="text" class="form-control" id="city" name="city" value="{{ old('city', $customer?->person?->city) }}" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="state">State</label>
                                <input type="text" class="form-control" id="state" name="state" value="{{ old('state', $customer?->person?->state) }}" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="zip">Zip</label>
                                <input type="text" class="form-control" id="zip" name="zip" value="{{ old('zip', $customer?->person?->zip) }}" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="country">Country</label>
                                <input type="text" class="form-control" id="country" name="country" value="{{ old('country', $customer?->person?->country) }}" />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="comments">Comments</label>
                                <textarea class="form-control" id="comments" name="comments" rows="3">{{ old('comments', $customer?->person?->comments) }}</textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="internal_notes">Internal Notes</label>
                                <textarea class="form-control" id="internal_notes" name="internal_notes" rows="3">{{ old('internal_notes', $customer?->internal_notes) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Taxes Tab -->
                    <div class="tab-pane fade" id="taxes" role="tabpanel" aria-labelledby="taxes-tab">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="taxable" name="taxable" value="1" @checked(old('taxable', $customer?->taxable ?? true))>
                                    <label class="form-check-label" for="taxable">Taxable</label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="override_default_tax" name="override_default_tax" value="1" @checked(old('override_default_tax', $customer?->override_default_tax))>
                                    <label class="form-check-label" for="override_default_tax">Override Default Tax</label>
                                </div>
                            </div>

                            <div id="tax_overrides_container" class="{{ old('override_default_tax', $customer?->override_default_tax) ? '' : 'd-none' }}">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label" for="tax_class_id">Tax Class</label>
                                    <select class="form-select" id="tax_class_id" name="tax_class_id">
                                        <option value="">— None —</option>
                                        @foreach($taxClasses as $taxClass)
                                            <option value="{{ $taxClass->id }}" @selected(old('tax_class_id', $customer?->tax_class_id) == $taxClass->id)>{{ $taxClass->name }}</option>
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
                                            @if($customer)
                                                @foreach($customer->taxes as $index => $tax)
                                                    <tr>
                                                        <td><input type="text" class="form-control" name="tax_names[]" value="{{ old('tax_names.' . $index, $tax->name) }}"></td>
                                                        <td><input type="number" step="0.001" class="form-control" name="tax_percents[]" value="{{ old('tax_percents.' . $index, $tax->percent) }}"></td>
                                                        <td class="text-center">
                                                            <input type="checkbox" class="form-check-input" name="tax_cumulatives[]" value="1" @checked(old('tax_cumulatives.' . $index, $tax->cumulative))>
                                                        </td>
                                                        <td class="text-center"><button class="btn btn-sm btn-outline-danger remove-row" type="button"><i class="bi bi-trash"></i></button></td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-tax-row"><i class="bi bi-plus"></i> Add Tax Rate</button>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="tax_certificate">Tax Certificate #</label>
                                <input type="text" class="form-control" id="tax_certificate" name="tax_certificate" value="{{ old('tax_certificate', $customer?->tax_certificate) }}" />
                            </div>
                        </div>
                    </div>

                    <!-- Files Tab -->
                    <div class="tab-pane fade" id="files" role="tabpanel" aria-labelledby="files-tab">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <h6>Existing Files</h6>
                                <div class="list-group mb-3">
                                    @forelse($customerFiles as $file)
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="bi bi-file-earmark me-2"></i>
                                                {{ $file->file_name }}
                                                <small class="text-muted ms-2">Uploaded: {{ $file->timestamp }}</small>
                                            </div>
                                            <div class="btn-group">
                                                <a href="{{ route('customers.download-file', $file->file_id) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-file" data-id="{{ $file->file_id }}"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="list-group-item text-muted">No files uploaded.</div>
                                    @endforelse
                                </div>

                                <label class="form-label">Upload New Files</label>
                                <input type="file" class="form-control" name="customer_files[]" multiple />
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Tab -->
                    <div class="tab-pane fade" id="advanced" role="tabpanel" aria-labelledby="advanced-tab">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="tier_id">Price Tier</label>
                                <select class="form-select" id="tier_id" name="tier_id">
                                    <option value="-1">— None —</option>
                                    @foreach($tiers as $tier)
                                        <option value="{{ $tier->id }}" @selected(old('tier_id', $customer?->tier_id) == $tier->id)>{{ $tier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="balance">Store Account Balance</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" id="balance" name="balance" value="{{ old('balance', $customer?->balance ?? 0) }}" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="credit_limit">Credit Limit</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" id="credit_limit" name="credit_limit" value="{{ old('credit_limit', $customer?->credit_limit) }}" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="points">Loyalty Points</label>
                                <input type="number" class="form-control" id="points" name="points" value="{{ old('points', $customer?->points ?? 0) }}" />
                            </div>
                        </div>
                    </div>

                    <!-- Custom Fields Tab -->
                    <div class="tab-pane fade" id="custom-fields" role="tabpanel" aria-labelledby="custom-fields-tab">
                        <div class="row g-3">
                            @for($i = 1; $i <= 10; $i++)
                                <div class="col-md-6">
                                    <label class="form-label" for="custom_field_{{ $i }}_value">Custom Field {{ $i }}</label>
                                    <input type="text" class="form-control" id="custom_field_{{ $i }}_value" name="custom_field_{{ $i }}_value" value="{{ old('custom_field_' . $i . '_value', $customer?->{'custom_field_' . $i . '_value'}) }}" />
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-white py-3">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg"></i> {{ $customer ? 'Update' : 'Create' }} Customer
                </button>
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary px-4 ms-2">Cancel</a>
            </div>
        </div>
    </form>
</div>

<template id="tax-row-template">
    <tr>
        <td><input type="text" class="form-control" name="tax_names[]" value=""></td>
        <td><input type="number" step="0.001" class="form-control" name="tax_percents[]" value=""></td>
        <td class="text-center">
            <input type="checkbox" class="form-check-input" name="tax_cumulatives[]" value="1">
        </td>
        <td class="text-center"><button class="btn btn-sm btn-outline-danger remove-row" type="button"><i class="bi bi-trash"></i></button></td>
    </tr>
</template>

<form id="delete-file-form" method="post" style="display:none">
    @csrf
    @method('delete')
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tax Override Toggle
    const overrideTaxSwitch = document.getElementById('override_default_tax');
    const taxOverridesContainer = document.getElementById('tax_overrides_container');
    
    if (overrideTaxSwitch) {
        overrideTaxSwitch.addEventListener('change', function() {
            if (this.checked) {
                taxOverridesContainer.classList.remove('d-none');
            } else {
                taxOverridesContainer.classList.add('d-none');
            }
        });
    }

    // Add Tax Row
    const addTaxBtn = document.getElementById('add-tax-row');
    const taxesTableBody = document.querySelector('#taxes-table tbody');
    const taxRowTemplate = document.getElementById('tax-row-template');

    if (addTaxBtn) {
        addTaxBtn.addEventListener('click', function() {
            const clone = taxRowTemplate.content.cloneNode(true);
            taxesTableBody.appendChild(clone);
        });
    }

    // Remove Tax Row (Event Delegation)
    if (taxesTableBody) {
        taxesTableBody.addEventListener('click', function(e) {
            if (e.target.closest('.remove-row')) {
                e.target.closest('tr').remove();
            }
        });
    }

    // Delete File
    document.querySelectorAll('.delete-file').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this file?')) {
                const id = this.dataset.id;
                const form = document.getElementById('delete-file-form');
                form.action = `/customers/delete-file/${id}`;
                form.submit();
            }
        });
    });
});
</script>
@endpush
                    
                    <!-- Taxes Tab -->
                    <div class="tab-pane fade" id="taxes" role="tabpanel" aria-labelledby="taxes-tab">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="taxable" name="taxable" value="1" @checked(old('taxable', $customer?->taxable ?? true))>
                                    <label class="form-check-label" for="taxable">Taxable</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="tax_certificate">Tax Certificate #</label>
                                <input type="text" class="form-control" id="tax_certificate" name="tax_certificate" value="{{ old('tax_certificate', $customer?->tax_certificate) }}" />
                            </div>
                            
                            <div class="col-md-12">
                                <hr>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="override_default_tax" name="override_default_tax" value="1" @checked(old('override_default_tax', $customer?->override_default_tax))>
                                    <label class="form-check-label" for="override_default_tax">Override Default Tax</label>
                                </div>
                            </div>
                            
                            <div id="tax_overrides_container" class="{{ old('override_default_tax', $customer?->override_default_tax) ? '' : 'd-none' }}">
                                <div class="row g-3">
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label" for="tax_class_id">Tax Class</label>
                                        <select class="form-select" id="tax_class_id" name="tax_class_id">
                                            <option value="">— None —</option>
                                            @foreach($taxClasses as $taxClass)
                                                <option value="{{ $taxClass->id }}" @selected(old('tax_class_id', $customer?->tax_class_id) == $taxClass->id)>{{ $taxClass->name }}</option>
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
                                                    @foreach($customer?->taxes ?? [] as $tax)
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
                    
                    <!-- Files Tab -->
                    <div class="tab-pane fade" id="files" role="tabpanel" aria-labelledby="files-tab">
                        <div class="mb-4">
                            <label class="form-label" for="customer_files">Upload Files</label>
                            <input type="file" class="form-control" id="customer_files" name="customer_files[]" multiple />
                            <small class="text-muted">You can select multiple files at once.</small>
                        </div>
                        
                        @if(!empty($customerFiles) && count($customerFiles) > 0)
                            <h6>Existing Files</h6>
                            <div class="list-group shadow-sm mb-4">
                                @foreach($customerFiles as $file)
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-file-earmark-text fs-4 me-3 text-primary"></i>
                                            <div>
                                                <div class="fw-bold">{{ $file->file_name }}</div>
                                                <small class="text-muted">Uploaded on {{ \Carbon\Carbon::parse($file->timestamp)->format('M d, Y') }}</small>
                                            </div>
                                        </div>
                                        <div class="btn-group">
                                            <a href="{{ route('customers.files.download', $file->file_id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="if(confirm('Delete this file?')) { document.getElementById('delete-file-{{ $file->file_id }}').submit(); }">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            @foreach($customerFiles as $file)
                                <form id="delete-file-{{ $file->file_id }}" action="{{ route('customers.files.delete', $file->file_id) }}" method="POST" style="display:none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            @endforeach
                        @endif
                    </div>

                    <!-- Advanced Tab -->
                    <div class="tab-pane fade" id="advanced" role="tabpanel" aria-labelledby="advanced-tab">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="tier_id">Price Tier</label>
                                <select class="form-select" id="tier_id" name="tier_id">
                                    <option value="-1">— None —</option>
                                    @foreach($tiers as $tier)
                                        <option value="{{ $tier->id }}" @selected(old('tier_id', $customer?->tier_id) == $tier->id)>{{ $tier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="balance">Store Account Balance</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" id="balance" name="balance" value="{{ old('balance', $customer?->balance ?? 0) }}" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="credit_limit">Credit Limit</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" id="credit_limit" name="credit_limit" value="{{ old('credit_limit', $customer?->credit_limit) }}" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="points">Points</label>
                                <input type="number" class="form-control" id="points" name="points" value="{{ old('points', $customer?->points ?? 0) }}" />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="internal_notes">Internal Notes</label>
                                <textarea class="form-control" id="internal_notes" name="internal_notes" rows="3">{{ old('internal_notes', $customer?->internal_notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Custom Fields Tab -->
                    <div class="tab-pane fade" id="custom-fields" role="tabpanel" aria-labelledby="custom-fields-tab">
                        <div class="row g-3">
                            @for($i = 1; $i <= 10; $i++)
                                <div class="col-md-6">
                                    <label class="form-label" for="custom_field_{{ $i }}_value">Custom Field {{ $i }}</label>
                                    <input type="text" class="form-control" id="custom_field_{{ $i }}_value" name="custom_field_{{ $i }}_value" value="{{ old("custom_field_{$i}_value", $customer?->{"custom_field_{$i}_value"}) }}">
                                </div>
                            @endfor
                        </div>
                    </div>
                    
                </div>
            </div>
            <div class="card-footer bg-white border-top text-end py-3">
                <a class="btn btn-outline-secondary me-2" href="{{ route('customers.index') }}">Cancel</a>
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save"></i> Save Customer</button>
            </div>
        </div>
    </form>
</div>

{{-- Templates for dynamic rows --}}
<template id="tax-row">
    <tr>
        <td><input type="text" class="form-control" name="tax_names[]" value=""></td>
        <td><input type="number" step="0.001" class="form-control" name="tax_percents[]" value=""></td>
        <td class="text-center"><input type="checkbox" class="form-check-input" name="tax_cumulatives[]" value="1"></td>
        <td class="text-center"><button class="btn btn-sm btn-outline-danger remove-row" type="button"><i class="bi bi-trash"></i></button></td>
    </tr>
</template>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle tax overrides container
    const overrideCheckbox = document.getElementById('override_default_tax');
    const taxContainer = document.getElementById('tax_overrides_container');
    if (overrideCheckbox) {
        overrideCheckbox.addEventListener('change', function() {
            taxContainer.classList.toggle('d-none', !this.checked);
        });
    }

    // Add tax row
    document.getElementById('add-tax-row')?.addEventListener('click', function() {
        const tbody = document.querySelector('#taxes-table tbody');
        const template = document.getElementById('tax-row').innerHTML;
        tbody.insertAdjacentHTML('beforeend', template);
    });

    // Remove row
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row')) {
            e.target.closest('tr').remove();
        }
    });
});
</script>
@endpush
