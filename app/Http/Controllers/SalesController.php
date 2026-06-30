<?php

namespace App\Http\Controllers;

use App\Models\ItemVariation;
use App\Models\PhpposCategory;
use App\Models\PhpposCurrencyExchangeRate;
use App\Models\PhpposCustomer;
use App\Models\PhpposItem;
use App\Models\PhpposItemKit;
use App\Models\PhpposLocation;
use App\Models\PhpposRegister;
use App\Models\PhpposRegisterLog;
use App\Models\PhpposSupplier;
use App\Models\PhpposTag;
use App\Services\AppConfigService;
use App\Services\EmployeeService;
use App\Services\LocationContextService;
use App\Services\SalesService;
use Illuminate\Http\JsonResponse;
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
        private readonly LocationContextService $locationContextService,
        private readonly EmployeeService $employeeService,
    ) {}

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

        $resolvedLocationId = $this->locationContextService->resolveLocationId($defaultCart['location_id']);
        $defaultCart['location_id'] = $resolvedLocationId;

        $cart = Session::get('sales_cart');
        $cart = is_array($cart) ? array_merge($defaultCart, $cart) : $defaultCart;
        $cart['location_id'] = $resolvedLocationId;

        return $cart;
    }

    public function index(): View
    {
        $cart = $this->getCart();
        $locationId = $cart['location_id'];
        $locations = PhpposLocation::where('deleted', 0)
            ->where('location_id', $locationId)
            ->orderBy('location_id')
            ->get();
        $registerId = session('register_id');
        $currentRegister = PhpposRegister::find($registerId);
        $registerLog = PhpposRegisterLog::with('employeeOpen.person')
            ->where('register_id', $registerId)
            ->whereNull('shift_end')
            ->first();
        $registers = PhpposRegister::where('location_id', $locationId)
            ->where('deleted', 0)
            ->get();
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
        $baseDecimals = is_numeric($baseDecimalsRaw) ? (int) $baseDecimalsRaw : 5;
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
            'currentRegister',
            'registerLog',
            'registers',
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

        if ($categoryId && ! $isRootCategory) {
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
                        'name' => '[KIT] '.$kit->name,
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
                        'name' => '[KIT] '.$kit->name,
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

    public function tags(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(24, (int) $request->input('per_page', 24)));

        $tagPage = PhpposTag::query()
            ->where('deleted', 0)
            ->orderBy('name')
            ->paginate($perPage, ['id', 'name'], 'page', $page);

        return response()->json([
            'level' => 'tags',
            'tags' => collect($tagPage->items())->values(),
            'current' => null,
            'pagination' => [
                'page' => $tagPage->currentPage(),
                'per_page' => $tagPage->perPage(),
                'total' => $tagPage->total(),
                'last_page' => $tagPage->lastPage(),
            ],
        ]);
    }

    public function tagItems(Request $request, int $tagId): JsonResponse
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(24, (int) $request->input('per_page', 24)));

        $tag = PhpposTag::query()
            ->where('deleted', 0)
            ->select('id', 'name')
            ->findOrFail($tagId);

        $cart = $this->getCart();
        $supplierId = $cart['supplier_id'] ?? null;

        $itemsQuery = DB::table('phppos_items_tags as it')
            ->join('phppos_items as i', 'i.item_id', '=', 'it.item_id')
            ->where('it.tag_id', $tagId)
            ->where('i.deleted', 0)
            ->select('i.item_id as id', 'i.name as name', 'i.unit_price as price');

        if ($supplierId) {
            $itemsQuery->where('i.supplier_id', $supplierId);
        }

        $items = $itemsQuery
            ->orderBy('i.name')
            ->get()
            ->map(fn ($row) => [
                'type' => 'item',
                'id' => $row->id,
                'name' => $row->name,
                'price' => (float) ($row->price ?? 0),
            ]);

        $kitsQuery = DB::table('phppos_item_kits_tags as ikt')
            ->join('phppos_item_kits as k', 'k.id', '=', 'ikt.item_kit_id')
            ->where('ikt.tag_id', $tagId)
            ->where('k.deleted', 0)
            ->select('k.id as id', 'k.name as name', 'k.unit_price as price');

        if ($supplierId) {
            $kitsQuery->where('k.supplier_id', $supplierId);
        }

        $kits = $kitsQuery
            ->orderBy('k.name')
            ->get()
            ->map(fn ($row) => [
                'type' => 'kit',
                'id' => $row->id,
                'name' => '[KIT] '.$row->name,
                'price' => (float) ($row->price ?? 0),
            ]);

        $products = $items->concat($kits)->sortBy('name')->values();
        $total = $products->count();
        $lastPage = (int) max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $paged = $products->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'level' => 'items',
            'categories' => [],
            'tags' => [],
            'products' => $paged,
            'current' => $tag,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ]);
    }

    public function favorites(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(24, (int) $request->input('per_page', 24)));

        $cart = $this->getCart();
        $supplierId = $cart['supplier_id'] ?? null;

        $itemsQuery = PhpposItem::query()
            ->where('deleted', 0)
            ->where('is_favorite', 1);

        if ($supplierId) {
            $itemsQuery->where('supplier_id', $supplierId);
        }

        $items = $itemsQuery
            ->orderBy('name')
            ->get(['item_id', 'name', 'unit_price'])
            ->map(fn (PhpposItem $item) => [
                'type' => 'item',
                'id' => $item->item_id,
                'name' => $item->name,
                'price' => (float) ($item->unit_price ?? 0),
            ]);

        $kitsQuery = PhpposItemKit::query()
            ->where('deleted', 0)
            ->where('is_favorite', 1);

        if ($supplierId) {
            $kitsQuery->where('supplier_id', $supplierId);
        }

        $kits = $kitsQuery
            ->orderBy('name')
            ->get(['id', 'name', 'unit_price'])
            ->map(fn (PhpposItemKit $kit) => [
                'type' => 'kit',
                'id' => $kit->id,
                'name' => '[KIT] '.$kit->name,
                'price' => (float) ($kit->unit_price ?? 0),
            ]);

        $products = $items->concat($kits)->sortBy('name')->values();
        $total = $products->count();
        $lastPage = (int) max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $paged = $products->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'level' => 'items',
            'categories' => [],
            'tags' => [],
            'products' => $paged,
            'current' => null,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ]);
    }

    public function search(Request $request)
    {
        $term = $request->input('term');
        $cart = $this->getCart();
        $supplierId = $cart['supplier_id'] ?? null;

        $isSkuSearch = str_starts_with($term, '#');
        if ($isSkuSearch) {
            $term = substr($term, 1);
        }

        $itemsQuery = PhpposItem::where('deleted', 0);
        if ($isSkuSearch) {
            $itemsQuery->where(function ($query) use ($term) {
                $query->where('item_id', $term)
                    ->orWhere('item_number', 'LIKE', "%$term%")
                    ->orWhere('product_id', 'LIKE', "%$term%");
            });
        } else {
            $itemsQuery->where(function ($query) use ($term) {
                $query->where('name', 'LIKE', "%$term%")
                    ->orWhere('item_number', 'LIKE', "%$term%")
                    ->orWhere('item_id', $term)
                    ->orWhere('product_id', 'LIKE', "%$term%");
            });
        }

        if ($supplierId) {
            $itemsQuery->where('supplier_id', $supplierId);
        }

        $items = $itemsQuery->limit(10)
            ->get(['item_id', 'name', 'unit_price'])
            ->map(function ($item) {
                $item->type = 'item';

                return $item;
            });

        $kitsQuery = PhpposItemKit::where('deleted', 0);
        if ($isSkuSearch) {
            $kitsQuery->where(function ($query) use ($term) {
                $query->where('item_kit_number', $term)
                    ->orWhere('product_id', 'LIKE', "%$term%");
            });
        } else {
            $kitsQuery->where(function ($query) use ($term) {
                $query->where('name', 'LIKE', "%$term%")
                    ->orWhere('item_kit_number', $term)
                    ->orWhere('product_id', 'LIKE', "%$term%");
            });
        }

        if ($supplierId) {
            $kitsQuery->where('supplier_id', $supplierId);
        }

        $kits = $kitsQuery->limit(10)
            ->get(['id', 'name', 'unit_price'])
            ->map(function ($kit) {
                $kit->item_id = 'KIT '.$kit->id;
                $kit->name = '[KIT] '.$kit->name;
                $kit->type = 'kit';

                return $kit;
            });

        $variationsQuery = ItemVariation::select([
            'phppos_item_variations.id',
            'phppos_item_variations.name',
            'phppos_item_variations.unit_price',
            'phppos_item_variations.cost_price',
            'phppos_items.name as parent_item_name',
        ])
            ->join('phppos_items', 'phppos_item_variations.item_id', '=', 'phppos_items.item_id')
            ->where('phppos_item_variations.deleted', 0);

        if ($isSkuSearch) {
            $variationsQuery->where(function ($query) use ($term) {
                $query->where('phppos_item_variations.item_number', 'LIKE', "%$term%");
            });
        } else {
            $variationsQuery->where(function ($query) use ($term) {
                $query->where('phppos_item_variations.name', 'LIKE', "%$term%")
                    ->orWhere('phppos_item_variations.item_number', 'LIKE', "%$term%");
            });
        }

        $variations = $variationsQuery->limit(10)
            ->get()
            ->map(function ($variation) {
                $variation->item_id = 'VAR '.$variation->id;
                $variation->display_name = $variation->name.' ('.$variation->parent_item_name.')';
                $variation->type = 'variant';
                unset($variation->parent_item_name, $variation->id);

                return $variation;
            });

        $results = $items->concat($kits)->concat($variations)->sortBy('name')->values();

        return response()->json($results);
    }

    public function addItem(Request $request): RedirectResponse
    {
        $request->validate([
            'item_id' => 'required|string',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $quantity = (int) ($request->quantity ?? 1);
        $itemIdStr = $request->item_id;
        $cart = $this->getCart();
        $countBefore = count($cart['items']);
        $totalQtyBefore = array_sum(array_column($cart['items'], 'quantity'));

        if (str_starts_with($itemIdStr, 'VAR ')) {
            $variationId = (int) str_replace('VAR ', '', $itemIdStr);
            $variation = ItemVariation::findOrFail($variationId);
            $parentItem = PhpposItem::findOrFail($variation->item_id);
            $this->addSingleItemToCart($parentItem, $quantity, $cart, $variation);
        } elseif (str_starts_with($itemIdStr, 'KIT ')) {
            $kitId = (int) str_replace('KIT ', '', $itemIdStr);
            $kit = PhpposItemKit::findOrFail($kitId);
            $this->addKitToCart($kit, $quantity, $cart);
        } else {
            $item = PhpposItem::findOrFail($itemIdStr);
            $this->addSingleItemToCart($item, $quantity, $cart);
        }

        Session::put('sales_cart', $cart);

        return redirect()->route('sales.index');
    }

    private function addKitToCart($kit, $quantity, &$cart): void
    {
        $kitId = (int) $kit->id;
        $existingKey = null;
        foreach ($cart['items'] as $key => $cartItem) {
            if (($cartItem['type'] ?? 'item') === 'kit' && ($cartItem['item_kit_id'] ?? null) === $kitId) {
                $existingKey = $key;
                break;
            }
        }

        $customerDiscount = 0;
        if ($cart['customer_id']) {
            $customer = PhpposCustomer::find($cart['customer_id']);
            if ($customer && (float) $customer->discount_percent > 0) {
                // TODO: Apply discount for item kits when customer discount_percent is set
                // Kit discount logic goes here (check kit-level discountable flag or max_discount_percent)
            }
        }

        if ($existingKey !== null) {
            $cart['items'][$existingKey]['quantity'] += $quantity;
        } else {
            $cart['items'][] = [
                'type' => 'kit',
                'item_kit_id' => $kitId,
                'name' => $kit->name,
                'quantity' => $quantity,
                'unit_price' => (float) ($kit->unit_price ?? 0),
                'discount' => 0,
            ];
        }
    }

    private function addSingleItemToCart($item, $quantity, &$cart, $variation = null): void
    {
        $itemId = $item->item_id;
        $variationId = $variation?->id;
        $existingKey = null;
        foreach ($cart['items'] as $key => $cartItem) {
            if (($cartItem['type'] ?? 'item') !== 'item') {
                continue;
            }
            if ((int) $cartItem['item_id'] === $itemId && ($cartItem['variation_id'] ?? null) === $variationId) {
                $existingKey = $key;
                break;
            }
        }

        $customerDiscount = 0;
        if ($cart['customer_id']) {
            $customer = PhpposCustomer::find($cart['customer_id']);
            if ($customer && (float) $customer->discount_percent > 0 && $item->discountable) {
                $customerDiscount = (float) $customer->discount_percent;
            }
        }

        if ($existingKey !== null) {
            $cart['items'][$existingKey]['quantity'] += $quantity;
        } else {
            $entry = [
                'item_id' => $item->item_id,
                'name' => $variation ? $variation->name.' ('.$item->name.')' : $item->name,
                'quantity' => $quantity,
                'unit_price' => (float) ($variation?->unit_price ?? $item->unit_price),
                'discount' => $customerDiscount,
            ];
            if ($variation) {
                $entry['variation_id'] = $variation->id;
            }
            $cart['items'][] = $entry;
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

        if ($cart['customer_id']) {
            $customer = PhpposCustomer::find($cart['customer_id']);
            $discountPercent = $customer ? (float) $customer->discount_percent : 0;

            if ($discountPercent > 0) {
                $nonKitItemIds = [];
                foreach ($cart['items'] as $item) {
                    if (($item['type'] ?? 'item') !== 'kit') {
                        $nonKitItemIds[] = (int) $item['item_id'];
                    }
                }

                $discountableIds = [];
                if ($nonKitItemIds) {
                    $discountableIds = PhpposItem::whereIn('item_id', $nonKitItemIds)
                        ->where('discountable', true)
                        ->pluck('item_id')
                        ->map(fn ($id) => (int) $id)
                        ->toArray();
                }

                foreach ($cart['items'] as $key => $item) {
                    if (($item['type'] ?? 'item') === 'kit') {
                        continue;
                    }
                    if (in_array((int) $item['item_id'], $discountableIds)) {
                        $cart['items'][$key]['discount'] = $discountPercent;
                    }
                }
            }
        }

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
        $cart['location_id'] = $this->locationContextService->resolveLocationId($cart['location_id'] ?? null);
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
        $baseDecimals = is_numeric($baseDecimalsRaw) ? (int) $baseDecimalsRaw : 5;
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

        $subtotal = 0.0;
        foreach ($cart['items'] as $item) {
            $subtotal += $item['unit_price'] * $item['quantity'] * (1 - $item['discount'] / 100);
        }

        $paymentTotal = 0.0;
        foreach ($cart['payments'] as $payment) {
            $paymentTotal += $payment['amount'];
        }

        $amountDue = max(0, $subtotal - $paymentTotal);

        if ($amountDue > 0) {
            return redirect()->back()->with('error', 'Complete all payments before completing the sale.');
        }

        $customerName = null;
        if ($cart['customer_id']) {
            $customer = PhpposCustomer::with('person')->find($cart['customer_id']);
            if ($customer && $customer->person) {
                $customerName = trim($customer->person->first_name.' '.$customer->person->last_name);
            }
        }

        $comment = $request->input('comment');

        try {
            $regularItems = [];
            $kitEntries = [];

            foreach ($cart['items'] as $item) {
                if (($item['type'] ?? 'item') === 'kit') {
                    $kitEntries[] = $item;
                } else {
                    $regularItems[] = $item;
                }
            }

            if (empty($regularItems) && empty($kitEntries)) {
                return redirect()->back()->with('error', 'Cart is empty.');
            }

            $locationId = $this->locationContextService->resolveLocationId($cart['location_id'] ?? null);

            $saleId = $this->salesService->createSaleFromCart(
                $locationId,
                (int) auth('employee')->id(),
                $regularItems,
                $cart['payments'],
                $customerName,
                $comment,
                (int) ($cart['sold_by_employee_id'] ?? auth('employee')->id()),
                session('register_id'),
                kitEntries: $kitEntries,
            );

            Session::forget('sales_cart');

            return redirect()->route('sales.receipt', ['sale' => $saleId])
                ->with('status', 'Sale #'.$saleId.' completed.');
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
            ->map(static function ($row): array {
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
            ->map(static fn ($row): array => (array) $row)
            ->values()
            ->all();

        $kitLines = DB::table('phppos_sales_item_kits as sik')
            ->leftJoin('phppos_item_kits as ik', 'ik.id', '=', 'sik.item_kit_id')
            ->where('sik.sale_id', $sale)
            ->select('sik.*', 'ik.name as item_kit_name')
            ->get()
            ->map(fn ($row) => (array) $row)
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
        $baseDecimals = is_numeric($baseDecimalsRaw) ? (int) $baseDecimalsRaw : 5;
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
            'kitLines' => $kitLines,
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
            ->map(static fn (array $row): array => [
                'sale_item_id' => (int) $row['sale_item_id'],
                'quantity' => (float) $row['quantity'],
            ])
            ->filter(static fn (array $row): bool => $row['quantity'] > 0)
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

    public function showRegisterOpenForm(Request $request): View
    {
        $employeeId = auth('employee')->id();
        $locationId = session('employee_current_location_id') ?? auth('employee')->user()?->location_id ?? 1;

        $registerId = session('register_id');
        if (! $registerId) {
            $defaultReg = $this->employeeService->getDefaultRegister($employeeId, $locationId);
            if ($defaultReg) {
                $registerId = $defaultReg['register_id'];
            } else {
                $firstReg = PhpposRegister::where('location_id', $locationId)->where('deleted', 0)->first();
                if ($firstReg) {
                    $registerId = $firstReg->register_id;
                } else {
                    $newReg = PhpposRegister::create([
                        'location_id' => $locationId,
                        'name' => 'Default Register',
                        'deleted' => 0,
                    ]);
                    $registerId = $newReg->register_id;
                }
            }
            session(['register_id' => $registerId]);
        }

        $registers = PhpposRegister::where('location_id', $locationId)
            ->where('deleted', 0)
            ->get();
        $currentRegister = PhpposRegister::find($registerId);

        $lastLog = PhpposRegisterLog::where('register_id', $registerId)
            ->whereNotNull('shift_end')
            ->orderBy('register_log_id', 'desc')
            ->first();

        $lastCloseAmount = 0.0;
        if ($lastLog) {
            $lastCashPayment = DB::table('phppos_register_log_payments')
                ->where('register_log_id', $lastLog->register_log_id)
                ->where('payment_type', 'Cash')
                ->first();
            $lastCloseAmount = $lastCashPayment ? (float) $lastCashPayment->close_amount : 0.0;
        }

        return view('sales.register_open', compact('registers', 'currentRegister', 'lastCloseAmount'));
    }

    public function openRegister(Request $request): RedirectResponse
    {
        $request->validate([
            'opening_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $registerId = session('register_id');
        if (! $registerId) {
            return redirect()->route('sales.index');
        }

        $logId = DB::table('phppos_register_log')->insertGetId([
            'employee_id_open' => auth('employee')->id(),
            'register_id' => $registerId,
            'shift_start' => now(),
            'notes' => $request->notes,
            'deleted' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('phppos_register_log_payments')->insert([
            'register_log_id' => $logId,
            'payment_type' => 'Cash',
            'open_amount' => $request->opening_amount,
            'close_amount' => 0,
            'payment_sales_amount' => 0,
            'total_payment_additions' => 0,
            'total_payment_subtractions' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        session(['register_log_id' => $logId]);

        return redirect()->route('sales.index')->with('status', 'Register opened successfully.');
    }

    public function changeRegister(Request $request): RedirectResponse
    {
        $request->validate([
            'register_id' => 'required|exists:phppos_registers,register_id',
        ]);

        $registerId = (int) $request->register_id;
        $locationId = session('employee_current_location_id') ?? auth('employee')->user()?->location_id ?? 1;

        // Verify register belongs to current location and is not deleted
        $registerExists = PhpposRegister::where('register_id', $registerId)
            ->where('location_id', $locationId)
            ->where('deleted', 0)
            ->exists();

        if ($registerExists) {
            session(['register_id' => $registerId]);
            session()->forget('register_log_id');
        }

        return redirect()->route('sales.index');
    }

    public function showRegisterCloseForm(Request $request): View|RedirectResponse
    {
        $registerId = session('register_id');
        $logId = session('register_log_id');

        if (! $registerId || ! $logId) {
            return redirect()->route('sales.index');
        }

        $currentRegister = PhpposRegister::find($registerId);
        $registerLog = PhpposRegisterLog::with('employeeOpen.person')->find($logId);

        if (! $registerLog || $registerLog->shift_end) {
            session()->forget('register_log_id');

            return redirect()->route('sales.index');
        }

        $logPayments = DB::table('phppos_register_log_payments')
            ->where('register_log_id', $logId)
            ->get();

        $paymentTypes = array_values(array_unique(array_merge(
            ['Cash', 'Check', 'Credit Card', 'Debit Card'],
            $this->configService->getAdditionalPaymentTypes(),
            $logPayments->pluck('payment_type')->toArray()
        )));

        $paymentsData = [];
        foreach ($paymentTypes as $type) {
            $payment = $logPayments->firstWhere('payment_type', $type);
            $open = $payment ? (float) $payment->open_amount : 0.0;
            $sales = $payment ? (float) $payment->payment_sales_amount : 0.0;
            $additions = $payment ? (float) $payment->total_payment_additions : 0.0;
            $subs = $payment ? (float) $payment->total_payment_subtractions : 0.0;
            $expected = $open + $sales + $additions - $subs;

            $paymentsData[$type] = [
                'open' => $open,
                'sales' => $sales,
                'expected' => $expected,
            ];
        }

        $baseCurrencyCode = (string) $this->configService->get('currency_code', '');
        $baseCurrencySymbol = (string) $this->configService->get('currency_symbol', '$');
        $baseSymbolLocation = (string) $this->configService->get('currency_symbol_location', 'before');
        $baseDecimalsRaw = $this->configService->get('number_of_decimals');
        $baseDecimals = is_numeric($baseDecimalsRaw) ? (int) $baseDecimalsRaw : 5;
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
        ];

        return view('sales.register_close', compact('currentRegister', 'registerLog', 'paymentsData', 'baseCurrency'));
    }

    public function closeRegister(Request $request): RedirectResponse
    {
        $request->validate([
            'closed_payments' => 'required|array',
            'closed_payments.*.actual' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $logId = session('register_log_id');
        if (! $logId) {
            return redirect()->route('sales.index');
        }

        $registerLog = PhpposRegisterLog::find($logId);
        if (! $registerLog || $registerLog->shift_end) {
            session()->forget('register_log_id');

            return redirect()->route('sales.index');
        }

        DB::transaction(function () use ($logId, $request, $registerLog) {
            DB::table('phppos_register_log')
                ->where('register_log_id', $logId)
                ->update([
                    'employee_id_close' => auth('employee')->id(),
                    'shift_end' => now(),
                    'notes' => trim(($registerLog->notes ? $registerLog->notes."\n" : '').'Closing Notes: '.$request->notes),
                    'updated_at' => now(),
                ]);

            foreach ($request->closed_payments as $type => $data) {
                $actual = (float) $data['actual'];

                $logPayment = DB::table('phppos_register_log_payments')
                    ->where('register_log_id', $logId)
                    ->where('payment_type', $type)
                    ->first();

                if ($logPayment) {
                    DB::table('phppos_register_log_payments')
                        ->where('id', $logPayment->id)
                        ->update([
                            'close_amount' => $actual,
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('phppos_register_log_payments')->insert([
                        'register_log_id' => $logId,
                        'payment_type' => $type,
                        'open_amount' => 0,
                        'close_amount' => $actual,
                        'payment_sales_amount' => 0,
                        'total_payment_additions' => 0,
                        'total_payment_subtractions' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        session()->forget('register_log_id');

        return redirect()->route('modules.index')->with('status', 'Register closed successfully.');
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
