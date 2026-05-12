<?php

namespace App\Http\Controllers;

use App\Models\PhpposCategory;
use App\Models\PhpposCurrencyExchangeRate;
use App\Models\PhpposCustomer;
use App\Models\PhpposItem;
use App\Models\PhpposItemKit;
use App\Models\PhpposLocation;
use App\Models\PhpposSupplier;
use App\Services\AppConfigService;
use App\Services\SalesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Throwable;

class SalesController extends Controller
{
    public function __construct(
        private readonly SalesService $salesService,
        private readonly AppConfigService $configService,
    ) {
    }

    private function getCart(): array
    {
        $defaultCart = [
            'items' => [],
            'payments' => [],
            'customer_id' => null,
            'supplier_id' => null,
            'sold_by_employee_id' => null,
            'location_id' => auth('employee')->user()?->location_id ?? 1,
        ];

        $cart = Session::get('sales_cart');

        return is_array($cart) ? array_merge($defaultCart, $cart) : $defaultCart;
    }

    public function index(): View
    {
        $cart = $this->getCart();

        $locations = PhpposLocation::where('deleted', 0)->orderBy('location_id')->get();
        $customers = PhpposCustomer::with('person')->orderBy('person_id')->get();
        $suppliers = PhpposSupplier::with('person')->orderBy('person_id')->get();
        $categories = PhpposCategory::where('deleted', 0)
            ->where('hide_from_grid', 0)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        $exchangeRates = PhpposCurrencyExchangeRate::query()
            ->orderBy('currency_code_to')
            ->get();

        $baseCurrencyCode = (string) $this->configService->get('currency_code', '');
        $baseCurrencySymbol = (string) $this->configService->get('currency_symbol', '$');
        $baseSymbolLocation = (string) $this->configService->get('currency_symbol_location', 'before');
        $baseDecimalsRaw = $this->configService->get('number_of_decimals');
        $baseDecimals = is_numeric($baseDecimalsRaw) ? (int) $baseDecimalsRaw : 2;
        $baseThousands = (string) $this->configService->get('thousands_separator', ',');
        if ($baseThousands === '') {
            $baseThousands = ',';
        }
        $baseDecimalPoint = (string) $this->configService->get('decimal_point', '.');
        if ($baseDecimalPoint === '') {
            $baseDecimalPoint = '.';
        }

        $baseCurrency = [
            'code' => $baseCurrencyCode,
            'symbol' => $baseCurrencySymbol,
            'symbol_location' => $baseSymbolLocation,
            'decimals' => $baseDecimals,
            'thousands_separator' => $baseThousands,
            'decimal_point' => $baseDecimalPoint,
            'rate' => 1.0,
        ];

        $currencyRates = $exchangeRates
            ->map(function (PhpposCurrencyExchangeRate $rate) use ($baseDecimals, $baseThousands, $baseDecimalPoint, $baseCurrencySymbol): array {
                $decimals = $rate->number_of_decimals;
                $decimalCount = is_numeric($decimals) ? (int) $decimals : $baseDecimals;
                $thousands = $rate->thousands_separator !== '' ? $rate->thousands_separator : $baseThousands;
                $decimalPoint = $rate->decimal_point !== '' ? $rate->decimal_point : $baseDecimalPoint;

                return [
                    'code' => (string) $rate->currency_code_to,
                    'symbol' => (string) ($rate->currency_symbol !== '' ? $rate->currency_symbol : $baseCurrencySymbol),
                    'symbol_location' => (string) ($rate->currency_symbol_location ?: 'before'),
                    'decimals' => $decimalCount,
                    'thousands_separator' => $thousands,
                    'decimal_point' => $decimalPoint,
                    'rate' => (float) $rate->exchange_rate,
                ];
            })
            ->values();

        $paymentTypes = array_values(array_unique(array_merge(
            ['Cash'],
            $this->configService->getAdditionalPaymentTypes(),
        )));

        $subtotal = 0.0;
        foreach ($cart['items'] as $item) {
            $subtotal += $item['unit_price'] * $item['quantity'] * (1 - $item['discount'] / 100);
        }

        $paymentTotal = 0.0;
        foreach ($cart['payments'] as $payment) {
            $paymentTotal += $payment['amount'];
        }

        $total = $subtotal;
        $amountDue = max(0, $total - $paymentTotal);

        return view('sales.index', compact(
            'cart',
            'locations',
            'customers',
            'suppliers',
            'categories',
            'paymentTypes',
            'subtotal',
            'total',
            'paymentTotal',
            'amountDue',
            'baseCurrency',
            'currencyRates',
        ));
    }

