@extends('layouts.app')

@section('title', 'Store Config')
@section('page-title', 'Store Config')

@section('content')
<div class="container-fluid">
    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @error('config')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <form method="post" action="{{ route('config.update') }}" enctype="multipart/form-data">
        @csrf
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <ul class="nav nav-tabs" id="configTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">General</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="taxes-tab" data-bs-toggle="tab" data-bs-target="#taxes" type="button" role="tab">Taxes</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="localization-tab" data-bs-toggle="tab" data-bs-target="#localization" type="button" role="tab">Localization</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button" role="tab">Sales & Receipts</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="loyalty-tab" data-bs-toggle="tab" data-bs-target="#loyalty" type="button" role="tab">Loyalty & Accounts</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="barcodes-tab" data-bs-toggle="tab" data-bs-target="#barcodes" type="button" role="tab">Barcodes</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced" type="button" role="tab">Advanced</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="currency-tab" data-bs-toggle="tab" data-bs-target="#currency" type="button" role="tab">Currency</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="payment-types-tab" data-bs-toggle="tab" data-bs-target="#payment-types" type="button" role="tab">Payment Types</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="price-rules-tab" data-bs-toggle="tab" data-bs-target="#price-rules" type="button" role="tab">Price Rules</button>
                    </li>
                </ul>
            </div>
            
            <div class="card-body p-4">
                <div class="tab-content" id="configTabsContent">
                    
                    <!-- General Info -->
                    <div class="tab-pane fade show active" id="general" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="company" class="form-control" value="{{ $values['company'] }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Company Logo</label>
                                <input type="file" name="company_logo" class="form-control">
                            </div>
                            <div class="col-md-4 d-flex align-items-center pt-4">
                                @if($values['company_logo'])
                                    <img src="{{ route('app_files.view', ['fileId' => $values['company_logo']]) }}" alt="Logo" style="max-height: 50px;" class="border p-1 rounded">
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Website</label>
                                <input type="text" name="website" class="form-control" value="{{ $values['website'] }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ $values['email'] }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="{{ $values['phone'] }}">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2">{{ $values['address'] }}</textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Return Policy</label>
                                <textarea name="return_policy" class="form-control" rows="3">{{ $values['return_policy'] }}</textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Taxes -->
                    <div class="tab-pane fade" id="taxes" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Default Tax 1 Name</label>
                                <input type="text" name="default_tax_1_name" class="form-control" value="{{ $values['default_tax_1_name'] }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Default Tax 1 Rate (%)</label>
                                <input type="text" name="default_tax_1_rate" class="form-control" value="{{ $values['default_tax_1_rate'] }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Default Tax 2 Name</label>
                                <input type="text" name="default_tax_2_name" class="form-control" value="{{ $values['default_tax_2_name'] }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Default Tax 2 Rate (%)</label>
                                <input type="text" name="default_tax_2_rate" class="form-control" value="{{ $values['default_tax_2_rate'] }}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="default_tax_2_cumulative" class="form-check-input" value="1" @checked($values['default_tax_2_cumulative'])>
                                    <label class="form-check-label">Cumulative</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Localization -->
                    <div class="tab-pane fade" id="localization" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Date Format</label>
                                <input type="text" name="date_format" class="form-control" value="{{ $values['date_format'] }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Time Format</label>
                                <input type="text" name="time_format" class="form-control" value="{{ $values['time_format'] }}">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sales & Receipts -->
                    <div class="tab-pane fade" id="sales" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Sale ID Prefix</label>
                                <input type="text" name="sale_prefix" class="form-control" value="{{ $values['sale_prefix'] }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Receipt Text Size</label>
                                <select name="receipt_text_size" class="form-select">
                                    <option value="small" @selected($values['receipt_text_size'] == 'small')>Small</option>
                                    <option value="medium" @selected($values['receipt_text_size'] == 'medium')>Medium</option>
                                    <option value="large" @selected($values['receipt_text_size'] == 'large')>Large</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <hr>
                                <div class="form-check form-switch mb-2">
                                    <input type="checkbox" name="print_after_sale" class="form-check-input" value="1" @checked($values['print_after_sale'])>
                                    <label class="form-check-label">Automatically print receipt after sale</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input type="checkbox" name="automatically_email_receipt" class="form-check-input" value="1" @checked($values['automatically_email_receipt'])>
                                    <label class="form-check-label">Automatically email receipt to customer</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input type="checkbox" name="hide_signature" class="form-check-input" value="1" @checked($values['hide_signature'])>
                                    <label class="form-check-label">Hide signature line on receipts</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Loyalty & Accounts -->
                    <div class="tab-pane fade" id="loyalty" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="form-check form-switch mb-3">
                                    <input type="checkbox" name="enable_customer_loyalty_system" class="form-check-input" value="1" @checked($values['enable_customer_loyalty_system'])>
                                    <label class="form-check-label fw-bold">Enable Customer Loyalty System</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Point Value ($)</label>
                                <input type="text" name="point_value" class="form-control" value="{{ $values['point_value'] }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Spend to Point Ratio (e.g. 10:1)</label>
                                <input type="text" name="spend_to_point_ratio" class="form-control" value="{{ $values['spend_to_point_ratio'] }}">
                            </div>
                            <div class="col-md-12 mt-4">
                                <hr>
                                <h6>Store Accounts</h6>
                                <div class="form-check form-switch mb-2">
                                    <input type="checkbox" name="customers_store_accounts" class="form-check-input" value="1" @checked($values['customers_store_accounts'])>
                                    <label class="form-check-label">Enable Customer Store Accounts</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input type="checkbox" name="suppliers_store_accounts" class="form-check-input" value="1" @checked($values['suppliers_store_accounts'])>
                                    <label class="form-check-label">Enable Supplier Store Accounts</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Barcodes -->
                    <div class="tab-pane fade" id="barcodes" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Barcode Type</label>
                                <select name="barcode_type" class="form-select">
                                    <option value="Code39" @selected($values['barcode_type'] == 'Code39')>Code 39</option>
                                    <option value="Code128" @selected($values['barcode_type'] == 'Code128')>Code 128</option>
                                    <option value="EAN13" @selected($values['barcode_type'] == 'EAN13')>EAN-13</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Background Image</label>
                                <input type="file" name="barcode_background" class="form-control">
                            </div>
                            <div class="col-md-4 d-flex align-items-center pt-4">
                                @if($values['barcode_background'])
                                    <img src="{{ route('app_files.view', ['fileId' => $values['barcode_background']]) }}" alt="Background" style="max-height: 50px;" class="border p-1 rounded">
                                @endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sheet Label Background</label>
                                <input type="file" name="label_sheet_background" class="form-control">
                            </div>
                            <div class="col-md-4 d-flex align-items-center pt-4">
                                @if($values['label_sheet_background'])
                                    <img src="{{ route('app_files.view', ['fileId' => $values['label_sheet_background']]) }}" alt="Sheet Background" style="max-height: 50px;" class="border p-1 rounded">
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Font Size</label>
                                <input type="number" name="barcode_font_size" class="form-control" value="{{ $values['barcode_font_size'] }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Width</label>
                                <input type="number" name="barcode_width" class="form-control" value="{{ $values['barcode_width'] }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Height</label>
                                <input type="number" name="barcode_height" class="form-control" value="{{ $values['barcode_height'] }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Quality (1-100)</label>
                                <input type="number" name="barcode_quality" class="form-control" value="{{ $values['barcode_quality'] }}">
                            </div>
                            <div class="col-md-12 mt-2">
                                <div class="form-check form-switch mb-2">
                                    <input type="checkbox" name="show_barcode_company_name" class="form-check-input" value="1" @checked($values['show_barcode_company_name'])>
                                    <label class="form-check-label">Show company name on barcode labels</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input type="checkbox" name="hide_barcode_on_barcode_labels" class="form-check-input" value="1" @checked($values['hide_barcode_on_barcode_labels'])>
                                    <label class="form-check-label">Hide barcode image on labels</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced -->
                    <div class="tab-pane fade" id="advanced" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Session Expiration (seconds, 0 for browser close)</label>
                                <input type="number" name="phppos_session_expiration" class="form-control" value="{{ $values['phppos_session_expiration'] }}">
                            </div>
                            <div class="col-md-12 mt-3">
                                <div class="form-check form-switch mb-2">
                                    <input type="checkbox" name="speed_up_search_queries" class="form-check-input" value="1" @checked($values['speed_up_search_queries'])>
                                    <label class="form-check-label">Speed up search queries (optimized indexes)</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input type="checkbox" name="enable_sounds" class="form-check-input" value="1" @checked($values['enable_sounds'])>
                                    <label class="form-check-label">Enable UI sounds</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input type="checkbox" name="hide_dashboard_statistics" class="form-check-input" value="1" @checked($values['hide_dashboard_statistics'])>
                                    <label class="form-check-label">Hide statistics from dashboard</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Currency -->
                    <div class="tab-pane fade" id="currency" role="tabpanel">
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Currency Symbol</label>
                                <input type="text" name="currency_symbol" class="form-control" value="{{ $values['currency_symbol'] }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Currency Code (ISO)</label>
                                <input type="text" name="currency_code" class="form-control" value="{{ $values['currency_code'] }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Symbol Location</label>
                                <select name="currency_symbol_location" class="form-select">
                                    <option value="before" @selected($values['currency_symbol_location'] == 'before')>Before Amount ($1.00)</option>
                                    <option value="after" @selected($values['currency_symbol_location'] == 'after')>After Amount (1.00$)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Decimals</label>
                                <input type="number" name="number_of_decimals" class="form-control" value="{{ $values['number_of_decimals'] }}">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="exchangeRatesTable">
                                <thead>
                                    <tr>
                                        <th>Currency Code To</th>
                                        <th>Symbol</th>
                                        <th>Exchange Rate</th>
                                        <th>Symbol Location</th>
                                        <th>Decimals</th>
                                        <th>Thousands Separator</th>
                                        <th>Decimal Point</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($exchange_rates as $rate)
                                    <tr>
                                        <td><input type="text" name="currency_exchange_rates_to[]" class="form-control" value="{{ $rate->currency_code_to }}"></td>
                                        <td><input type="text" name="currency_exchange_rates_symbol[]" class="form-control" value="{{ $rate->currency_symbol }}"></td>
                                        <td><input type="text" name="currency_exchange_rates_rate[]" class="form-control" value="{{ (float)$rate->exchange_rate }}"></td>
                                        <td>
                                            <select name="currency_exchange_rates_symbol_location[]" class="form-select">
                                                <option value="before" @selected($rate->currency_symbol_location == 'before')>Before</option>
                                                <option value="after" @selected($rate->currency_symbol_location == 'after')>After</option>
                                            </select>
                                        </td>
                                        <td><input type="number" name="currency_exchange_rates_number_of_decimals[]" class="form-control" value="{{ $rate->number_of_decimals }}"></td>
                                        <td><input type="text" name="currency_exchange_rates_thousands_separator[]" class="form-control" value="{{ $rate->thousands_separator }}"></td>
                                        <td><input type="text" name="currency_exchange_rates_decimal_point[]" class="form-control" value="{{ $rate->decimal_point }}"></td>
                                        <td><button type="button" class="btn btn-sm btn-danger remove-rate"><i class="bi bi-trash"></i></button></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-sm btn-secondary" id="addExchangeRate">Add Exchange Rate</button>
                        </div>
                    </div>

                    <!-- Payment Types -->
                    <div class="tab-pane fade" id="payment-types" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Default Payment Type</label>
                                <select name="default_payment_type" class="form-select">
                                    <option value="Cash" @selected($values['default_payment_type'] == 'Cash')>Cash</option>
                                    <option value="Check" @selected($values['default_payment_type'] == 'Check')>Check</option>
                                    <option value="Gift Card" @selected($values['default_payment_type'] == 'Gift Card')>Gift Card</option>
                                    <option value="Debit Card" @selected($values['default_payment_type'] == 'Debit Card')>Debit Card</option>
                                    <option value="Credit Card" @selected($values['default_payment_type'] == 'Credit Card')>Credit Card</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Additional Payment Types (Comma Separated)</label>
                                <input type="text" name="additional_payment_types" class="form-control" value="{{ $values['additional_payment_types'] }}" placeholder="e.g. PayPal, Stripe, Store Credit">
                            </div>
                        </div>
                    </div>

                    <!-- Price Rules -->
                    <div class="tab-pane fade" id="price-rules" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="form-check form-switch mb-2">
                                    <input type="checkbox" name="disable_price_rules_dialog" class="form-check-input" value="1" @checked($values['disable_price_rules_dialog'] ?? false)>
                                    <label class="form-check-label">Disable Price Rules Dialog</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
            
            <div class="card-footer bg-white border-top text-end py-3">
                <button type="submit" class="btn btn-primary px-5 shadow-sm"><i class="bi bi-save me-2"></i>Save All Settings</button>
            </div>
        </div>
    </form>
