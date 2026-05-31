@extends('layouts.app')

@section('title', 'Sales Register')
@section('page-title', 'Sales Register')

@push('styles')
    <style>
        .category-tabs {
            display: inline-flex;
            border: 1px solid #d7e0ea;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        .category-tabs .tab-btn {
            border: 0;
            background: transparent;
            padding: 8px 16px;
            font-weight: 600;
            color: #334155;
        }

        .category-tabs .tab-btn+.tab-btn {
            border-left: 1px solid #e2e8f0;
        }

        .category-tabs .tab-btn.is-active {
            background: var(--primary);
            color: #fff;
        }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(8, minmax(0, 1fr));
            gap: 12px;
        }

        @media (max-width: 1600px) {
            .category-grid {
                grid-template-columns: repeat(6, minmax(0, 1fr));
            }
        }

        @media (max-width: 1200px) {
            .category-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        @media (max-width: 992px) {
            .category-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 576px) {
            .category-grid {
                grid-template-columns: 1fr;
            }
        }

        .category-card {
            border: 1px solid #e2e8f0;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            text-align: left;
            padding: 0;
            overflow: hidden;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .category-card:hover {
            border-color: var(--primary-soft);
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.08);
        }

        .category-name {
            padding: 14px 12px;
            font-weight: 600;
            color: #0f172a;
            text-align: center;
        }

        .category-pagination {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 12px;
        }

        .category-pagination .page-btn {
            border: 1px solid #d7e0ea;
            background: #fff;
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 600;
            color: #334155;
        }

        .category-pagination .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .category-pagination .page-info {
            font-size: 0.9rem;
            color: #64748b;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
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

        @if($errors instanceof \Illuminate\Support\ViewErrorBag && $errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                @foreach($errors->all() as $message)
                    <div>{{ $message }}</div>
                @endforeach
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($currentRegister)
        <div class="card shadow-sm border-0 mb-4 bg-light border-start border-primary border-4">
            <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <span class="badge bg-success me-3 px-3 py-2 fs-6">
                        <i class="bi bi-circle-fill me-1 small"></i> Open
                    </span>
                    <div>
                        <h6 class="m-0 fw-bold text-dark">Register: {{ $currentRegister->name }}</h6>
                        @if($registerLog)
                            <small class="text-muted">
                                Opened by <strong>{{ $registerLog->employeeOpen?->person?->first_name }} {{ $registerLog->employeeOpen?->person?->last_name }}</strong> 
                                at {{ \Carbon\Carbon::parse($registerLog->shift_start)->format('M d, Y h:i A') }}
                            </small>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changeRegisterModal">
                        <i class="bi bi-arrow-left-right me-1"></i> Change Register
                    </button>
                    <a href="{{ route('sales.register.close') }}" class="btn btn-sm btn-danger">
                        <i class="bi bi-x-circle me-1"></i> Close Register
                    </a>
                </div>
            </div>
        </div>
        @endif

        @php
            $currencySymbol = $baseCurrency['symbol'] ?? '$';
            $currencySymbolLocation = $baseCurrency['symbol_location'] ?? 'before';
            $currencyDecimals = (int) ($baseCurrency['decimals'] ?? 2);
            $currencyThousands = $baseCurrency['thousands_separator'] ?? ',';
            $currencyDecimalPoint = $baseCurrency['decimal_point'] ?? '.';
            $baseCurrencyCode = $baseCurrency['code'] ?? '';

            $formatCurrencyWith = function (
                float $value,
                string $symbol,
                string $symbolLocation,
                int $decimals,
                string $thousandsSeparator,
                string $decimalPoint
            ): string {
                $formatted = number_format($value, $decimals, $decimalPoint, $thousandsSeparator);
                return $symbolLocation === 'after' ? $formatted . $symbol : $symbol . $formatted;
            };

            $formatCurrency = function (float $value) use (
                $currencySymbol,
                $currencySymbolLocation,
                $currencyDecimals,
                $currencyThousands,
                $currencyDecimalPoint,
                $formatCurrencyWith
            ): string {
                return $formatCurrencyWith(
                    $value,
                    $currencySymbol,
                    $currencySymbolLocation,
                    $currencyDecimals,
                    $currencyThousands,
                    $currencyDecimalPoint
                );
            };
        @endphp

        <div class="card shadow-sm border-0 mb-4" id="category-grid-card">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="category-tabs" role="tablist" aria-label="Category tabs">
                    <button type="button" class="tab-btn is-active" role="tab" aria-selected="true" data-mode="categories">Categories</button>
                    <button type="button" class="tab-btn" role="tab" aria-selected="false" data-mode="tags">Tags</button>
                    <button type="button" class="tab-btn" role="tab" aria-selected="false" data-mode="favorites">Favorites</button>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="toggle-category-grid"
                    data-show-text="Show Grid" data-hide-text="Hide Grid">Hide Grid</button>
            </div>
            <div class="card-body" id="category-grid-body">
                <div class="category-grid" id="category-grid">
                    @forelse($categories as $category)
                        <button type="button" class="category-card" data-category-id="{{ $category->id }}"
                            data-category-name="{{ $category->name }}">
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
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Item Selection</h6>
                        <div class="input-group input-group-sm w-50">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" id="item_search" class="form-control bg-light border-start-0"
                                placeholder="Search item or scan barcode..." autocomplete="off">
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="min-height: 400px;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4" width="50">#</th>
                                        <th>Item Name</th>
                                        <th width="120">Unit Price</th>
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
                                                <div class="fw-bold text-primary">{{ $item['name'] }}</div>
                                                <small class="text-muted">ID: {{ $item['item_id'] }}</small>
                                            </td>
                                            <td>
                                                <form action="{{ route('sales.item.edit', $index) }}" method="POST">
                                                    @csrf
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">{{ $currencySymbol }}</span>
                                                        <input type="number" step="0.01" name="unit_price" class="form-control"
                                                            value="{{ $item['unit_price'] }}" onchange="this.form.submit()">
                                                    </div>
                                                </form>
                                            </td>
                                            <td>
                                                <form action="{{ route('sales.item.edit', $index) }}" method="POST">
                                                    @csrf
                                                    <input type="number" step="0.001" name="quantity"
                                                        class="form-control form-control-sm" value="{{ $item['quantity'] }}"
                                                        onchange="this.form.submit()">
                                                </form>
                                            </td>
                                            <td>
                                                <form action="{{ route('sales.item.edit', $index) }}" method="POST">
                                                    @csrf
                                                    <input type="number" step="0.1" name="discount"
                                                        class="form-control form-control-sm" value="{{ $item['discount'] }}"
                                                        onchange="this.form.submit()">
                                                </form>
                                            </td>
                                            <td class="text-end fw-bold">
                                                {{ $formatCurrency($item['unit_price'] * $item['quantity'] * (1 - $item['discount'] / 100)) }}
                                            </td>
                                            <td class="text-end pe-4">
                                                <form action="{{ route('sales.item.remove', $index) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm text-danger"><i
                                                            class="bi bi-x-circle"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">
                                                <i class="bi bi-cart fs-1 d-block mb-2"></i>
                                                Your sales cart is empty.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div hidden class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <form action="{{ route('sales.location.set') }}" method="POST">
                            @csrf
                            <label class="form-label small fw-bold text-uppercase text-muted">Location</label>
                            <select name="location_id" class="form-select" onchange="this.form.submit()">
                                @foreach($locations as $location)
                                    <option value="{{ $location->location_id }}"
                                        @selected($cart['location_id'] == $location->location_id)>
                                        {{ $location->name }} ({{ $location->location_id }})
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <form action="{{ route('sales.customer.set') }}" method="POST">
                            @csrf
                            <label class="form-label small fw-bold text-uppercase text-muted">Select Customer</label>
                            <div class="input-group">
                                <select name="customer_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">— Walk-in Customer —</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->person_id }}"
                                            @selected($cart['customer_id'] == $customer->person_id)>
                                            {{ $customer->person?->first_name }} {{ $customer->person?->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($cart['customer_id'])
                                    <button type="submit" name="customer_id" value="" class="btn btn-outline-danger"><i
                                            class="bi bi-x"></i></button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                {{-- <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <form action="{{ route('sales.supplier.set') }}" method="POST">
                            @csrf
                            <label class="form-label small fw-bold text-uppercase text-muted">Filter by Supplier</label>
                            <div class="input-group">
                                <select name="supplier_id" class="form-select select2-supplier"
                                    onchange="this.form.submit()">
                                    <option value="">— All Suppliers —</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->person_id }}"
                                            @selected($cart['supplier_id'] == $supplier->person_id)>
                                            {{ $supplier->person?->first_name }} {{ $supplier->person?->last_name }}
                                            {{ $supplier->company_name ? '(' . $supplier->company_name . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($cart['supplier_id'])
                                    <button type="submit" name="supplier_id" value="" class="btn btn-outline-danger"><i
                                            class="bi bi-x"></i></button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div> --}}

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <h6 class="text-uppercase small fw-bold text-muted">Payments</h6>
                        <form action="{{ route('sales.payment.add') }}" method="POST" class="mb-3">
                            @csrf
                            <div class="mb-2">
                                @php
                                    $defaultPaymentTypes = ['Cash', 'Check', 'Credit Card', 'Debit Card'];
                                    $paymentTypeOptions = array_values(array_unique(array_merge($defaultPaymentTypes, $paymentTypes)));
                                @endphp
                                <select name="payment_type" class="form-select form-select-sm" required>
                                    @foreach($paymentTypeOptions as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <select name="currency_code" id="payment_currency" class="form-select form-select-sm">
                                    <option value="{{ $baseCurrencyCode }}">
                                        {{ $baseCurrencyCode !== '' ? $baseCurrencyCode : 'Base Currency' }} ({{ $currencySymbol }})
                                    </option>
                                    @foreach($currencyRates as $rate)
                                        <option value="{{ $rate['code'] }}">{{ $rate['code'] }} ({{ $rate['symbol'] }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" id="payment-currency-symbol">{{ $currencySymbol }}</span>
                                <input type="number" step="0.01" name="amount" id="payment_amount" class="form-control" placeholder="Amount"
                                    required>
                                <button type="submit" class="btn btn-outline-primary">Add</button>
                            </div>
                            <small class="text-muted" id="payment-converted"></small>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <tbody>
                                    @forelse($cart['payments'] as $index => $payment)
                                        <tr>
                                            <td>{{ $payment['type'] }}</td>
                                            <td class="text-end">
                                                @php
                                                    $paymentSymbol = $payment['currency_symbol'] ?? $currencySymbol;
                                                    $paymentSymbolLocation = $payment['currency_symbol_location'] ?? $currencySymbolLocation;
                                                    $paymentDecimals = (int) ($payment['currency_number_of_decimals'] ?? $currencyDecimals);
                                                    $paymentThousands = $payment['currency_thousands_separator'] ?? $currencyThousands;
                                                    $paymentDecimalPoint = $payment['currency_decimal_point'] ?? $currencyDecimalPoint;
                                                    $paymentCurrencyAmount = (float) ($payment['currency_amount'] ?? $payment['amount']);
                                                    $paymentCurrencyCode = $payment['currency_code'] ?? $baseCurrencyCode;
                                                    $paymentRate = (float) ($payment['exchange_rate'] ?? 1);
                                                @endphp
                                                @if($paymentRate !== 1.0)
                                                    <div>{{ $formatCurrency((float) $payment['amount']) }}</div>
                                                    <small class="text-muted">
                                                        {{ $formatCurrencyWith($paymentCurrencyAmount, $paymentSymbol, $paymentSymbolLocation, $paymentDecimals, $paymentThousands, $paymentDecimalPoint) }}
                                                        {{ $paymentCurrencyCode }}
                                                    </small>
                                                @else
                                                    {{ $formatCurrency((float) $payment['amount']) }}
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <form action="{{ route('sales.payment.remove', $index) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm text-danger"><i
                                                            class="bi bi-x-circle"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-muted">No payments added yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 bg-secondary text-white">
                    <div class="card-body">
                        <h6 class="text-uppercase small mb-4 opacity-75 fw-bold">Order Summary</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span class="fw-bold">{{ $formatCurrency($subtotal) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Payments</span>
                            <span class="fw-bold">{{ $formatCurrency($paymentTotal) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-4 fs-5 border-top pt-3 mt-3">
                            <span class="fw-bold">Amount Due</span>
                            <span class="fw-bold">{{ $formatCurrency($amountDue) }}</span>
                        </div>

                        <form action="{{ route('sales.complete') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <textarea name="comment"
                                    class="form-control form-control-sm bg-dark border-white border-opacity-25 text-white placeholder-white"
                                    placeholder="Add notes/comments..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-light btn-lg w-100 fw-bold text-dark mb-2" {{ empty($cart['items']) ? 'disabled' : '' }}>
                                <i class="bi bi-check2-circle me-2"></i> COMPLETE SALE
                            </button>
                        </form>

                        <form action="{{ route('sales.cancel') }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="btn btn-link btn-sm w-100 text-white opacity-75 text-decoration-none mt-2"
                                onclick="return confirm('Clear cart?')">
                                <i class="bi bi-trash"></i> Cancel Sale
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="search_results" class="position-absolute shadow-sm bg-white rounded-bottom"
        style="display:none; z-index: 1000; width: 300px;"></div>

    <!-- Change Register Modal -->
    @if($currentRegister)
    <div class="modal fade" id="changeRegisterModal" tabindex="-1" aria-labelledby="changeRegisterModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content text-dark">
                <form action="{{ route('sales.register.change') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="changeRegisterModalLabel">Select Register</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label for="modal_register_select" class="form-label fw-semibold">Choose a Register</label>
                            <select name="register_id" id="modal_register_select" class="form-select">
                                @foreach($registers as $reg)
                                    <option value="{{ $reg->register_id }}" @selected($reg->register_id == $currentRegister->register_id)>
                                        {{ $reg->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-semibold">Switch Register</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const baseCurrency = @json($baseCurrency);
            const currencyRates = @json($currencyRates);
            const currencyMap = new Map(currencyRates.map((rate) => [rate.code, rate]));

            const normalizeCurrency = (meta) => {
                return {
                    symbol: meta.symbol || baseCurrency.symbol || '$',
                    symbolLocation: meta.symbol_location || baseCurrency.symbol_location || 'before',
                    decimals: Number.isFinite(meta.decimals) ? meta.decimals : (baseCurrency.decimals ?? 2),
                    thousandsSeparator: meta.thousands_separator || baseCurrency.thousands_separator || ',',
                    decimalPoint: meta.decimal_point || baseCurrency.decimal_point || '.',
                    rate: Number(meta.rate) || 1,
                };
            };

            const formatNumber = (value, decimals, thousandsSeparator, decimalPoint) => {
                const negative = value < 0;
                const fixed = Math.abs(value).toFixed(decimals);
                const parts = fixed.split('.');
                let whole = parts[0];
                const fraction = parts[1] || '';
                const rgx = /(\d+)(\d{3})/;
                while (rgx.test(whole)) {
                    whole = whole.replace(rgx, `$1${thousandsSeparator}$2`);
                }
                const combined = fraction ? `${whole}${decimalPoint}${fraction}` : whole;
                return negative ? `-${combined}` : combined;
            };

            const formatCurrency = (value, meta = baseCurrency) => {
                const normalized = normalizeCurrency(meta);
                const formatted = formatNumber(
                    Number(value || 0),
                    normalized.decimals,
                    normalized.thousandsSeparator,
                    normalized.decimalPoint
                );
                return normalized.symbolLocation === 'after'
                    ? `${formatted}${normalized.symbol}`
                    : `${normalized.symbol}${formatted}`;
            };

            const applyTabUI = (tabEl) => {
                const tabList = tabEl.closest('.category-tabs');
                if (!tabList) return;
                const tabs = tabList.querySelectorAll('.tab-btn');
                tabs.forEach((btn) => {
                    btn.classList.toggle('is-active', btn === tabEl);
                    btn.setAttribute('aria-selected', btn === tabEl ? 'true' : 'false');
                });
            };

            const toggleButton = document.getElementById('toggle-category-grid');
            const gridBody = document.getElementById('category-grid-body');
            const storageKey = 'salesCategoryGridHidden';

            const grid = document.getElementById('category-grid');
            const paginationEl = document.getElementById('category-grid-pagination');
            const browseUrl = "{{ route('sales.categories') }}";
            const tagsUrl = "{{ route('sales.tags') }}";
            const favoritesUrl = "{{ route('sales.favorites') }}";
            const tagItemsBaseUrl = "{{ url('/sales/tags') }}";

            let gridMode = 'categories';
            let currentTagId = null;
            let tagsListPage = 1;
            const categoryStack = [];
            const defaultPageSize = 8;
            const childPageSize = 7;

            const renderBackTile = () => {
                const shouldShow =
                    (gridMode === 'categories' && categoryStack.length) ||
                    (gridMode === 'tags' && currentTagId !== null);

                if (!shouldShow) return '';
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

            const renderTagTile = (tag) => {
                return `
                <button type="button" class="category-card" data-tag-id="${tag.id}" data-tag-name="${tag.name}">
                    <div class="category-name">${tag.name}</div>
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
                    if (data.categories?.length) {
                        data.categories.forEach((category) => tiles.push(renderCategoryTile(category)));
                    } else {
                        tiles.push('<div class="text-muted">No categories available.</div>');
                    }
                } else if (data.level === 'tags') {
                    if (data.tags?.length) {
                        data.tags.forEach((tag) => tiles.push(renderTagTile(tag)));
                    } else {
                        tiles.push('<div class="text-muted">No tags available.</div>');
                    }
                } else {
                    if (data.products?.length) {
                        data.products.forEach((product) => tiles.push(renderProductTile(product)));
                    } else {
                        tiles.push('<div class="text-muted">No products found.</div>');
                    }
                }

                grid.innerHTML = tiles.join('');
                renderPagination(data.pagination);
            };

            const fetchJson = (url) => {
                return fetch(url)
                    .then((res) => {
                        if (!res.ok) throw new Error('Request failed');
                        return res.json();
                    });
            };

            const loadCategories = (categoryId, pushStack, page = 1) => {
                const params = new URLSearchParams();
                const perPage = categoryId ? childPageSize : defaultPageSize;
                params.set('page', String(page));
                params.set('per_page', String(perPage));
                if (categoryId) params.set('category_id', categoryId);
                const url = `${browseUrl}?${params.toString()}`;

                fetchJson(url)
                    .then((data) => {
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

            const loadTags = (page = 1) => {
                const params = new URLSearchParams();
                params.set('page', String(page));
                params.set('per_page', String(defaultPageSize));
                const url = `${tagsUrl}?${params.toString()}`;

                fetchJson(url)
                    .then((data) => {
                        tagsListPage = data.pagination?.page || page;
                        renderGrid(data);
                    })
                    .catch(() => {
                        if (grid) grid.innerHTML = '<div class="text-muted">Unable to load tags.</div>';
                    });
            };

            const loadTagItems = (tagId, page = 1) => {
                const params = new URLSearchParams();
                params.set('page', String(page));
                params.set('per_page', String(childPageSize));
                const url = `${tagItemsBaseUrl}/${encodeURIComponent(tagId)}/items?${params.toString()}`;

                fetchJson(url)
                    .then((data) => {
                        renderGrid(data);
                    })
                    .catch(() => {
                        if (grid) grid.innerHTML = '<div class="text-muted">Unable to load tag items.</div>';
                    });
            };

            const loadFavorites = (page = 1) => {
                const params = new URLSearchParams();
                params.set('page', String(page));
                params.set('per_page', String(defaultPageSize));
                const url = `${favoritesUrl}?${params.toString()}`;

                fetchJson(url)
                    .then((data) => {
                        renderGrid(data);
                    })
                    .catch(() => {
                        if (grid) grid.innerHTML = '<div class="text-muted">Unable to load favorites.</div>';
                    });
            };

            const setGridMode = (mode) => {
                gridMode = mode;
                categoryStack.splice(0, categoryStack.length);
                currentTagId = null;

                if (mode === 'categories') {
                    loadCategories(null, false, 1);
                } else if (mode === 'tags') {
                    loadTags(1);
                } else if (mode === 'favorites') {
                    loadFavorites(1);
                }
            };

            const loadLevel = (categoryId, pushStack, page = 1) => loadCategories(categoryId, pushStack, page);

            if (grid) {
                grid.addEventListener('click', (event) => {
                    const backTile = event.target.closest('[data-action="back"]');
                    if (backTile) {
                        if (gridMode === 'tags' && currentTagId !== null) {
                            currentTagId = null;
                            loadTags(tagsListPage || 1);
                            return;
                        }

                        categoryStack.pop();
                        const prev = categoryStack[categoryStack.length - 1];
                        loadLevel(prev ? prev.id : null, false, prev?.page || 1);
                        return;
                    }

                    const tagTile = event.target.closest('[data-tag-id]');
                    if (tagTile) {
                        const tagId = tagTile.getAttribute('data-tag-id');
                        currentTagId = tagId;
                        gridMode = 'tags';
                        loadTagItems(tagId, 1);
                        return;
                    }

                    const categoryTile = event.target.closest('[data-category-id]');
                    if (categoryTile) {
                        const categoryId = categoryTile.getAttribute('data-category-id');
                        gridMode = 'categories';
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

                    if (gridMode === 'tags') {
                        if (currentTagId !== null) {
                            loadTagItems(currentTagId, page);
                        } else {
                            loadTags(page);
                        }
                        return;
                    }

                    if (gridMode === 'favorites') {
                        loadFavorites(page);
                        return;
                    }

                    const current = categoryStack[categoryStack.length - 1];
                    loadLevel(current ? current.id : null, false, page);
                });
            }

            document.querySelectorAll('.category-tabs .tab-btn').forEach((tab) => {
                tab.addEventListener('click', () => {
                    const mode = tab.getAttribute('data-mode') || 'categories';
                    applyTabUI(tab);
                    setGridMode(mode);
                });
            });

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

            searchInput.addEventListener('input', function () {
                clearTimeout(timer);
                const term = this.value;
                if (term.length < 2) {
                    resultsDiv.style.display = 'none';
                    return;
                }

                timer = setTimeout(() => {
                    fetch(`{{ route('sales.search') }}?term=${term}`)
                        .then(res => res.json())
                        .then(data => {
                            resultsDiv.innerHTML = '';
                            if (data.length > 0) {
                                data.forEach(item => {
                                    const div = document.createElement('div');
                                    div.className = 'p-2 border-bottom cursor-pointer hover-bg-light';
                                    div.style.cursor = 'pointer';
                                    div.innerHTML = `<strong>${item.name}</strong> <small class="text-muted">(${formatCurrency(item.unit_price || item.cost_price)})</small>`;
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
                        text: 'Do you want to add this item to the sales cart?',
                        showCancelButton: true,
                        confirmButtonText: 'Add',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: 'var(--primary)',
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
                form.action = "{{ route('sales.item.add') }}";
                form.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="item_id" value="${itemId}">`;
                document.body.appendChild(form);
                form.submit();
            }

            const paymentCurrencySelect = document.getElementById('payment_currency');
            const paymentAmountInput = document.getElementById('payment_amount');
            const paymentConverted = document.getElementById('payment-converted');
            const paymentSymbol = document.getElementById('payment-currency-symbol');

            const updatePaymentPreview = () => {
                if (!paymentCurrencySelect || !paymentAmountInput) return;
                const code = paymentCurrencySelect.value || baseCurrency.code || '';
                const meta = currencyMap.get(code) || baseCurrency;
                const normalized = normalizeCurrency(meta);
                const amount = parseFloat(paymentAmountInput.value);
                if (Number.isNaN(amount) || amount <= 0) {
                    if (paymentConverted) paymentConverted.textContent = '';
                    return;
                }

                const baseAmount = normalized.rate !== 0 ? amount / normalized.rate : amount;
                if (paymentConverted) {
                    paymentConverted.textContent = normalized.rate !== 1
                        ? `Base: ${formatCurrency(baseAmount, baseCurrency)}`
                        : '';
                }
                if (paymentSymbol) {
                    paymentSymbol.textContent = normalized.symbol || baseCurrency.symbol || '$';
                }
            };

            if (paymentCurrencySelect) {
                paymentCurrencySelect.addEventListener('change', updatePaymentPreview);
            }
            if (paymentAmountInput) {
                paymentAmountInput.addEventListener('input', updatePaymentPreview);
            }
            updatePaymentPreview();

            document.addEventListener('click', (e) => {
                if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                    resultsDiv.style.display = 'none';
                }
            });
        });
    </script>
    <style>
        .hover-bg-light:hover {
            background-color: #f8f9fa;
        }
    </style>
@endpush