    public function categories(Request $request)
    {
        $categoryId = $request->input('category_id');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(8, (int) $request->input('per_page', 8)));
        $currentCategory = null;

        if ($categoryId) {
            $currentCategory = PhpposCategory::where('deleted', 0)
                ->select('id', 'name', 'parent_id')
                ->find($categoryId);
        }

        $isRootCategory = $currentCategory && $currentCategory->parent_id === null;
        $cart = $this->getCart();
        $supplierId = $cart['supplier_id'] ?? null;

        if ($categoryId && !$isRootCategory) {
            $itemsQuery = PhpposItem::where('deleted', 0)
                ->where('category_id', $categoryId);

            if ($supplierId) {
                $itemsQuery->where('supplier_id', $supplierId);
            }

            $items = $itemsQuery->orderBy('name')
                ->get(['item_id', 'name', 'unit_price'])
                ->map(function ($item) {
                    return [
                        'type' => 'item',
                        'id' => $item->item_id,
                        'name' => $item->name,
                        'price' => $item->unit_price,
                    ];
                });

            $kitsQuery = PhpposItemKit::where('deleted', 0)
                ->where('category_id', $categoryId);

            if ($supplierId) {
                $kitsQuery->where('supplier_id', $supplierId);
            }

            $kits = $kitsQuery->orderBy('name')
                ->get(['id', 'name', 'unit_price'])
                ->map(function ($kit) {
                    return [
                        'type' => 'kit',
                        'id' => $kit->id,
                        'name' => '[KIT] ' . $kit->name,
                        'price' => $kit->unit_price,
                    ];
                });

            $products = $items->concat($kits)->sortBy('name')->values();
            $total = $products->count();
            $lastPage = (int) max(1, (int) ceil($total / $perPage));
            $page = min($page, $lastPage);
            $paged = $products->slice(($page - 1) * $perPage, $perPage)->values();

            return response()->json([
                'level' => 'items',
                'categories' => [],
                'products' => $paged,
                'current' => $currentCategory,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => $lastPage,
                ],
            ]);
        }

        $categoryQuery = PhpposCategory::where('deleted', 0)
            ->where('hide_from_grid', 0)
            ->orderBy('name');

        if ($categoryId && $isRootCategory) {
            $categoryQuery->where('parent_id', $categoryId);
        } else {
            $categoryQuery->whereNull('parent_id');
        }

        $categoryPage = $categoryQuery->paginate($perPage, ['id', 'name', 'parent_id'], 'page', $page);
        $categories = collect($categoryPage->items());

        if ($categoryId && $categories->isEmpty()) {
            $itemsQuery = PhpposItem::where('deleted', 0)
                ->where('category_id', $categoryId);

            if ($supplierId) {
                $itemsQuery->where('supplier_id', $supplierId);
            }

            $items = $itemsQuery->orderBy('name')
                ->get(['item_id', 'name', 'unit_price'])
                ->map(function ($item) {
                    return [
                        'type' => 'item',
                        'id' => $item->item_id,
                        'name' => $item->name,
                        'price' => $item->unit_price,
                    ];
                });

            $kitsQuery = PhpposItemKit::where('deleted', 0)
                ->where('category_id', $categoryId);

            if ($supplierId) {
                $kitsQuery->where('supplier_id', $supplierId);
            }

            $kits = $kitsQuery->orderBy('name')
                ->get(['id', 'name', 'unit_price'])
                ->map(function ($kit) {
                    return [
                        'type' => 'kit',
                        'id' => $kit->id,
                        'name' => '[KIT] ' . $kit->name,
                        'price' => $kit->unit_price,
                    ];
                });

            $products = $items->concat($kits)->sortBy('name')->values();
            $total = $products->count();
            $lastPage = (int) max(1, (int) ceil($total / $perPage));
            $page = min($page, $lastPage);
            $paged = $products->slice(($page - 1) * $perPage, $perPage)->values();

            return response()->json([
                'level' => 'items',
                'categories' => [],
                'products' => $paged,
                'current' => $currentCategory,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => $lastPage,
                ],
            ]);
        }

        return response()->json([
            'level' => 'categories',
            'categories' => $categories,
            'products' => [],
            'current' => $currentCategory,
            'pagination' => [
                'page' => $categoryPage->currentPage(),
                'per_page' => $categoryPage->perPage(),
                'total' => $categoryPage->total(),
                'last_page' => $categoryPage->lastPage(),
            ],
        ]);
    }

    public function search(Request $request)
    {
        $term = $request->input('term');
        $cart = $this->getCart();
        $supplierId = $cart['supplier_id'] ?? null;

        $itemsQuery = PhpposItem::where('deleted', 0)
            ->where(function ($query) use ($term) {
                $query->where('name', 'LIKE', "%$term%")
                    ->orWhere('item_id', $term)
                    ->orWhere('product_id', $term);
            });

        if ($supplierId) {
            $itemsQuery->where('supplier_id', $supplierId);
        }

        $items = $itemsQuery->limit(10)
            ->get(['item_id', 'name', 'unit_price']);

        $kitsQuery = PhpposItemKit::where('deleted', 0)
            ->where(function ($query) use ($term) {
                $query->where('name', 'LIKE', "%$term%")
                    ->orWhere('item_kit_number', $term)
                    ->orWhere('product_id', $term);
            });

        if ($supplierId) {
            $kitsQuery->where('supplier_id', $supplierId);
        }

        $kits = $kitsQuery->limit(10)
            ->get(['id', 'name', 'unit_price'])
            ->map(function ($kit) {
                $kit->item_id = 'KIT ' . $kit->id;
                $kit->name = '[KIT] ' . $kit->name;
                return $kit;
            });

        $results = $items->concat($kits)->sortBy('name')->values();

        return response()->json($results);
    }

    public function addItem(Request $request): RedirectResponse
    {
        $request->validate(['item_id' => 'required|string']);

        $itemIdStr = $request->item_id;
        $cart = $this->getCart();
        $countBefore = count($cart['items']);
        $totalQtyBefore = array_sum(array_column($cart['items'], 'quantity'));

        if (str_starts_with($itemIdStr, 'KIT ')) {
            $kitId = (int) str_replace('KIT ', '', $itemIdStr);
            $kit = PhpposItemKit::with(['items.item', 'nestedKits'])->findOrFail($kitId);
            $this->addKitItemsToCart($kit, 1, $cart);

            $countAfter = count($cart['items']);
            $totalQtyAfter = array_sum(array_column($cart['items'], 'quantity'));
            if ($countAfter === $countBefore && $totalQtyAfter === $totalQtyBefore) {
                $this->addKitAsLineItem($kit, 1, $cart);
            }
        } else {
            $item = PhpposItem::findOrFail($itemIdStr);
            $this->addSingleItemToCart($item, 1, $cart);
        }

        Session::put('sales_cart', $cart);

        return redirect()->route('sales.index');
    }

    private function addKitItemsToCart($kit, $quantity, &$cart): void
    {
        foreach ($kit->items as $kitItem) {
            $item = $kitItem->item ?? PhpposItem::find($kitItem->item_id);
            if ($item) {
                $this->addSingleItemToCart($item, $kitItem->quantity * $quantity, $cart);
            }
        }

        foreach ($kit->nestedKits as $nestedKit) {
            $nKit = PhpposItemKit::with(['items.item', 'nestedKits'])->find($nestedKit->item_kit_item_kit);
            if ($nKit) {
                $this->addKitItemsToCart($nKit, $nestedKit->quantity * $quantity, $cart);
            }
        }
    }

    private function addKitAsLineItem($kit, $quantity, &$cart): void
    {
        $kitLineId = 'KIT_' . $kit->id;
        $existingKey = null;
        foreach ($cart['items'] as $key => $cartItem) {
            if (($cartItem['item_id'] ?? null) === $kitLineId) {
                $existingKey = $key;
                break;
            }
        }
        if ($existingKey !== null) {
            $cart['items'][$existingKey]['quantity'] += $quantity;
        } else {
            $cart['items'][] = [
                'item_id' => $kitLineId,
                'name' => '[KIT] ' . $kit->name,
                'quantity' => $quantity,
                'unit_price' => (float) ($kit->unit_price ?? 0),
                'discount' => 0,
            ];
        }
    }

    private function addSingleItemToCart($item, $quantity, &$cart): void
    {
        $existingKey = null;
        foreach ($cart['items'] as $key => $cartItem) {
            if ($cartItem['item_id'] == $item->item_id) {
                $existingKey = $key;
                break;
            }
        }

        if ($existingKey !== null) {
            $cart['items'][$existingKey]['quantity'] += $quantity;
        } else {
            $cart['items'][] = [
                'item_id' => $item->item_id,
                'name' => $item->name,
                'quantity' => $quantity,
                'unit_price' => (float) $item->unit_price,
                'discount' => 0,
            ];
        }
    }

    public function editItem(Request $request, int $index): RedirectResponse
    {
        $cart = $this->getCart();

        if (isset($cart['items'][$index])) {
            if ($request->has('quantity')) {
                $cart['items'][$index]['quantity'] = (float) $request->quantity;
            }
            if ($request->has('unit_price')) {
                $cart['items'][$index]['unit_price'] = (float) $request->unit_price;
            }
            if ($request->has('discount')) {
                $cart['items'][$index]['discount'] = (float) $request->discount;
            }
            Session::put('sales_cart', $cart);
        }

        return redirect()->route('sales.index');
    }

    public function removeItem(int $index): RedirectResponse
    {
        $cart = $this->getCart();

        if (isset($cart['items'][$index])) {
            unset($cart['items'][$index]);
            $cart['items'] = array_values($cart['items']);
            Session::put('sales_cart', $cart);
        }

        return redirect()->route('sales.index');
    }

    public function setSupplier(Request $request): RedirectResponse
    {
        $cart = $this->getCart();
        $cart['supplier_id'] = $request->supplier_id;
        Session::put('sales_cart', $cart);
        return redirect()->route('sales.index');
    }

    public function setCustomer(Request $request): RedirectResponse
    {
        $cart = $this->getCart();
        $cart['customer_id'] = $request->customer_id ?: null;
        Session::put('sales_cart', $cart);

        return redirect()->route('sales.index');
    }

    public function setSoldBy(Request $request): RedirectResponse
    {
        $cart = $this->getCart();
        $cart['sold_by_employee_id'] = $request->sold_by_employee_id ?: null;
        Session::put('sales_cart', $cart);

        return redirect()->route('sales.index');
    }

    public function setLocation(Request $request): RedirectResponse
    {
        $cart = $this->getCart();
        $cart['location_id'] = $request->location_id ?: $cart['location_id'];
        Session::put('sales_cart', $cart);

        return redirect()->route('sales.index');
    }

    public function addPayment(Request $request): RedirectResponse
    {
        $request->validate([
            'payment_type' => ['required', 'string', 'max:60'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency_code' => ['nullable', 'string', 'max:10'],
        ]);

        $baseCurrencyCode = (string) $this->configService->get('currency_code', '');
        $baseCurrencySymbol = (string) $this->configService->get('currency_symbol', '$');
        $baseSymbolLocation = (string) $this->configService->get('currency_symbol_location', 'before');
        $baseDecimalsRaw = $this->configService->get('number_of_decimals');
        $baseDecimals = is_numeric($baseDecimalsRaw) ? (int) $baseDecimalsRaw : 2;
        $baseThousands = (string) $this->configService->get('thousands_separator', ',');
        if ($baseThousands === '') {
            $baseThousands = ',';
        }
        $baseDecimalPoint = (string) $this->configService->get('decimal_point', '.');
        if ($baseDecimalPoint === '') {
            $baseDecimalPoint = '.';
        }

        $currencyCode = (string) $request->input('currency_code', $baseCurrencyCode);
        if ($currencyCode === '') {
            $currencyCode = $baseCurrencyCode;
        }

        $rateRow = null;
        if ($currencyCode !== '' && $currencyCode !== $baseCurrencyCode) {
            $rateRow = PhpposCurrencyExchangeRate::query()
                ->where('currency_code_to', $currencyCode)
                ->first();

            if (! $rateRow) {
                return back()->withErrors(['amount' => 'Unknown currency selected.']);
            }
        }

        $exchangeRate = $rateRow ? (float) $rateRow->exchange_rate : 1.0;
        if ($exchangeRate <= 0) {
            return back()->withErrors(['amount' => 'Invalid exchange rate for selected currency.']);
        }

        $currencyAmount = (float) $request->amount;
        $baseAmount = $exchangeRate !== 1.0 ? ($currencyAmount / $exchangeRate) : $currencyAmount;

        $currencySymbol = ($rateRow && $rateRow->currency_symbol !== '') ? $rateRow->currency_symbol : $baseCurrencySymbol;
        $currencySymbolLocation = $rateRow ? ($rateRow->currency_symbol_location ?: $baseSymbolLocation) : $baseSymbolLocation;
        $currencyDecimalsRaw = $rateRow ? $rateRow->number_of_decimals : null;
        $currencyDecimals = is_numeric($currencyDecimalsRaw) ? (int) $currencyDecimalsRaw : $baseDecimals;
        $currencyThousands = ($rateRow && $rateRow->thousands_separator !== '') ? $rateRow->thousands_separator : $baseThousands;
        $currencyDecimalPoint = ($rateRow && $rateRow->decimal_point !== '') ? $rateRow->decimal_point : $baseDecimalPoint;

        $cart = $this->getCart();
        $cart['payments'][] = [
            'type' => $request->payment_type,
            'amount' => $baseAmount,
            'currency_code' => $currencyCode !== '' ? $currencyCode : $baseCurrencyCode,
            'currency_amount' => $currencyAmount,
            'exchange_rate' => $exchangeRate,
            'currency_symbol' => $currencySymbol,
            'currency_symbol_location' => $currencySymbolLocation,
            'currency_number_of_decimals' => $currencyDecimals,
            'currency_thousands_separator' => $currencyThousands,
            'currency_decimal_point' => $currencyDecimalPoint,
        ];

        Session::put('sales_cart', $cart);

        return redirect()->route('sales.index');
    }

    public function removePayment(int $index): RedirectResponse
    {
        $cart = $this->getCart();

        if (isset($cart['payments'][$index])) {
            unset($cart['payments'][$index]);
            $cart['payments'] = array_values($cart['payments']);
            Session::put('sales_cart', $cart);
        }

        return redirect()->route('sales.index');
    }

    public function complete(Request $request): RedirectResponse
    {
        $cart = $this->getCart();

        if (empty($cart['items'])) {
            return redirect()->back()->with('error', 'Cart is empty.');
        }

        $customerName = null;
        if ($cart['customer_id']) {
            $customer = PhpposCustomer::with('person')->find($cart['customer_id']);
            if ($customer && $customer->person) {
                $customerName = trim($customer->person->first_name . ' ' . $customer->person->last_name);
            }
        }

        $comment = $request->input('comment');

        try {
            // Strip kit-level fallback rows (KIT_*) — they have no real integer item_id
            $saleItems = array_values(array_filter($cart['items'], static fn($item) => !str_starts_with((string) ($item['item_id'] ?? ''), 'KIT_')));

            if (empty($saleItems)) {
                return redirect()->back()->with('error', 'No valid items in cart. Add component items to the kit before selling.');
            }

            $saleId = $this->salesService->createSaleFromCart(
                (int) $cart['location_id'],
                (int) auth('employee')->id(),
                $saleItems,
                $cart['payments'],
                $customerName,
                $comment,
                (int) ($cart['sold_by_employee_id'] ?? auth('employee')->id()),
            );

            Session::forget('sales_cart');

            return redirect()->route('sales.receipt', ['sale' => $saleId])
                ->with('status', 'Sale #' . $saleId . ' completed.');
        } catch (Throwable $e) {
            return back()->withErrors(['sale' => $e->getMessage()]);
        }
    }

    public function cancel(): RedirectResponse
    {
        Session::forget('sales_cart');

        return redirect()->route('sales.index');
    }

    public function receipt(int $sale): View
    {
        $saleRow = DB::table('phppos_sales as s')
            ->join('phppos_locations as l', 'l.location_id', '=', 's.location_id')
            ->join('phppos_people as p', 'p.person_id', '=', 's.employee_id')
            ->select(
                's.*',
                'l.name as location_name',
                'l.address_1',
                'l.address_2',
                'l.city',
                'l.state',
                'l.zip',
                'l.country',
                'l.phone as location_phone',
                'p.first_name',
                'p.last_name'
            )
            ->where('s.sale_id', $sale)
            ->firstOrFail();

        $lines = DB::table('phppos_sales_items as si')
            ->join('phppos_items as i', 'i.item_id', '=', 'si.item_id')
            ->select(
                'si.*',
                'i.name as item_name',
                'i.item_number',
                'i.product_id',
                'i.size',
                'i.description'
            )
            ->where('si.sale_id', $sale)
            ->get()
            ->map(static function ($row) use ($sale): array {
                $arr = (array) $row;
                $returnedQty = (float) DB::table('phppos_sales_item_returns')
                    ->where('sale_item_id', $arr['id'])
                    ->sum('quantity_returned');

                $arr['returned_qty'] = $returnedQty;
                $arr['returnable_qty'] = max(0, (float) $arr['quantity_purchased'] - $returnedQty);

                return $arr;
            })
            ->values()
            ->all();

        $itemsSold = 0.0;
        $itemsReturned = 0.0;
        foreach ($lines as $line) {
            $itemsSold += (float) $line['quantity_purchased'];
            $itemsReturned += (float) $line['returned_qty'];
        }

        $payments = DB::table('phppos_sales_payments')
            ->where('sale_id', $sale)
            ->get()
            ->map(static fn($row): array => (array) $row)
            ->values()
            ->all();

        $settings = DB::table('phppos_receipt_settings')->where('id', 1)->first();

        $companyLogoId = $this->configService->get('company_logo');
        $companyLogoUrl = $companyLogoId ? route('app_files.view', ['fileId' => $companyLogoId]) : null;

        $company = (string) $this->configService->get('company', '');
        $taxId = (string) $this->configService->get('tax_id', '');
        $website = (string) $this->configService->get('website', '');
        $returnPolicy = (string) $this->configService->get('return_policy', '');
        $receiptTitle = (string) $this->configService->get('override_receipt_title', '');

        $baseCurrencyCode = (string) $this->configService->get('currency_code', '');
        $baseCurrencySymbol = (string) $this->configService->get('currency_symbol', '$');
        $baseSymbolLocation = (string) $this->configService->get('currency_symbol_location', 'before');
        $baseDecimalsRaw = $this->configService->get('number_of_decimals');
        $baseDecimals = is_numeric($baseDecimalsRaw) ? (int) $baseDecimalsRaw : 2;
        $baseThousands = (string) $this->configService->get('thousands_separator', ',');
        if ($baseThousands === '') {
            $baseThousands = ',';
        }
        $baseDecimalPoint = (string) $this->configService->get('decimal_point', '.');
        if ($baseDecimalPoint === '') {
            $baseDecimalPoint = '.';
        }

        $baseCurrency = [
            'code' => $baseCurrencyCode,
            'symbol' => $baseCurrencySymbol,
            'symbol_location' => $baseSymbolLocation,
            'decimals' => $baseDecimals,
            'thousands_separator' => $baseThousands,
            'decimal_point' => $baseDecimalPoint,
            'rate' => 1.0,
        ];

        return view('sales.receipt', [
            'sale' => $saleRow,
            'lines' => $lines,
            'payments' => $payments,
            'settings' => $settings,
            'companyLogoUrl' => $companyLogoUrl,
            'company' => $company,
            'taxId' => $taxId,
            'website' => $website,
            'returnPolicy' => $returnPolicy,
            'receiptTitle' => $receiptTitle,
            'itemsSold' => $itemsSold,
            'itemsReturned' => $itemsReturned,
            'barcodeType' => (string) ($this->configService->get('barcode_type') ?: 'Code128'),
            'barcodeWidth' => (float) ($this->configService->get('barcode_width') ?: 1.5),
            'barcodeHeight' => (float) ($this->configService->get('barcode_height') ?: 36),
            'barcodeFontSize' => (int) ($this->configService->get('barcode_font_size') ?: 12),
            'baseCurrency' => $baseCurrency,
        ]);
    }

    public function returnItems(Request $request, int $sale): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
            'returns' => ['required', 'array', 'min:1'],
            'returns.*.sale_item_id' => ['required', 'integer', 'exists:phppos_sales_items,id'],
            'returns.*.quantity' => ['required', 'numeric', 'min:0'],
        ]);

        $lines = collect($data['returns'])
            ->map(static fn(array $row): array => [
                'sale_item_id' => (int) $row['sale_item_id'],
                'quantity' => (float) $row['quantity'],
            ])
            ->filter(static fn(array $row): bool => $row['quantity'] > 0)
            ->values()
            ->all();

        if (empty($lines)) {
            return back()->withErrors(['return' => 'Enter at least one quantity greater than 0.']);
        }

        try {
            $this->salesService->returnSaleItems($sale, (int) auth('employee')->id(), $lines, $data['reason'] ?? null);

            return redirect()->route('sales.receipt', ['sale' => $sale])->with('status', 'Return posted successfully.');
        } catch (Throwable $e) {
            return back()->withErrors(['return' => $e->getMessage()]);
        }
    }

    public function settings(): View
    {
        $settings = DB::table('phppos_receipt_settings')->where('id', 1)->first();

        return view('sales.settings', compact('settings'));
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'footer' => ['required', 'string', 'max:255'],
            'paper_size' => ['required', 'string', 'max:20'],
            'show_cashier' => ['nullable', 'boolean'],
            'show_customer' => ['nullable', 'boolean'],
        ]);

        DB::table('phppos_receipt_settings')->updateOrInsert(
            ['id' => 1],
            [
                'title' => $data['title'],
                'footer' => $data['footer'],
                'paper_size' => $data['paper_size'],
                'show_cashier' => (bool) ($data['show_cashier'] ?? false),
                'show_customer' => (bool) ($data['show_customer'] ?? false),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return redirect()->route('sales.settings')->with('status', 'Receipt settings updated.');
    }
}