</div>
</div>



    document.addEventListener('DOMContentLoaded', function() {
        const addExchangeRateBtn = document.getElementById('addExchangeRate');
        if (addExchangeRateBtn) {
            addExchangeRateBtn.addEventListener('click', function() {
                const tbody = document.querySelector('#exchangeRatesTable tbody');
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><input type="text" name="currency_exchange_rates_to[]" class="form-control"></td>
                    <td><input type="text" name="currency_exchange_rates_symbol[]" class="form-control"></td>
                    <td><input type="text" name="currency_exchange_rates_rate[]" class="form-control" value="1"></td>
                    <td>
                        <select name="currency_exchange_rates_symbol_location[]" class="form-select">
                            <option value="before">Before</option>
                            <option value="after">After</option>
                        </select>
                    </td>
                    <td><input type="number" name="currency_exchange_rates_number_of_decimals[]" class="form-control" value="2"></td>
                    <td><input type="text" name="currency_exchange_rates_thousands_separator[]" class="form-control"></td>
                    <td><input type="text" name="currency_exchange_rates_decimal_point[]" class="form-control"></td>
                    <td><button type="button" class="btn btn-sm btn-danger remove-rate"><i class="bi bi-trash"></i></button></td>
                `;
                tbody.appendChild(tr);
            });
        }

        document.querySelector('#exchangeRatesTable').addEventListener('click', function(e) {
            if (e.target.closest('.remove-rate')) {
                e.target.closest('tr').remove();
            }
        });
    });
</script>
@endsection
