@extends('layouts.app')

@section('page-title', 'Store Config')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/store-config.css') }}">
@endpush

@section('content')
    <div class="container-fluid" style="padding: 0;">
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
            
            <div class="sc-search-wrap" style="justify-content: flex-end; margin-bottom: 20px;">
                <button type="submit" class="sc-btn-save shadow-sm"><i class="bi bi-save me-2"></i>Save All Settings</button>
            </div>

            <div class="sc-tabs" id="scTabs">
                <button type="button" class="sc-tab active" data-tab="general">General Info</button>
                <button type="button" class="sc-tab" data-tab="taxes">Taxes</button>
                <button type="button" class="sc-tab" data-tab="localization">Localization</button>
                <button type="button" class="sc-tab" data-tab="sales">Sales & Receipts</button>
                <button type="button" class="sc-tab" data-tab="loyalty">Loyalty & Accounts</button>
                <button type="button" class="sc-tab" data-tab="barcodes">Barcodes</button>
                <button type="button" class="sc-tab" data-tab="advanced">Advanced</button>
                <button type="button" class="sc-tab" data-tab="currency">Currency</button>
                <button type="button" class="sc-tab" data-tab="payment-types">Payment Types</button>
                <button type="button" class="sc-tab" data-tab="price-rules">Price Rules</button>
                <button type="button" class="sc-tab" data-tab="theme">Theme</button>
            </div>

            <!-- General Info -->
            <div class="sc-tab-panel active" id="tab-general">
                <div class="sc-form-card">
                    <div class="sc-form-row">
                        <label class="sc-form-label">Company Name</label>
                        <input type="text" name="company" class="sc-form-control" value="{{ $values['company'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Company Logo</label>
                        <div class="sc-file-wrap w-100">
                            <input type="file" name="company_logo" class="sc-form-control">
                            @if($values['company_logo'])
                                <img src="{{ route('app_files.view', ['fileId' => $values['company_logo']]) }}" alt="Logo" style="max-height: 40px; margin-left: 15px;" class="border p-1 rounded">
                            @endif
                        </div>
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Website</label>
                        <input type="text" name="website" class="sc-form-control" value="{{ $values['website'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Email</label>
                        <input type="email" name="email" class="sc-form-control" value="{{ $values['email'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Phone</label>
                        <input type="text" name="phone" class="sc-form-control" value="{{ $values['phone'] }}">
                    </div>
                    <div class="sc-form-row" style="align-items: flex-start;">
                        <label class="sc-form-label">Address</label>
                        <textarea name="address" class="sc-form-control" rows="2">{{ $values['address'] }}</textarea>
                    </div>
                    <div class="sc-form-row" style="align-items: flex-start;">
                        <label class="sc-form-label">Return Policy</label>
                        <textarea name="return_policy" class="sc-form-control" rows="3">{{ $values['return_policy'] }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Taxes -->
            <div class="sc-tab-panel" id="tab-taxes">
                <div class="sc-form-card">
                    <div class="sc-form-row">
                        <label class="sc-form-label">Default Tax 1 Name</label>
                        <input type="text" name="default_tax_1_name" class="sc-form-control" value="{{ $values['default_tax_1_name'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Default Tax 1 Rate (%)</label>
                        <input type="text" name="default_tax_1_rate" class="sc-form-control" value="{{ $values['default_tax_1_rate'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Default Tax 2 Name</label>
                        <input type="text" name="default_tax_2_name" class="sc-form-control" value="{{ $values['default_tax_2_name'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Default Tax 2 Rate (%)</label>
                        <div style="display: flex; gap: 15px; align-items: center; width: 100%;">
                            <input type="text" name="default_tax_2_rate" class="sc-form-control" value="{{ $values['default_tax_2_rate'] }}">
                            <label class="sc-file-check" style="white-space: nowrap;">
                                <input type="checkbox" name="default_tax_2_cumulative" value="1" @checked($values['default_tax_2_cumulative'])> Cumulative
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Localization -->
            <div class="sc-tab-panel" id="tab-localization">
                <div class="sc-form-card">
                    <div class="sc-form-row">
                        <label class="sc-form-label">Date Format</label>
                        <input type="text" name="date_format" class="sc-form-control" value="{{ $values['date_format'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Time Format</label>
                        <input type="text" name="time_format" class="sc-form-control" value="{{ $values['time_format'] }}">
                    </div>
                </div>
            </div>

            <!-- Sales & Receipts -->
            <div class="sc-tab-panel" id="tab-sales">
                <div class="sc-receipt-card">
                    <div class="sc-receipt-row">
                        <label class="sc-receipt-label">Sale ID Prefix</label>
                        <input type="text" name="sale_prefix" class="sc-receipt-input" value="{{ $values['sale_prefix'] }}">
                    </div>
                    <div class="sc-receipt-row">
                        <label class="sc-receipt-label">Receipt Text Size</label>
                        <select name="receipt_text_size" class="sc-receipt-select">
                            <option value="small" @selected($values['receipt_text_size'] == 'small')>Small</option>
                            <option value="medium" @selected($values['receipt_text_size'] == 'medium')>Medium</option>
                            <option value="large" @selected($values['receipt_text_size'] == 'large')>Large</option>
                        </select>
                    </div>
                    <div class="sc-receipt-row">
                        <label class="sc-receipt-label">Automatically print receipt after sale</label>
                        <div><input type="checkbox" name="print_after_sale" class="sc-receipt-check" value="1" @checked($values['print_after_sale'])></div>
                    </div>
                    <div class="sc-receipt-row">
                        <label class="sc-receipt-label">Automatically email receipt to customer</label>
                        <div><input type="checkbox" name="automatically_email_receipt" class="sc-receipt-check" value="1" @checked($values['automatically_email_receipt'])></div>
                    </div>
                    <div class="sc-receipt-row">
                        <label class="sc-receipt-label">Hide signature line on receipts</label>
                        <div><input type="checkbox" name="hide_signature" class="sc-receipt-check" value="1" @checked($values['hide_signature'])></div>
                    </div>
                </div>
            </div>

            <!-- Loyalty & Accounts -->
            <div class="sc-tab-panel" id="tab-loyalty">
                <div class="sc-form-card">
                    <div class="sc-form-row">
                        <label class="sc-form-label fw-bold" style="color: var(--primary);">Enable Customer Loyalty System</label>
                        <input type="checkbox" name="enable_customer_loyalty_system" value="1" @checked($values['enable_customer_loyalty_system']) style="width: 18px; height: 18px;">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Point Value ($)</label>
                        <input type="text" name="point_value" class="sc-form-control" value="{{ $values['point_value'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Spend to Point Ratio</label>
                        <input type="text" name="spend_to_point_ratio" class="sc-form-control" value="{{ $values['spend_to_point_ratio'] }}" placeholder="e.g. 10:1">
                    </div>
                    <hr style="border-top: 1px solid var(--gray-200); margin: 20px 0;">
                    <div class="sc-form-row">
                        <label class="sc-form-label">Enable Customer Store Accounts</label>
                        <input type="checkbox" name="customers_store_accounts" value="1" @checked($values['customers_store_accounts']) style="width: 18px; height: 18px;">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Enable Supplier Store Accounts</label>
                        <input type="checkbox" name="suppliers_store_accounts" value="1" @checked($values['suppliers_store_accounts']) style="width: 18px; height: 18px;">
                    </div>
                </div>
            </div>

            <!-- Barcodes -->
            <div class="sc-tab-panel" id="tab-barcodes">
                <div class="sc-form-card">
                    <div class="sc-form-row">
                        <label class="sc-form-label">Barcode Type</label>
                        <select name="barcode_type" class="sc-form-control">
                            <option value="Code39" @selected($values['barcode_type'] == 'Code39')>Code 39</option>
                            <option value="Code128" @selected($values['barcode_type'] == 'Code128')>Code 128</option>
                            <option value="EAN13" @selected($values['barcode_type'] == 'EAN13')>EAN-13</option>
                        </select>
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Background Image</label>
                        <div class="sc-file-wrap w-100">
                            <input type="file" name="barcode_background" class="sc-form-control">
                            @if($values['barcode_background'])
                                <img src="{{ route('app_files.view', ['fileId' => $values['barcode_background']]) }}" alt="Background" style="max-height: 40px; margin-left: 15px;" class="border p-1 rounded">
                            @endif
                        </div>
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Sheet Label Background</label>
                        <div class="sc-file-wrap w-100">
                            <input type="file" name="label_sheet_background" class="sc-form-control">
                            @if($values['label_sheet_background'])
                                <img src="{{ route('app_files.view', ['fileId' => $values['label_sheet_background']]) }}" alt="Background" style="max-height: 40px; margin-left: 15px;" class="border p-1 rounded">
                            @endif
                        </div>
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Font Size</label>
                        <input type="number" name="barcode_font_size" class="sc-form-control" value="{{ $values['barcode_font_size'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Width</label>
                        <input type="number" name="barcode_width" class="sc-form-control" value="{{ $values['barcode_width'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Height</label>
                        <input type="number" name="barcode_height" class="sc-form-control" value="{{ $values['barcode_height'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Quality (1-100)</label>
                        <input type="number" name="barcode_quality" class="sc-form-control" value="{{ $values['barcode_quality'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Show company name on labels</label>
                        <input type="checkbox" name="show_barcode_company_name" value="1" @checked($values['show_barcode_company_name']) style="width: 18px; height: 18px;">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Hide barcode image on labels</label>
                        <input type="checkbox" name="hide_barcode_on_barcode_labels" value="1" @checked($values['hide_barcode_on_barcode_labels']) style="width: 18px; height: 18px;">
                    </div>
                </div>
            </div>

            <!-- Advanced -->
            <div class="sc-tab-panel" id="tab-advanced">
                <div class="sc-form-card">
                    <div class="sc-form-row">
                        <label class="sc-form-label">Session Expiration (seconds, 0 for browser close)</label>
                        <input type="number" name="phppos_session_expiration" class="sc-form-control" value="{{ $values['phppos_session_expiration'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Speed up search queries (optimized indexes)</label>
                        <input type="checkbox" name="speed_up_search_queries" value="1" @checked($values['speed_up_search_queries']) style="width: 18px; height: 18px;">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Enable UI sounds</label>
                        <input type="checkbox" name="enable_sounds" value="1" @checked($values['enable_sounds']) style="width: 18px; height: 18px;">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Hide statistics from dashboard</label>
                        <input type="checkbox" name="hide_dashboard_statistics" value="1" @checked($values['hide_dashboard_statistics']) style="width: 18px; height: 18px;">
                    </div>
                </div>
            </div>

            <!-- Currency -->
            <div class="sc-tab-panel" id="tab-currency">
                <div class="sc-currency-card">
                    <div class="sc-currency-grid" style="margin-bottom: 20px;">
                        <label class="sc-currency-label" for="currencySymbol">Currency Symbol:</label>
                        <input type="text" name="currency_symbol" class="sc-currency-control" id="currencySymbol" value="{{ $values['currency_symbol'] }}" />
                        <label class="sc-currency-label" for="currencyCode">Currency Code (ISO):</label>
                        <input type="text" name="currency_code" class="sc-currency-control" id="currencyCode" value="{{ $values['currency_code'] }}" />
                    </div>
                    
                    <div class="sc-currency-grid" style="margin-bottom: 20px;">
                        <label class="sc-currency-label" for="currencySymbolLocation">Symbol Location:</label>
                        <select name="currency_symbol_location" class="sc-currency-control" id="currencySymbolLocation">
                            <option value="before" @selected($values['currency_symbol_location'] == 'before')>Before Amount</option>
                            <option value="after" @selected($values['currency_symbol_location'] == 'after')>After Amount</option>
                        </select>
                        <label class="sc-currency-label" for="currencyDecimals">Decimals:</label>
                        <input type="number" name="number_of_decimals" class="sc-currency-control" id="currencyDecimals" value="{{ $values['number_of_decimals'] }}" />
                    </div>

                    <div class="sc-currency-table-wrap">
                        <table class="sc-currency-table" id="exchangeRatesTable">
                            <thead>
                                <tr>
                                    <th>Currency Code To</th>
                                    <th>Symbol</th>
                                    <th>Symbol Location</th>
                                    <th>Decimals</th>
                                    <th>Thousands Separator</th>
                                    <th>Decimal Point</th>
                                    <th>Exchange Rate</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($exchange_rates as $rate)
                                <tr>
                                    <td><input type="text" name="currency_exchange_rates_to[]" class="sc-currency-control" value="{{ $rate->currency_code_to }}"></td>
                                    <td><input type="text" name="currency_exchange_rates_symbol[]" class="sc-currency-control" value="{{ $rate->currency_symbol }}"></td>
                                    <td>
                                        <select name="currency_exchange_rates_symbol_location[]" class="sc-currency-control">
                                            <option value="before" @selected($rate->currency_symbol_location == 'before')>Before</option>
                                            <option value="after" @selected($rate->currency_symbol_location == 'after')>After</option>
                                        </select>
                                    </td>
                                    <td><input type="number" name="currency_exchange_rates_number_of_decimals[]" class="sc-currency-control" value="{{ $rate->number_of_decimals }}"></td>
                                    <td><input type="text" name="currency_exchange_rates_thousands_separator[]" class="sc-currency-control" value="{{ $rate->thousands_separator }}"></td>
                                    <td><input type="text" name="currency_exchange_rates_decimal_point[]" class="sc-currency-control" value="{{ $rate->decimal_point }}"></td>
                                    <td><input type="text" name="currency_exchange_rates_rate[]" class="sc-currency-control" value="{{ (float)$rate->exchange_rate }}"></td>
                                    <td><a class="sc-currency-link remove-rate" href="#" style="color: var(--danger);">Delete</a></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <a class="sc-currency-link" href="#" id="addExchangeRate">Add Currency Exchange Rate</a>
                    </div>
                </div>
            </div>

            <!-- Payment Types -->
            <div class="sc-tab-panel" id="tab-payment-types">
                <div class="sc-payment-card">
                    <div class="sc-payment-row">
                        <label class="sc-payment-label">Default Payment Type:</label>
                        <select name="default_payment_type" class="sc-payment-select">
                            <option value="Cash" @selected($values['default_payment_type'] == 'Cash')>Cash</option>
                            <option value="Check" @selected($values['default_payment_type'] == 'Check')>Check</option>
                            <option value="Gift Card" @selected($values['default_payment_type'] == 'Gift Card')>Gift Card</option>
                            <option value="Debit Card" @selected($values['default_payment_type'] == 'Debit Card')>Debit Card</option>
                            <option value="Credit Card" @selected($values['default_payment_type'] == 'Credit Card')>Credit Card</option>
                        </select>
                    </div>
                    <div class="sc-payment-row">
                        <label class="sc-payment-label">Additional Payment Types (Comma Separated):</label>
                        <input type="text" name="additional_payment_types" class="sc-payment-control" value="{{ $values['additional_payment_types'] }}" placeholder="e.g. PayPal, Stripe, Store Credit" />
                    </div>
                </div>
            </div>

            <!-- Price Rules -->
            <div class="sc-tab-panel" id="tab-price-rules">
                <div class="sc-price-rules-card">
                    <label class="sc-price-rules-row" for="disablePriceRulesDialog">
                        <span>Disable Price Rules Dialog:</span>
                        <input id="disablePriceRulesDialog" name="disable_price_rules_dialog" class="sc-price-rules-check" type="checkbox" value="1" @checked($values['disable_price_rules_dialog'] ?? false) />
                    </label>
                </div>
            </div>

            <!-- Theme -->
            <div class="sc-tab-panel" id="tab-theme">
                <div class="sc-form-card">
                    <h5 class="mb-4" style="color: var(--primary); font-weight: 700;">Location Theme Colors</h5>
                    <p class="text-muted small mb-4">Set the primary and secondary colors for each location. These colors will be applied when an employee logged into the location uses the system.</p>
                    
                    @foreach($locations as $location)
                    <div class="sc-form-row mb-4" style="border-bottom: 1px solid var(--gray-100); padding-bottom: 15px;">
                        <div style="flex: 1;">
                            <label class="sc-form-label fw-bold">{{ $location->name }}</label>
                            <div class="text-muted small">Location ID: {{ $location->location_id }}</div>
                        </div>
                        <div style="display: flex; gap: 20px; flex: 2;">
                            <div style="flex: 1;">
                                <label class="small text-muted d-block mb-1">Primary Color</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" name="locations_color[{{ $location->location_id }}]" class="form-control form-control-color" value="{{ $location->color ?? '#2563EB' }}" title="Choose primary color">
                                    <input type="text" class="sc-form-control text-uppercase" value="{{ $location->color ?? '#2563EB' }}" readonly style="width: 100px; font-family: monospace;">
                                </div>
                            </div>
                            <div style="flex: 1;">
                                <label class="small text-muted d-block mb-1">Secondary Color</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" name="locations_secondary_color[{{ $location->location_id }}]" class="form-control form-control-color" value="{{ $location->secondary_color ?? '#1E293B' }}" title="Choose secondary color">
                                    <input type="text" class="sc-form-control text-uppercase" value="{{ $location->secondary_color ?? '#1E293B' }}" readonly style="width: 100px; font-family: monospace;">
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </form>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('assets/js/store-config.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const addExchangeRateBtn = document.getElementById('addExchangeRate');
            if (addExchangeRateBtn) {
                addExchangeRateBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const tbody = document.querySelector('#exchangeRatesTable tbody');
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                                <td><input type="text" name="currency_exchange_rates_to[]" class="sc-currency-control"></td>
                                <td><input type="text" name="currency_exchange_rates_symbol[]" class="sc-currency-control"></td>
                                <td>
                                    <select name="currency_exchange_rates_symbol_location[]" class="sc-currency-control">
                                        <option value="before">Before</option>
                                        <option value="after">After</option>
                                    </select>
                                </td>
                                <td><input type="number" name="currency_exchange_rates_number_of_decimals[]" class="sc-currency-control" value="2"></td>
                                <td><input type="text" name="currency_exchange_rates_thousands_separator[]" class="sc-currency-control" value=","></td>
                                <td><input type="text" name="currency_exchange_rates_decimal_point[]" class="sc-currency-control" value="."></td>
                                <td><input type="text" name="currency_exchange_rates_rate[]" class="sc-currency-control" value="1"></td>
                                <td><a class="sc-currency-link remove-rate" href="#" style="color: var(--danger);">Delete</a></td>
                            `;
                    tbody.appendChild(tr);
                });
            }

            document.querySelector('#exchangeRatesTable').addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-rate')) {
                    e.preventDefault();
                    e.target.closest('tr').remove();
                }
            });
            
            // Tab switching logic (can also be handled by store-config.js, but let's be safe)
            const tabs = document.querySelectorAll('.sc-tab');
            const panels = document.querySelectorAll('.sc-tab-panel');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Remove active from all tabs
                    tabs.forEach(t => t.classList.remove('active'));
                    // Hide all panels
                    panels.forEach(p => p.classList.remove('active'));

                    // Add active to clicked
                    tab.classList.add('active');
                    // Show target panel
                    const targetId = 'tab-' + tab.getAttribute('data-tab');
                    const targetPanel = document.getElementById(targetId);
                    if(targetPanel) {
                        targetPanel.classList.add('active');
                    }
                });
            });
        });
    </script>
@endpush