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

        <form id="storeConfigForm" method="post" action="{{ route('config.update') }}" enctype="multipart/form-data">
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
                <button type="button" class="sc-tab" data-tab="sales-module">Sales Module</button>
                <button type="button" class="sc-tab" data-tab="items-module">Items Module</button>
                <button type="button" class="sc-tab" data-tab="price-tiers">Price Tiers</button>
                <button type="button" class="sc-tab" data-tab="ecommerce">Ecommerce Platform</button>
                <button type="button" class="sc-tab" data-tab="app-settings">Application Settings</button>
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
                    <h5 class="mb-3" style="color:var(--primary);font-weight:700;">Tax Settings</h5>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Tax ID</label>
                        <input type="text" name="tax_id" class="sc-form-control" value="{{ $values['tax_id'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Prices Include Tax</label>
                        <input type="checkbox" name="prices_include_tax" value="1" @checked($values['prices_include_tax']) style="width:18px;height:18px;">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Charge Tax on Receivings</label>
                        <input type="checkbox" name="charge_tax_on_recv" value="1" @checked($values['charge_tax_on_recv']) style="width:18px;height:18px;">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Discount Tax for Flat Discounts</label>
                        <input type="checkbox" name="flat_discounts_discount_tax" value="1" @checked($values['flat_discounts_discount_tax']) style="width:18px;height:18px;">
                    </div>
                </div>

                <div class="sc-form-card" style="margin-top:20px;">
                    <h5 class="mb-3" style="color:var(--primary);font-weight:700;">Tax Classes</h5>
                    <p class="text-muted small mb-3">Tax classes define grouped rates used by items, item kits, customers, suppliers, sales, and receivings.</p>
                    <div class="table-responsive">
                        <table id="taxClassesTable" class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 18%;">Class Name</th>
                                    <th>Tax Rates</th>
                                    <th style="width: 10%;" class="text-center">Default</th>
                                    <th style="width: 12%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($taxClasses as $taxClass)
                                    <tr data-tax-class-id="{{ $taxClass->id }}">
                                        <td>
                                            <input type="text" name="tax_classes[{{ $taxClass->id }}][name]" class="sc-form-control" value="{{ $taxClass->name }}">
                                        </td>
                                        <td>
                                            <table class="table table-sm mb-2 tax-rate-table">
                                                <thead>
                                                    <tr>
                                                        <th>Tax Name</th>
                                                        <th>Percent (%)</th>
                                                        <th class="text-center">Cumulative</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($taxClass->taxes as $rateIndex => $taxRate)
                                                        <tr data-rate-index="{{ $rateIndex }}">
                                                            <td>
                                                                <input type="text" name="taxes[{{ $taxClass->id }}][name][{{ $rateIndex }}]" class="sc-form-control" value="{{ $taxRate->name }}">
                                                                <input type="hidden" name="taxes[{{ $taxClass->id }}][tax_class_tax_id][{{ $rateIndex }}]" value="{{ $taxRate->id }}">
                                                            </td>
                                                            <td>
                                                                <input type="text" name="taxes[{{ $taxClass->id }}][percent][{{ $rateIndex }}]" class="sc-form-control" value="{{ $taxRate->percent }}">
                                                            </td>
                                                            <td class="text-center">
                                                                <input type="hidden" name="taxes[{{ $taxClass->id }}][cumulative][{{ $rateIndex }}]" value="0">
                                                                <input type="checkbox" name="taxes[{{ $taxClass->id }}][cumulative][{{ $rateIndex }}]" value="1" @checked($taxRate->cumulative)>
                                                            </td>
                                                            <td>
                                                                <a href="#" class="remove-tax-rate" style="color:var(--danger);">Delete</a>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr data-rate-index="0">
                                                            <td>
                                                                <input type="text" name="taxes[{{ $taxClass->id }}][name][0]" class="sc-form-control" value="">
                                                                <input type="hidden" name="taxes[{{ $taxClass->id }}][tax_class_tax_id][0]" value="">
                                                            </td>
                                                            <td>
                                                                <input type="text" name="taxes[{{ $taxClass->id }}][percent][0]" class="sc-form-control" value="">
                                                            </td>
                                                            <td class="text-center">
                                                                <input type="hidden" name="taxes[{{ $taxClass->id }}][cumulative][0]" value="0">
                                                                <input type="checkbox" name="taxes[{{ $taxClass->id }}][cumulative][0]" value="1">
                                                            </td>
                                                            <td>
                                                                <a href="#" class="remove-tax-rate" style="color:var(--danger);">Delete</a>
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                            <a href="#" class="add-tax-rate">Add Rate</a>
                                        </td>
                                        <td class="text-center">
                                            <input type="radio" name="tax_class_id" value="{{ $taxClass->id }}" @checked((string) $values['tax_class_id'] === (string) $taxClass->id)>
                                        </td>
                                        <td>
                                            <a href="#" class="remove-tax-class" style="color:var(--danger);">Delete Class</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="text-muted" data-empty-row>
                                        <td colspan="4">No tax classes defined yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <a href="#" id="addTaxClass">Add Tax Class</a>
                </div>

                <div class="sc-form-card" style="margin-top:20px;">
                    <h5 class="mb-3" style="color:var(--primary);font-weight:700;">Default Taxes</h5>
                    @if(!empty($values['tax_class_id']))
                        <div class="alert alert-light border" style="margin-bottom: 16px;">
                            Default taxes apply only when no tax class is selected.
                        </div>
                    @endif
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

                    <div id="moreDefaultTaxes" style="display: {{ $values['default_tax_3_rate'] ? 'block' : 'none' }};">
                        <div class="sc-form-row">
                            <label class="sc-form-label">Default Tax 3 Name</label>
                            <input type="text" name="default_tax_3_name" class="sc-form-control" value="{{ $values['default_tax_3_name'] }}">
                        </div>
                        <div class="sc-form-row">
                            <label class="sc-form-label">Default Tax 3 Rate (%)</label>
                            <input type="text" name="default_tax_3_rate" class="sc-form-control" value="{{ $values['default_tax_3_rate'] }}">
                        </div>
                        <div class="sc-form-row">
                            <label class="sc-form-label">Default Tax 4 Name</label>
                            <input type="text" name="default_tax_4_name" class="sc-form-control" value="{{ $values['default_tax_4_name'] }}">
                        </div>
                        <div class="sc-form-row">
                            <label class="sc-form-label">Default Tax 4 Rate (%)</label>
                            <input type="text" name="default_tax_4_rate" class="sc-form-control" value="{{ $values['default_tax_4_rate'] }}">
                        </div>
                        <div class="sc-form-row">
                            <label class="sc-form-label">Default Tax 5 Name</label>
                            <input type="text" name="default_tax_5_name" class="sc-form-control" value="{{ $values['default_tax_5_name'] }}">
                        </div>
                        <div class="sc-form-row">
                            <label class="sc-form-label">Default Tax 5 Rate (%)</label>
                            <input type="text" name="default_tax_5_rate" class="sc-form-control" value="{{ $values['default_tax_5_rate'] }}">
                        </div>
                    </div>
                    <a href="#" id="toggleMoreTaxes" style="display: {{ $values['default_tax_3_rate'] ? 'none' : 'inline' }};">Show more tax rates</a>
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
                            <option value="extra_large" @selected($values['receipt_text_size'] == 'extra_large')>Extra large</option>
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

            <!-- Currency (order matches legacy config: symbol/code → exchange rates → base formatting → denominations) -->
            <div class="sc-tab-panel" id="tab-currency">
                <input type="hidden" name="config_exchange_rates_sync" value="1">
                <div class="sc-currency-card">
                    <div class="sc-currency-grid" style="margin-bottom: 20px;">
                        <label class="sc-currency-label" for="currencySymbol">Currency Symbol</label>
                        <input type="text" name="currency_symbol" class="sc-currency-control" id="currencySymbol" value="{{ $values['currency_symbol'] }}" />
                        <label class="sc-currency-label" for="currencyCode">Currency Code (ISO)</label>
                        <input type="text" name="currency_code" class="sc-currency-control" id="currencyCode" value="{{ $values['currency_code'] }}" />
                    </div>

                    <h6 class="text-muted mb-2" style="font-size: 0.85rem; font-weight: 600;">Currency exchange rates</h6>
                    <div class="sc-currency-table-wrap mb-4">
                        <table class="sc-currency-table" id="exchangeRatesTable">
                            <thead>
                                <tr>
                                    <th>Exchange to</th>
                                    <th>Symbol</th>
                                    <th>Symbol location</th>
                                    <th>Decimals</th>
                                    <th>Thousands separator</th>
                                    <th>Decimal point</th>
                                    <th>Exchange rate</th>
                                    <th></th>
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
                                    <td>
                                        <select name="currency_exchange_rates_number_of_decimals[]" class="sc-currency-control">
                                            <option value="" @selected((string) $rate->number_of_decimals === '')>Let system decide</option>
                                            @foreach (range(0, 5) as $d)
                                                <option value="{{ $d }}" @selected((string) $rate->number_of_decimals === (string) $d)>{{ $d }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="currency_exchange_rates_thousands_separator[]" class="sc-currency-control" value="{{ $rate->thousands_separator }}"></td>
                                    <td><input type="text" name="currency_exchange_rates_decimal_point[]" class="sc-currency-control" value="{{ $rate->decimal_point }}"></td>
                                    <td><input type="text" name="currency_exchange_rates_rate[]" class="sc-currency-control" value="{{ $rate->exchange_rate }}"></td>
                                    <td><a class="sc-currency-link remove-rate" href="#" style="color: var(--danger);">Delete</a></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <a class="sc-currency-link" href="#" id="addExchangeRate">Add currency exchange rate</a>
                    </div>

                    <div class="sc-currency-grid" style="margin-bottom: 20px;">
                        <label class="sc-currency-label" for="currencySymbolLocation">Base symbol location</label>
                        <select name="currency_symbol_location" class="sc-currency-control" id="currencySymbolLocation">
                            <option value="before" @selected($values['currency_symbol_location'] == 'before')>Before amount</option>
                            <option value="after" @selected($values['currency_symbol_location'] == 'after')>After amount</option>
                        </select>
                        <label class="sc-currency-label" for="currencyDecimals">Base number of decimals</label>
                        <select name="number_of_decimals" class="sc-currency-control" id="currencyDecimals">
                            <option value="" @selected((string) $values['number_of_decimals'] === '')>Let system decide</option>
                            @foreach (range(0, 5) as $d)
                                <option value="{{ $d }}" @selected((string) $values['number_of_decimals'] === (string) $d)>{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sc-currency-grid" style="margin-bottom: 24px;">
                        <label class="sc-currency-label" for="thousandsSeparator">Thousands separator</label>
                        <input type="text" name="thousands_separator" class="sc-currency-control" id="thousandsSeparator" value="{{ $values['thousands_separator'] !== '' && $values['thousands_separator'] !== null ? $values['thousands_separator'] : ',' }}" maxlength="8" />
                        <label class="sc-currency-label" for="decimalPoint">Decimal point</label>
                        <input type="text" name="decimal_point" class="sc-currency-control" id="decimalPoint" value="{{ $values['decimal_point'] !== '' && $values['decimal_point'] !== null ? $values['decimal_point'] : '.' }}" maxlength="8" />
                    </div>

                    <h6 class="text-muted mb-2" style="font-size: 0.85rem; font-weight: 600;">Register currency denominations</h6>
                    <p class="small text-muted mb-3">Cash denominations for the register (e.g. bills and coins), matching the legacy Store Config currency section.</p>
                    <div class="sc-currency-table-wrap">
                        <table class="sc-currency-table" id="currencyDenomsTable">
                            <thead>
                                <tr>
                                    <th>Denomination</th>
                                    <th>Value</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($currency_denoms as $denom)
                                <tr>
                                    <td><input type="text" name="currency_denoms_name[]" class="sc-currency-control" value="{{ $denom->name }}"></td>
                                    <td><input type="text" name="currency_denoms_value[]" class="sc-currency-control" value="{{ $denom->value }}"></td>
                                    <td>
                                        <a class="sc-currency-link remove-denom" href="#" style="color: var(--danger);" data-id="{{ $denom->id }}">Delete</a>
                                        <input type="hidden" name="currency_denoms_ids[]" value="{{ $denom->id }}" />
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <a class="sc-currency-link" href="#" id="addDenom">Add denomination</a>
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
                        <input id="disablePriceRulesDialog" name="disable_price_rules_dialog" class="sc-price-rules-check" type="checkbox" value="1" @checked($values['disable_price_rules_dialog']) />
                    </label>
                </div>
            </div>

            <!-- Sales Module -->
            <div class="sc-tab-panel" id="tab-sales-module">
                <div class="sc-form-card">
                    <h5 class="mb-4" style="color:var(--primary);font-weight:700;">Sales Module Settings</h5>

                    <div class="sc-form-row">
                        <label class="sc-form-label">Sale ID Prefix</label>
                        <input type="text" name="sale_prefix" class="sc-form-control" value="{{ $values['sale_prefix'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">ID to Show on Sale Interface</label>
                        <select name="id_to_show_on_sale_interface" class="sc-form-control">
                            <option value="number" @selected($values['id_to_show_on_sale_interface']=='number')>Item Number</option>
                            <option value="product_id" @selected($values['id_to_show_on_sale_interface']=='product_id')>Product ID</option>
                            <option value="id" @selected($values['id_to_show_on_sale_interface']=='id')>Item ID</option>
                        </select>
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Number of Recent Sales</label>
                        <select name="number_of_recent_sales" class="sc-form-control">
                            @foreach(['10','20','50','100','200','500'] as $n)
                                <option value="{{ $n }}" @selected($values['number_of_recent_sales']==$n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Damaged Reasons (comma-separated)</label>
                        <input type="text" name="damaged_reasons" class="sc-form-control" value="{{ $values['damaged_reasons'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Commission Default Rate (%)</label>
                        <input type="text" name="commission_default_rate" class="sc-form-control" value="{{ $values['commission_default_rate'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Commission Percent Type</label>
                        <select name="commission_percent_type" class="sc-form-control">
                            <option value="selling_price" @selected($values['commission_percent_type']=='selling_price')>Unit Price</option>
                            <option value="profit" @selected($values['commission_percent_type']=='profit')>Profit</option>
                        </select>
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Default Sales Person</label>
                        <select name="default_sales_person" class="sc-form-control">
                            <option value="logged_in_employee" @selected($values['default_sales_person']=='logged_in_employee')>Logged In Employee</option>
                            <option value="not_set" @selected($values['default_sales_person']=='not_set')>Not Set</option>
                        </select>
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Default Type for Grid</label>
                        <select name="default_type_for_grid" class="sc-form-control">
                            <option value="categories" @selected($values['default_type_for_grid']=='categories')>Categories</option>
                            <option value="tags" @selected($values['default_type_for_grid']=='tags')>Tags</option>
                            <option value="suppliers" @selected($values['default_type_for_grid']=='suppliers')>Suppliers</option>
                            <option value="favorites" @selected($values['default_type_for_grid']=='favorites')>Favorites</option>
                        </select>
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Averaging Method</label>
                        <select name="averaging_method" class="sc-form-control">
                            <option value="moving_average" @selected($values['averaging_method']=='moving_average')>Moving Average</option>
                            <option value="historical_average" @selected($values['averaging_method']=='historical_average')>Historical Average</option>
                            <option value="dont_average" @selected($values['averaging_method']=='dont_average')>Don't Average (Use Current Recv Price)</option>
                        </select>
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">QR Code Format</label>
                        <select name="qr_code_format" class="sc-form-control">
                            <option value="link_to_receipt" @selected($values['qr_code_format']=='link_to_receipt')>Link to Receipt</option>
                            <option value="sale_summary_info" @selected($values['qr_code_format']=='sale_summary_info')>Sale Summary Info</option>
                            <option value="saudi_arabia_digital_receipt" @selected($values['qr_code_format']=='saudi_arabia_digital_receipt')>Saudi Arabia Digital Receipt</option>
                        </select>
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Decimals on Sales Interface</label>
                        <select name="number_of_decimals_displayed_on_sales_interface" class="sc-form-control">
                            <option value="" @selected($values['number_of_decimals_displayed_on_sales_interface']=='')>Let System Decide</option>
                            @foreach(range(0,10) as $d)
                                <option value="{{ $d }}" @selected((string)$values['number_of_decimals_displayed_on_sales_interface']==(string)$d)>{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Amount of Cash Left in Drawer at Closing</label>
                        <input type="text" name="amount_of_cash_to_be_left_in_drawer_at_closing" class="sc-form-control" value="{{ $values['amount_of_cash_to_be_left_in_drawer_at_closing'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Cash Alert High</label>
                        <input type="text" name="cash_alert_high" class="sc-form-control" value="{{ $values['cash_alert_high'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Cash Alert Low</label>
                        <input type="text" name="cash_alert_low" class="sc-form-control" value="{{ $values['cash_alert_low'] }}">
                    </div>

                    <hr style="margin:20px 0;border-color:var(--gray-200)">
                    <h6 style="color:var(--primary);font-weight:600;margin-bottom:12px;">Checkout &amp; Discounts</h6>
                    @foreach([
                        ['disable_confirmation_sale','Disable Sale Confirmation Dialog'],
                        ['disable_confirm_recv','Disable Receiving Confirmation'],
                        ['disable_quick_complete_sale','Disable Quick Complete Sale'],
                        ['disable_sale_cloning','Disable Sale Cloning'],
                        ['disable_recv_cloning','Disable Receiving Cloning'],
                        ['disable_discounts_percentage_per_line_item','Disable % Discount Per Line Item'],
                        ['disabled_fixed_discounts','Disable Fixed Discounts'],
                        ['disable_discount_by_percentage','Disable Discount By Percentage'],
                        ['do_not_allow_below_cost','Do Not Allow Selling Below Cost'],
                        ['do_not_allow_out_of_stock_items_to_be_sold','Do Not Allow Out of Stock Items to be Sold'],
                        ['do_not_allow_items_to_go_out_of_stock_when_transfering','Do Not Allow Items to Go Out of Stock When Transferring'],
                        ['do_not_allow_item_with_variations_to_be_sold_without_selecting_variation','Require Variation Selection Before Selling'],
                        ['do_not_allow_sales_with_zero_value','Do Not Allow Sales with Zero Value'],
                        ['do_not_allow_edit_of_overall_subtotal','Do Not Allow Edit of Overall Subtotal'],
                        ['do_not_group_same_items','Do Not Group Same Items in Cart'],
                    ] as [$key, $label])
                    <div class="sc-form-row">
                        <label class="sc-form-label">{{ $label }}</label>
                        <input type="checkbox" name="{{ $key }}" value="1" @checked($values[$key]) style="width:18px;height:18px;">
                    </div>
                    @endforeach

                    <hr style="margin:20px 0;border-color:var(--gray-200)">
                    <h6 style="color:var(--primary);font-weight:600;margin-bottom:12px;">Interface &amp; Grid</h6>
                    @foreach([
                        ['always_show_item_grid','Always Show Item Grid'],
                        ['hide_images_in_grid','Hide Images in Grid'],
                        ['hide_out_of_stock_grid','Hide Out of Stock Items in Grid'],
                        ['quick_variation_grid','Quick Variation Grid'],
                        ['hide_categories_sales_grid','Hide Categories in Sales Grid'],
                        ['hide_tags_sales_grid','Hide Tags in Sales Grid'],
                        ['hide_suppliers_sales_grid','Hide Suppliers in Sales Grid'],
                        ['hide_favorites_sales_grid','Hide Favorites in Sales Grid'],
                        ['hide_supplier_on_sales_interface','Hide Supplier on Sales Interface'],
                        ['hide_supplier_on_recv_interface','Hide Supplier on Receiving Interface'],
                        ['disable_supplier_selection_on_sales_interface','Disable Supplier Selection on Sales Interface'],
                        ['allow_drag_drop_sale','Allow Drag &amp; Drop in Sale'],
                        ['allow_drag_drop_recv','Allow Drag &amp; Drop in Receiving'],
                        ['always_put_last_added_item_on_top_of_cart','Last Added Item on Top of Cart'],
                        ['scan_and_set_sales','Scan and Set Quantity (Sales)'],
                        ['scan_and_set_recv','Scan and Set Quantity (Receiving)'],
                        ['collapse_sales_ui_by_default','Collapse Sales UI by Default'],
                        ['collapse_recv_ui_by_default','Collapse Receiving UI by Default'],
                        ['auto_focus_on_item_after_sale_and_receiving','Auto Focus on Item Field After Sale/Receiving'],
                        ['edit_item_price_if_zero_after_adding','Edit Item Price if Zero After Adding'],
                        ['remind_customer_facing_display','Remind Customer Facing Display'],
                    ] as [$key, $label])
                    <div class="sc-form-row">
                        <label class="sc-form-label">{!! $label !!}</label>
                        <input type="checkbox" name="{{ $key }}" value="1" @checked($values[$key]) style="width:18px;height:18px;">
                    </div>
                    @endforeach

                    <hr style="margin:20px 0;border-color:var(--gray-200)">
                    <h6 style="color:var(--primary);font-weight:600;margin-bottom:12px;">Customer &amp; Sales Person</h6>
                    @foreach([
                        ['require_customer_for_sale','Require Customer for Sale'],
                        ['hide_customer_recent_sales','Hide Customer Recent Sales'],
                        ['enable_customer_quick_add','Enable Customer Quick Add'],
                        ['enable_supplier_quick_add','Enable Supplier Quick Add'],
                        ['select_sales_person_during_sale','Select Sales Person During Sale'],
                        ['capture_internal_notes_during_sale','Capture Internal Notes During Sale'],
                        ['capture_sig_for_all_payments','Capture Signature for All Payments'],
                        ['disable_sale_notifications','Disable Sale Notifications'],
                        ['confirm_error_adding_item','Confirm Error Messages (Modal)'],
                        ['change_sale_date_for_new_sale','Change Sale Date for New Sale'],
                        ['prompt_amount_for_cash_sale','Prompt Amount for Cash Sale'],
                    ] as [$key, $label])
                    <div class="sc-form-row">
                        <label class="sc-form-label">{{ $label }}</label>
                        <input type="checkbox" name="{{ $key }}" value="1" @checked($values[$key]) style="width:18px;height:18px;">
                    </div>
                    @endforeach

                    <hr style="margin:20px 0;border-color:var(--gray-200)">
                    <h6 style="color:var(--primary);font-weight:600;margin-bottom:12px;">Tips, QR &amp; Gift Cards</h6>
                    @foreach([
                        ['enable_tips','Enable Tips'],
                        ['tip_preset_zero','Tip Preset to Zero'],
                        ['show_qr_code_for_sale','Show QR Code for Sale'],
                        ['disable_verification_for_qr_codes','Disable Verification for QR Codes'],
                        ['hide_available_giftcards','Hide Available Gift Cards'],
                        ['show_giftcards_even_if_0_balance','Show Gift Cards Even if $0 Balance'],
                        ['disable_giftcard_detection','Disable Gift Card Detection'],
                        ['do_not_show_closing','Do Not Show Closing'],
                    ] as [$key, $label])
                    <div class="sc-form-row">
                        <label class="sc-form-label">{{ $label }}</label>
                        <input type="checkbox" name="{{ $key }}" value="1" @checked($values[$key]) style="width:18px;height:18px;">
                    </div>
                    @endforeach

                    <hr style="margin:20px 0;border-color:var(--gray-200)">
                    <h6 style="color:var(--primary);font-weight:600;margin-bottom:12px;">Cost &amp; Receiving</h6>
                    @foreach([
                        ['calculate_average_cost_price_from_receivings','Calculate Average Cost Price from Receivings'],
                        ['update_cost_price_on_transfer','Update Cost Price on Transfer'],
                        ['require_supplier_for_recv','Require Supplier for Receiving'],
                        ['track_shipping_cost_recv','Track Shipping Cost for Receivings'],
                        ['hide_suspended_recv_in_reports','Hide Suspended Receivings in Reports'],
                    ] as [$key, $label])
                    <div class="sc-form-row">
                        <label class="sc-form-label">{{ $label }}</label>
                        <input type="checkbox" name="{{ $key }}" value="1" @checked($values[$key]) style="width:18px;height:18px;">
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Items Module -->
            <div class="sc-tab-panel" id="tab-items-module">
                <div class="sc-form-card">
                    <h5 class="mb-4" style="color:var(--primary);font-weight:700;">Items Module Settings</h5>

                    <div class="sc-form-row">
                        <label class="sc-form-label">Items Per Page</label>
                        <select name="number_of_items_per_page" class="sc-form-control">
                            @foreach(['20','50','100','200','500'] as $n)
                                <option value="{{ $n }}" @selected($values['number_of_items_per_page']==$n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Items Per Search Suggestions</label>
                        <select name="items_per_search_suggestions" class="sc-form-control">
                            @foreach(['20','50','100','200','500'] as $n)
                                <option value="{{ $n }}" @selected($values['items_per_search_suggestions']==$n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Items in Grid</label>
                        <select name="number_of_items_in_grid" class="sc-form-control">
                            @foreach(range(1,50) as $n)
                                <option value="{{ $n }}" @selected($values['number_of_items_in_grid']==$n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Default Reorder Level (New Items)</label>
                        <input type="text" name="default_reorder_level_when_creating_items" class="sc-form-control" value="{{ $values['default_reorder_level_when_creating_items'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Default Days to Expire (New Items)</label>
                        <input type="text" name="default_days_to_expire_when_creating_items" class="sc-form-control" value="{{ $values['default_days_to_expire_when_creating_items'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Max Discount % Allowed</label>
                        <input type="text" name="max_discount_percent" class="sc-form-control" value="{{ $values['max_discount_percent'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Default Age to Verify</label>
                        <input type="text" name="default_age_to_verify" class="sc-form-control" value="{{ $values['default_age_to_verify'] }}">
                    </div>

                    <hr style="margin:20px 0;border-color:var(--gray-200)">
                    <h6 style="color:var(--primary);font-weight:600;margin-bottom:12px;">Item Behavior</h6>
                    @foreach([
                        ['default_new_items_to_service','Default New Items to Service'],
                        ['highlight_low_inventory_items_in_items_module','Highlight Low Inventory Items'],
                        ['limit_manual_price_adj','Limit Manual Price Adjustments'],
                        ['enable_markup_calculator','Enable Markup Calculator'],
                        ['enable_margin_calculator','Enable Margin Calculator'],
                        ['verify_age_for_products','Verify Age for Products'],
                        ['strict_age_format_check','Strict Age Format Check'],
                        ['hide_supplier_in_item_search_result','Hide Supplier in Item Search Results'],
                        ['hide_supplier_from_item_popup','Hide Supplier from Item Popup'],
                        ['easy_item_clone_button','Easy Item Clone Button'],
                        ['add_ck_editor_to_item','Add CKEditor to Item Description'],
                    ] as [$key, $label])
                    <div class="sc-form-row">
                        <label class="sc-form-label">{{ $label }}</label>
                        <input type="checkbox" name="{{ $key }}" value="1" @checked($values[$key]) style="width:18px;height:18px;">
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Price Tiers -->
            <div class="sc-tab-panel" id="tab-price-tiers">
                <div class="sc-form-card">
                    <h5 class="mb-4" style="color:var(--primary);font-weight:700;">Price Tiers</h5>
                    <p class="text-muted small mb-3">Define customer price tiers. Each tier can have a default % off, cost + %, or cost + fixed amount applied to items.</p>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered" id="price_tiers_table" style="font-size:0.9rem;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:30%">Tier Name</th>
                                    <th>Default % Off</th>
                                    <th>Default Cost + %</th>
                                    <th>Default Cost + Fixed</th>
                                    <th style="width:80px">Delete</th>
                                </tr>
                            </thead>
                            <tbody id="price_tiers_tbody">
                                @foreach($price_tiers as $tier)
                                <tr data-tier-id="{{ $tier->id }}">
                                    <td><input type="text" class="sc-form-control" name="tiers_to_edit[{{ $tier->id }}][name]" value="{{ $tier->name }}"></td>
                                    <td><input type="text" class="sc-form-control" name="tiers_to_edit[{{ $tier->id }}][default_percent_off]" value="{{ $tier->default_percent_off !== null ? $tier->default_percent_off + 0 : '' }}"></td>
                                    <td><input type="text" class="sc-form-control" name="tiers_to_edit[{{ $tier->id }}][default_cost_plus_percent]" value="{{ $tier->default_cost_plus_percent !== null ? $tier->default_cost_plus_percent + 0 : '' }}"></td>
                                    <td><input type="text" class="sc-form-control" name="tiers_to_edit[{{ $tier->id }}][default_cost_plus_fixed_amount]" value="{{ $tier->default_cost_plus_fixed_amount !== null ? $tier->default_cost_plus_fixed_amount + 0 : '' }}"></td>
                                    <td class="text-center">
                                        <a href="#" class="delete_tier text-danger" data-tier-id="{{ $tier->id }}">Delete</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <a href="#" id="add_tier" class="sc-currency-link">+ Add Price Tier</a>
                    </div>

                    <hr style="margin:20px 0;border-color:var(--gray-200)">
                    <h6 style="color:var(--primary);font-weight:600;margin-bottom:12px;">Tier Settings</h6>

                    <div class="sc-form-row">
                        <label class="sc-form-label">Override Tier Name</label>
                        <input type="text" name="override_tier_name" class="sc-form-control" value="{{ $values['override_tier_name'] }}" placeholder="e.g. Customer Level">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Default % Type for Excel Import</label>
                        <select name="default_tier_percent_type_for_excel_import" class="sc-form-control">
                            <option value="percent_off" @selected($values['default_tier_percent_type_for_excel_import']=='percent_off')>Percent Off</option>
                            <option value="cost_plus_percent" @selected($values['default_tier_percent_type_for_excel_import']=='cost_plus_percent')>Cost Plus Percent</option>
                        </select>
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Default Fixed Type for Excel Import</label>
                        <select name="default_tier_fixed_type_for_excel_import" class="sc-form-control">
                            <option value="fixed_amount" @selected($values['default_tier_fixed_type_for_excel_import']=='fixed_amount')>Fixed Amount</option>
                            <option value="cost_plus_fixed_amount" @selected($values['default_tier_fixed_type_for_excel_import']=='cost_plus_fixed_amount')>Cost Plus Fixed Amount</option>
                        </select>
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Hide Tier on Receipt</label>
                        <input type="checkbox" name="hide_tier_on_receipt" value="1" @checked($values['hide_tier_on_receipt']) style="width:18px;height:18px;">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Round Tier Prices to 2 Decimals</label>
                        <input type="checkbox" name="round_tier_prices_to_2_decimals" value="1" @checked($values['round_tier_prices_to_2_decimals']) style="width:18px;height:18px;">
                    </div>
                </div>
            </div>

            <!-- Ecommerce Platform -->
            <div class="sc-tab-panel" id="tab-ecommerce">
                <div class="sc-form-card">
                    <h5 class="mb-4" style="color:var(--primary);font-weight:700;">Ecommerce Platform</h5>

                    <div class="sc-form-row">
                        <label class="sc-form-label">Ecommerce Platform</label>
                        <select name="ecommerce_platform" id="ecommerce_platform_select" class="sc-form-control">
                            <option value="" @selected(!$values['ecommerce_platform'])>None</option>
                            <option value="woocommerce" @selected($values['ecommerce_platform']=='woocommerce')>WooCommerce</option>
                            <option value="shopify" @selected($values['ecommerce_platform']=='shopify')>Shopify</option>
                        </select>
                    </div>

                    <div class="sc-form-row">
                        <label class="sc-form-label">SKU Sync Field</label>
                        <select name="sku_sync_field" class="sc-form-control">
                            <option value="item_number" @selected($values['sku_sync_field']=='item_number')>Item Number</option>
                            <option value="product_id" @selected($values['sku_sync_field']=='product_id')>Product ID</option>
                            <option value="item_id" @selected($values['sku_sync_field']=='item_id')>Item ID</option>
                        </select>
                    </div>

                    <div class="sc-form-row">
                        <label class="sc-form-label">Store Location (Ecommerce)</label>
                        <select name="ecom_store_location" class="sc-form-control">
                            <option value="">-- Select --</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->location_id }}" @selected($values['ecom_store_location']==$loc->location_id)>{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if($price_tiers->count() > 1)
                    <div class="sc-form-row">
                        <label class="sc-form-label">Online Price Tier</label>
                        <select name="online_price_tier" class="sc-form-control">
                            <option value="">-- Default --</option>
                            @foreach($price_tiers as $tier)
                                <option value="{{ $tier->id }}" @selected($values['online_price_tier']==$tier->id)>{{ $tier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <hr style="margin:20px 0;border-color:var(--gray-200)">
                    <h6 style="color:var(--primary);font-weight:600;margin-bottom:12px;">Sync Options</h6>

                    @foreach([
                        ['do_not_upload_images_to_ecommerce','Do Not Upload Images to Ecommerce'],
                        ['ecommerce_only_sync_completed_orders','Only Sync Completed Orders'],
                        ['import_ecommerce_orders_suspended','Import Ecommerce Orders as Suspended'],
                        ['new_items_are_ecommerce_by_default','New Items are Ecommerce by Default'],
                        ['use_main_image_as_default_image_in_e_commerce','Use Main Image as Default in Ecommerce'],
                    ] as [$key, $label])
                    <div class="sc-form-row">
                        <label class="sc-form-label">{{ $label }}</label>
                        <input type="checkbox" name="{{ $key }}" value="1" @checked($values[$key]) style="width:18px;height:18px;">
                    </div>
                    @endforeach

                    <hr style="margin:20px 0;border-color:var(--gray-200)">
                    <h6 style="color:var(--primary);font-weight:600;margin-bottom:12px;">Sync Inventory from Locations</h6>
                    @foreach($locations as $loc)
                    <div class="sc-form-row">
                        <label class="sc-form-label">{{ $loc->name }}</label>
                        <input type="checkbox" name="ecommerce_locations[]" value="{{ $loc->location_id }}" @checked(isset($ecommerce_locations[$loc->location_id])) style="width:18px;height:18px;">
                    </div>
                    @endforeach

                    <hr style="margin:20px 0;border-color:var(--gray-200)">
                    <h6 style="color:var(--primary);font-weight:600;margin-bottom:12px;">Cron Sync Operations</h6>
                    @php
                        $syncOps = array_filter(explode(',', $values['ecommerce_cron_sync_operations'] ?? ''));
                    @endphp
                    @foreach([
                        ['sync_inventory_changes','Sync Inventory Changes','all'],
                        ['import_ecommerce_tags_into_phppos','Import Ecommerce Tags into System','woocommerce'],
                        ['import_ecommerce_categories_into_phppos','Import Ecommerce Categories into System','woocommerce'],
                        ['import_ecommerce_attributes_into_phppos','Import Ecommerce Attributes into System','woocommerce'],
                        ['import_tax_classes_into_phppos','Import Tax Classes into System','woocommerce'],
                        ['import_shipping_classes_into_phppos','Import Shipping Classes into System','woocommerce'],
                        ['import_ecommerce_items_into_phppos','Import Ecommerce Items into System','all'],
                        ['import_ecommerce_orders_into_phppos','Import Ecommerce Orders into System','all'],
                        ['export_phppos_tags_to_ecommerce','Export Tags to Ecommerce','woocommerce'],
                        ['export_phppos_categories_to_ecommerce','Export Categories to Ecommerce','all'],
                        ['export_phppos_attributes_to_ecommerce','Export Attributes to Ecommerce','woocommerce'],
                        ['export_phppos_items_to_ecommerce','Export Items to Ecommerce','all'],
                    ] as [$op, $label, $platform])
                    <div class="sc-form-row ecommerce-cron-op" data-platform="{{ $platform }}">
                        <label class="sc-form-label">{{ $label }}</label>
                        <input type="checkbox" name="ecommerce_cron_sync_operations[]" value="{{ $op }}" @checked(in_array($op, $syncOps)) style="width:18px;height:18px;">
                    </div>
                    @endforeach

                    <!-- WooCommerce Credentials -->
                    <div id="woo_credentials" style="display:none">
                        <hr style="margin:20px 0;border-color:var(--gray-200)">
                        <h6 style="color:var(--primary);font-weight:600;margin-bottom:12px;">WooCommerce Credentials</h6>
                        <div class="sc-form-row">
                            <label class="sc-form-label">WooCommerce Store URL</label>
                            <input type="text" name="woocommerce_url" class="sc-form-control" value="{{ $values['woocommerce_url'] }}" placeholder="https://yourstore.com">
                        </div>
                        <div class="sc-form-row">
                            <label class="sc-form-label">Consumer Key</label>
                            <input type="text" name="woocommerce_consumer_key" class="sc-form-control" value="{{ $values['woocommerce_consumer_key'] }}">
                        </div>
                        <div class="sc-form-row">
                            <label class="sc-form-label">Consumer Secret</label>
                            <input type="password" name="woocommerce_consumer_secret" class="sc-form-control" value="{{ $values['woocommerce_consumer_secret'] }}">
                        </div>
                    </div>

                    <!-- Shopify Credentials -->
                    <div id="shopify_credentials" style="display:none">
                        <hr style="margin:20px 0;border-color:var(--gray-200)">
                        <h6 style="color:var(--primary);font-weight:600;margin-bottom:12px;">Shopify Credentials</h6>
                        <div class="sc-form-row">
                            <label class="sc-form-label">Shopify Store URL</label>
                            <input type="text" name="shopify_store_url" class="sc-form-control" value="{{ $values['shopify_store_url'] }}" placeholder="yourstore.myshopify.com">
                        </div>
                        <div class="sc-form-row">
                            <label class="sc-form-label">API Key (Public)</label>
                            <input type="text" name="shopify_public" class="sc-form-control" value="{{ $values['shopify_public'] }}">
                        </div>
                        <div class="sc-form-row">
                            <label class="sc-form-label">API Secret (Private)</label>
                            <input type="password" name="shopify_private" class="sc-form-control" value="{{ $values['shopify_private'] }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Application Settings -->
            <div class="sc-tab-panel" id="tab-app-settings">
                <div class="sc-form-card">
                    <h5 class="mb-4" style="color:var(--primary);font-weight:700;">Application Settings</h5>

                    <div class="sc-form-row">
                        <label class="sc-form-label">Store Closing Time</label>
                        <input type="time" name="store_closing_time" class="sc-form-control" value="{{ $values['store_closing_time'] }}">
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Session Expiration</label>
                        <select name="phppos_session_expiration" class="sc-form-control">
                            <option value="0" @selected($values['phppos_session_expiration']==0||$values['phppos_session_expiration']==='')>Never</option>
                            @foreach([15,30,60,120,240,480,720,1440] as $min)
                                <option value="{{ $min }}" @selected($values['phppos_session_expiration']==$min)>{{ $min }} min</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Offline Mode Sync Period (hours)</label>
                        <select name="offline_mode_sync_period" class="sc-form-control">
                            @foreach(range(1,48) as $h)
                                <option value="{{ $h }}" @selected($values['offline_mode_sync_period']==$h)>{{ $h }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Report Sort Order</label>
                        <select name="report_sort_order" class="sc-form-control">
                            <option value="asc" @selected($values['report_sort_order']=='asc')>Ascending</option>
                            <option value="desc" @selected($values['report_sort_order']=='desc')>Descending</option>
                        </select>
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Spreadsheet Export Format</label>
                        <select name="spreadsheet_format" class="sc-form-control">
                            <option value="CSV" @selected($values['spreadsheet_format']=='CSV')>CSV</option>
                            <option value="XLSX" @selected($values['spreadsheet_format']=='XLSX')>XLSX</option>
                        </select>
                    </div>
                    <div class="sc-form-row">
                        <label class="sc-form-label">Mailing Labels Type</label>
                        <select name="mailing_labels_type" class="sc-form-control">
                            <option value="pdf" @selected($values['mailing_labels_type']=='pdf')>PDF</option>
                            <option value="excel" @selected($values['mailing_labels_type']=='excel')>Excel</option>
                        </select>
                    </div>

                    <hr style="margin:20px 0;border-color:var(--gray-200)">
                    <h6 style="color:var(--primary);font-weight:600;margin-bottom:12px;">Offline &amp; Sync</h6>
                    @foreach([
                        ['offline_mode','Enable Offline Mode'],
                        ['auto_sync_offline_sales','Auto Sync Offline Sales'],
                        ['payvantage','PayVantage'],
                    ] as [$key,$label])
                    <div class="sc-form-row">
                        <label class="sc-form-label">{{ $label }}</label>
                        <input type="checkbox" name="{{ $key }}" value="1" @checked($values[$key]) style="width:18px;height:18px;">
                    </div>
                    @endforeach

                    <hr style="margin:20px 0;border-color:var(--gray-200)">
                    <h6 style="color:var(--primary);font-weight:600;margin-bottom:12px;">Display &amp; UI</h6>
                    @foreach([
                        ['dark_mode','Dark Mode'],
                        ['show_language_switcher','Show Language Switcher'],
                        ['show_clock_on_header','Show Clock on Header'],
                        ['always_minimize_menu','Always Minimize Menu'],
                        ['hide_dashboard_statistics','Hide Dashboard Statistics'],
                        ['enable_sounds','Enable Sounds'],
                    ] as [$key,$label])
                    <div class="sc-form-row">
                        <label class="sc-form-label">{{ $label }}</label>
                        <input type="checkbox" name="{{ $key }}" value="1" @checked($values[$key]) style="width:18px;height:18px;">
                    </div>
                    @endforeach

                    <hr style="margin:20px 0;border-color:var(--gray-200)">
                    <h6 style="color:var(--primary);font-weight:600;margin-bottom:12px;">Customers &amp; Security</h6>
                    @foreach([
                        ['only_allow_current_location_customers','Only Allow Current Location Customers'],
                        ['default_new_customer_to_current_location','Default New Customer to Current Location'],
                        ['force_https','Force HTTPS'],
                        ['do_not_force_http','Do Not Force HTTP (Payments)'],
                        ['hide_expire_dashboard','Hide Expiry Dashboard Notices'],
                        ['do_not_delete_saved_card_after_failure','Do Not Delete Saved Card After Failure'],
                        ['test_mode','Test Mode'],
                        ['disable_test_mode','Disable Test Mode'],
                        ['customer_allow_partial_match','Customer Allow Partial Match'],
                    ] as [$key,$label])
                    <div class="sc-form-row">
                        <label class="sc-form-label">{{ $label }}</label>
                        <input type="checkbox" name="{{ $key }}" value="1" @checked($values[$key]) style="width:18px;height:18px;">
                    </div>
                    @endforeach

                    <hr style="margin:20px 0;border-color:var(--gray-200)">
                    <h6 style="color:var(--primary);font-weight:600;margin-bottom:12px;">Search &amp; Reports</h6>
                    @foreach([
                        ['speed_up_search_queries','Speed Up Search Queries (uses DB index)'],
                        ['enhanced_search_method','Enhanced Search Method (Full Text)'],
                        ['include_child_categories_when_searching_or_reporting','Include Child Categories When Searching/Reporting'],
                        ['show_full_category_path','Show Full Category Path'],
                        ['legacy_detailed_report_export','Legacy Detailed Report Export'],
                        ['hide_item_descriptions_in_reports','Hide Item Descriptions in Reports'],
                        ['overwrite_existing_items_on_excel_import','Overwrite Existing Items on Excel Import'],
                        ['allow_scan_of_customer_into_item_field','Allow Scan of Customer Barcode into Item Field'],
                        ['send_sms_via_whatsapp','Send SMS via WhatsApp'],
                    ] as [$key,$label])
                    <div class="sc-form-row">
                        <label class="sc-form-label">{{ $label }}</label>
                        <input type="checkbox" name="{{ $key }}" value="1" @checked($values[$key]) style="width:18px;height:18px;">
                    </div>
                    @endforeach

                    <hr style="margin:20px 0;border-color:var(--gray-200)">
                    <h6 style="color:var(--primary);font-weight:600;margin-bottom:12px;">Quick Add &amp; Edit</h6>
                    @foreach([
                        ['enable_quick_edit','Enable Quick Edit'],
                        ['enable_quick_expense','Enable Quick Expense'],
                        ['enable_quick_customers','Enable Quick Customers'],
                        ['enable_quick_suppliers','Enable Quick Suppliers'],
                        ['enable_quick_items','Enable Quick Items'],
                    ] as [$key,$label])
                    <div class="sc-form-row">
                        <label class="sc-form-label">{{ $label }}</label>
                        <input type="checkbox" name="{{ $key }}" value="1" @checked($values[$key]) style="width:18px;height:18px;">
                    </div>
                    @endforeach
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
                                <td><input type="text" name="currency_exchange_rates_symbol[]" class="sc-currency-control" value="$"></td>
                                <td>
                                    <select name="currency_exchange_rates_symbol_location[]" class="sc-currency-control">
                                        <option value="before">Before</option>
                                        <option value="after">After</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="currency_exchange_rates_number_of_decimals[]" class="sc-currency-control">
                                        <option value="">Let system decide</option>
                                        <option value="0">0</option>
                                        <option value="1">1</option>
                                        <option value="2" selected>2</option>
                                        <option value="3">3</option>
                                        <option value="4">4</option>
                                        <option value="5">5</option>
                                    </select>
                                </td>
                                <td><input type="text" name="currency_exchange_rates_thousands_separator[]" class="sc-currency-control" value=","></td>
                                <td><input type="text" name="currency_exchange_rates_decimal_point[]" class="sc-currency-control" value="."></td>
                                <td><input type="text" name="currency_exchange_rates_rate[]" class="sc-currency-control" value=""></td>
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

            const denomsTable = document.querySelector('#currencyDenomsTable');
            const storeConfigForm = document.getElementById('storeConfigForm');
            document.getElementById('addDenom')?.addEventListener('click', function (e) {
                e.preventDefault();
                if (!denomsTable) return;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><input type="text" name="currency_denoms_name[]" class="sc-currency-control" value=""></td>
                    <td><input type="text" name="currency_denoms_value[]" class="sc-currency-control" value=""></td>
                    <td>
                        <a class="sc-currency-link remove-denom" href="#" style="color: var(--danger);">Delete</a>
                        <input type="hidden" name="currency_denoms_ids[]" value="" />
                    </td>`;
                denomsTable.querySelector('tbody').appendChild(tr);
            });

            denomsTable?.addEventListener('click', function (e) {
                const link = e.target.closest('.remove-denom');
                if (!link) return;
                e.preventDefault();
                const id = link.getAttribute('data-id');
                if (id && storeConfigForm) {
                    const hid = document.createElement('input');
                    hid.type = 'hidden';
                    hid.name = 'deleted_denmos[]';
                    hid.value = id;
                    storeConfigForm.appendChild(hid);
                }
                link.closest('tr').remove();
            });

            const taxClassesTable = document.getElementById('taxClassesTable');
            const addTaxClassBtn = document.getElementById('addTaxClass');
            let taxClassCounter = Date.now();

            function buildTaxRateRow(classId, rateIndex) {
                return `
                    <tr data-rate-index="${rateIndex}">
                        <td>
                            <input type="text" name="taxes[${classId}][name][${rateIndex}]" class="sc-form-control" value="">
                            <input type="hidden" name="taxes[${classId}][tax_class_tax_id][${rateIndex}]" value="">
                        </td>
                        <td>
                            <input type="text" name="taxes[${classId}][percent][${rateIndex}]" class="sc-form-control" value="">
                        </td>
                        <td class="text-center">
                            <input type="hidden" name="taxes[${classId}][cumulative][${rateIndex}]" value="0">
                            <input type="checkbox" name="taxes[${classId}][cumulative][${rateIndex}]" value="1">
                        </td>
                        <td>
                            <a href="#" class="remove-tax-rate" style="color:var(--danger);">Delete</a>
                        </td>
                    </tr>
                `;
            }

            function getNextRateIndex(tbody) {
                let maxIndex = -1;
                tbody.querySelectorAll('tr[data-rate-index]').forEach((row) => {
                    const idx = parseInt(row.getAttribute('data-rate-index'), 10);
                    if (!Number.isNaN(idx) && idx > maxIndex) {
                        maxIndex = idx;
                    }
                });
                return maxIndex + 1;
            }

            addTaxClassBtn?.addEventListener('click', function (e) {
                e.preventDefault();
                if (!taxClassesTable) return;

                const tbody = taxClassesTable.querySelector('tbody');
                tbody.querySelector('[data-empty-row]')?.remove();

                const classId = `new_${taxClassCounter++}`;
                const row = document.createElement('tr');
                row.setAttribute('data-tax-class-id', classId);
                row.innerHTML = `
                    <td>
                        <input type="text" name="tax_classes[${classId}][name]" class="sc-form-control" value="">
                    </td>
                    <td>
                        <table class="table table-sm mb-2 tax-rate-table">
                            <thead>
                                <tr>
                                    <th>Tax Name</th>
                                    <th>Percent (%)</th>
                                    <th class="text-center">Cumulative</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                ${buildTaxRateRow(classId, 0)}
                            </tbody>
                        </table>
                        <a href="#" class="add-tax-rate">Add Rate</a>
                    </td>
                    <td class="text-center">
                        <input type="radio" name="tax_class_id" value="${classId}">
                    </td>
                    <td>
                        <a href="#" class="remove-tax-class" style="color:var(--danger);">Delete Class</a>
                    </td>
                `;
                tbody.appendChild(row);
            });

            taxClassesTable?.addEventListener('click', function (e) {
                const addRate = e.target.closest('.add-tax-rate');
                if (addRate) {
                    e.preventDefault();
                    const classRow = addRate.closest('tr[data-tax-class-id]');
                    const classId = classRow?.getAttribute('data-tax-class-id');
                    const rateBody = classRow?.querySelector('.tax-rate-table tbody');
                    if (!classId || !rateBody) return;
                    const nextIndex = getNextRateIndex(rateBody);
                    rateBody.insertAdjacentHTML('beforeend', buildTaxRateRow(classId, nextIndex));
                    return;
                }

                const removeRate = e.target.closest('.remove-tax-rate');
                if (removeRate) {
                    e.preventDefault();
                    const rateRow = removeRate.closest('tr[data-rate-index]');
                    const taxIdInput = rateRow?.querySelector('input[name*="[tax_class_tax_id]"]');
                    if (taxIdInput && taxIdInput.value && storeConfigForm) {
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'taxes_to_delete[]';
                        hidden.value = taxIdInput.value;
                        storeConfigForm.appendChild(hidden);
                    }
                    rateRow?.remove();
                    return;
                }

                const removeClass = e.target.closest('.remove-tax-class');
                if (removeClass) {
                    e.preventDefault();
                    const classRow = removeClass.closest('tr[data-tax-class-id]');
                    const classId = classRow?.getAttribute('data-tax-class-id');
                    const defaultRadio = classRow?.querySelector('input[type="radio"][name="tax_class_id"]');
                    if (defaultRadio && defaultRadio.checked) {
                        defaultRadio.checked = false;
                    }
                    if (classId && /^[0-9]+$/.test(classId) && storeConfigForm) {
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'tax_classes_to_delete[]';
                        hidden.value = classId;
                        storeConfigForm.appendChild(hidden);
                    }
                    classRow?.remove();
                }
            });

            document.getElementById('toggleMoreTaxes')?.addEventListener('click', function (e) {
                e.preventDefault();
                const moreTaxes = document.getElementById('moreDefaultTaxes');
                if (moreTaxes) {
                    moreTaxes.style.display = 'block';
                }
                e.target.style.display = 'none';
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
            // Price Tiers JS
            let tierAddIndex = Date.now();
            document.getElementById('add_tier')?.addEventListener('click', function(e) {
                e.preventDefault();
                const tbody = document.getElementById('price_tiers_tbody');
                const idx = 'new_' + tierAddIndex++;
                const tr = document.createElement('tr');
                tr.setAttribute('data-tier-id', idx);
                tr.innerHTML = `
                    <td><input type="text" class="sc-form-control" name="tiers_to_edit[${idx}][name]" value=""></td>
                    <td><input type="text" class="sc-form-control" name="tiers_to_edit[${idx}][default_percent_off]" value=""></td>
                    <td><input type="text" class="sc-form-control" name="tiers_to_edit[${idx}][default_cost_plus_percent]" value=""></td>
                    <td><input type="text" class="sc-form-control" name="tiers_to_edit[${idx}][default_cost_plus_fixed_amount]" value=""></td>
                    <td class="text-center"><a href="#" class="delete_tier text-danger" data-tier-id="${idx}">Delete</a></td>`;
                tbody.appendChild(tr);
            });

            document.getElementById('price_tiers_tbody')?.addEventListener('click', function(e) {
                const link = e.target.closest('.delete_tier');
                if (!link) return;
                e.preventDefault();
                const tierId = link.getAttribute('data-tier-id');
                const row = link.closest('tr');
                if (tierId && !tierId.startsWith('new_')) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'tiers_to_delete[]';
                    input.value = tierId;
                    document.getElementById('storeConfigForm').appendChild(input);
                }
                row.remove();
            });

            // Ecommerce Platform Toggle
            function updateEcommercePlatform() {
                const platform = document.getElementById('ecommerce_platform_select')?.value;
                const woo = document.getElementById('woo_credentials');
                const shopify = document.getElementById('shopify_credentials');
                if (woo) woo.style.display = platform === 'woocommerce' ? '' : 'none';
                if (shopify) shopify.style.display = platform === 'shopify' ? '' : 'none';
                document.querySelectorAll('.ecommerce-cron-op').forEach(function(row) {
                    const p = row.getAttribute('data-platform');
                    row.style.display = (p === 'all' || p === platform) ? '' : 'none';
                });
            }
            document.getElementById('ecommerce_platform_select')?.addEventListener('change', updateEcommercePlatform);
            updateEcommercePlatform();
        });
    </script>
@endpush