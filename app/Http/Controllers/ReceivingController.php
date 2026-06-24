<?php

namespace App\Http\Controllers;

use App\Models\ItemVariation;
use App\Models\Location;
use App\Models\Notification;
use App\Models\PhpposCategory;
use App\Models\PhpposItem;
use App\Models\PhpposItemKit;
use App\Models\PhpposLocation;
use App\Models\PhpposReceiving;
use App\Models\PhpposReceivingItem;
use App\Models\PhpposSupplier;
use App\Models\PhpposTransfer;
use App\Services\InventoryFlowService;
use App\Services\LocationContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class ReceivingController extends Controller
{
    public function __construct(private readonly LocationContextService $locationContextService) {}

    private function getCart(): array
    {
        $defaultCart = [
            'items' => [],
            'supplier_id' => null,
            'mode' => 'receive',
            'location_id' => auth('employee')->user()?->location_id ?? 1,
        ];
        $resolvedLocationId = $this->locationContextService->resolveLocationId($defaultCart['location_id']);
        $defaultCart['location_id'] = $resolvedLocationId;

        $cart = Session::get('receiving_cart');
        $cart = is_array($cart) ? array_merge($defaultCart, $cart) : $defaultCart;
        $cart['location_id'] = $resolvedLocationId;

        return $cart;
    }

    public function index(Request $request): View
    {
        $initialListMode = $request->query('tab') === 'return' ? 'Return' : 'Receive';

        return view('receivings.index', [
            'purchasesHistoryUrl' => route('purchases.history-data'),
            'purchasesCreateUrl' => route('purchases.create', ['mode' => 'receive']),
            'purchasesCreateReturnUrl' => route('purchases.create', ['mode' => 'return']),
            'initialListMode' => $initialListMode,
        ]);
    }

    public function purchasesHistoryData(Request $request): JsonResponse
    {
        $type = $request->query('type', 'receive') === 'return' ? 'return' : 'receive';

        $q = trim((string) $request->query('q', ''));
        $criteria = $request->query('criteria', 'id');
        if (! in_array($criteria, ['id', 'supplier', 'date', 'status'], true)) {
            $criteria = 'id';
        }

        $query = $this->purchasesHistoryBaseQuery($type);
        if ($q !== '') {
            $this->applyPurchasesListSearch($query, $q, $criteria);
        }

        $paginator = $query->orderByDesc('receiving_time')->paginate(15);

        return response()->json([
            'success' => true,
            'items' => collect($paginator->items())->map(fn (PhpposReceiving $r): array => $this->mapReceivingHistoryRow($r))->values(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
            'count' => count($paginator->items()),
        ]);
    }

    private function purchasesHistoryBaseQuery(string $type): Builder
    {
        $query = PhpposReceiving::query()
            ->with(['supplier', 'items'])
            ->where('deleted', 0);

        if ($type === 'return') {
            $query->where(function (Builder $q): void {
                $q->where('type', 'return')->orWhere(function (Builder $q2): void {
                    $q2->whereNull('type')->where('mode', 'return');
                });
            });
        } else {
            $query->where(function (Builder $q): void {
                $q->whereIn('type', ['receive', 'transfer'])
                    ->orWhere(function (Builder $q2): void {
                        $q2->whereNull('type')->where(function (Builder $q3): void {
                            $q3->whereNull('mode')->orWhere('mode', '<>', 'return');
                        });
                    });
            });
        }

        return $query;
    }

    /**
     * @return array{label: string, tone: string}
     */
    private function receivingHistoryUiStatus(PhpposReceiving $r): array
    {
        if ($r->mode === 'transfer') {
            return ['label' => 'Transfer', 'tone' => 'neutral'];
        }

        if ($r->mode === 'return') {
            if ($r->suspended) {
                return ['label' => 'Suspended', 'tone' => 'open'];
            }

            return ['label' => 'Returned', 'tone' => 'closed'];
        }

        if ($r->is_po) {
            return ['label' => 'PO', 'tone' => 'open'];
        }

        if ($r->suspended) {
            return ['label' => 'Suspended', 'tone' => 'open'];
        }

        return ['label' => 'Closed', 'tone' => 'closed'];
    }

    /**
     * @return array{receiving_id: int, internal_code: string, type: string, date: string, supplier: string, items: int, total: string, status_label: string, status_tone: string, source: string|null, reference_id: string|null}
     */
    private function mapReceivingHistoryRow(PhpposReceiving $r): array
    {
        $st = $this->receivingHistoryUiStatus($r);
        $mode = $r->mode ?? 'receive';
        $internalCode = $r->internal_code ?? PhpposReceiving::formatInternalCode($mode, (int) $r->receiving_id);
        $docType = $r->type ?? PhpposReceiving::documentTypeFromMode($mode);

        return [
            'receiving_id' => (int) $r->receiving_id,
            'internal_code' => $internalCode,
            'type' => $docType,
            'date' => $r->receiving_time?->format('M d, Y h:i A') ?? '',
            'supplier' => $r->supplier?->company_name ?? '—',
            'items' => $r->items->count(),
            'total' => '$'.number_format((float) $r->total, 2),
            'suspended' => (bool) $r->suspended,
            'status_label' => $st['label'],
            'status_tone' => $st['tone'],
            'source' => $r->source,
            'reference_id' => $r->reference_id,
        ];
    }

    private function applyPurchasesListSearch(Builder $query, string $q, string $criteria): void
    {
        match ($criteria) {
            'supplier' => $query->whereHas('supplier', static function (Builder $sq) use ($q): void {
                $sq->where('company_name', 'like', '%'.$q.'%');
            }),
            'date' => $query->where('receiving_time', 'like', '%'.$q.'%'),
            'status' => $this->applyPurchasesStatusSearch($query, $q),
            default => $this->applyReceivingIdOrCodeSearch($query, $q),
        };
    }

    private function applyReceivingIdOrCodeSearch(Builder $query, string $q): void
    {
        $t = trim($q);
        if ($t === '') {
            return;
        }

        $query->where(function (Builder $sub) use ($t): void {
            if (ctype_digit($t)) {
                $sub->where('receiving_id', (int) $t)
                    ->orWhere('internal_code', 'like', '%'.$t.'%');
            } else {
                $sub->where('receiving_id', 'like', '%'.$t.'%')
                    ->orWhere('internal_code', 'like', '%'.$t.'%');
            }
        });
    }

    private function applyPurchasesStatusSearch(Builder $query, string $q): void
    {
        $ql = strtolower($q);
        if (str_contains($ql, 'suspend')) {
            $query->where('suspended', '>', 0);

            return;
        }
        if (str_contains($ql, 'po') || str_contains($ql, 'order')) {
            $query->where('is_po', true);

            return;
        }
        $query->where('suspended', 0)->where('is_po', false);
    }

    public function create(Request $request): View
    {
        $mode = $request->query('mode');
        if (in_array($mode, ['receive', 'return', 'transfer'], true)) {
            $cart = $this->getCart();
            $cart['mode'] = $mode;
            Session::put('receiving_cart', $cart);
        }

        $cart = $this->getCart();

        $suppliers = PhpposSupplier::with('person')->get();
        $locations = PhpposLocation::where('deleted', 0)
            ->where('location_id', $cart['location_id'])
            ->get();
        $categories = PhpposCategory::where('deleted', 0)
            ->where('hide_from_grid', 0)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        $subtotal = 0;
        foreach ($cart['items'] as $item) {
            $subtotal += $item['cost_price'] * $item['quantity'] * (1 - $item['discount'] / 100);
        }
        $total = $subtotal;

        return view('receivings.register', compact('cart', 'suppliers', 'locations', 'categories', 'subtotal', 'total'));
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
                ->get(['item_id', 'name', 'cost_price'])
                ->map(function ($item) {
                    return [
                        'type' => 'item',
                        'id' => $item->item_id,
                        'name' => $item->name,
                        'price' => $item->cost_price,
                    ];
                });

            $kitsQuery = PhpposItemKit::where('deleted', 0)
                ->where('category_id', $categoryId);

            if ($supplierId) {
                $kitsQuery->where('supplier_id', $supplierId);
            }

            $kits = $kitsQuery->orderBy('name')
                ->get(['id', 'name', 'cost_price'])
                ->map(function ($kit) {
                    return [
                        'type' => 'kit',
                        'id' => $kit->id,
                        'name' => '[KIT] '.$kit->name,
                        'price' => $kit->cost_price,
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
                ->get(['item_id', 'name', 'cost_price'])
                ->map(function ($item) {
                    return [
                        'type' => 'item',
                        'id' => $item->item_id,
                        'name' => $item->name,
                        'price' => $item->cost_price,
                    ];
                });

            $kitsQuery = PhpposItemKit::where('deleted', 0)
                ->where('category_id', $categoryId);

            if ($supplierId) {
                $kitsQuery->where('supplier_id', $supplierId);
            }

            $kits = $kitsQuery->orderBy('name')
                ->get(['id', 'name', 'cost_price'])
                ->map(function ($kit) {
                    return [
                        'type' => 'kit',
                        'id' => $kit->id,
                        'name' => '[KIT] '.$kit->name,
                        'price' => $kit->cost_price,
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
            ->get(['item_id', 'name', 'cost_price'])
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
            ->get(['id', 'name', 'cost_price'])
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
        $request->validate(['item_id' => 'required|string']);

        $itemIdStr = $request->item_id;
        $cart = $this->getCart();

        if (str_starts_with($itemIdStr, 'VAR ')) {
            $variationId = (int) str_replace('VAR ', '', $itemIdStr);
            $variation = ItemVariation::findOrFail($variationId);
            $parentItem = PhpposItem::findOrFail($variation->item_id);
            $this->addSingleItemToCart($parentItem, 1, $cart, $variation);
        } elseif (str_starts_with($itemIdStr, 'KIT ')) {
            $kitId = (int) str_replace('KIT ', '', $itemIdStr);
            $kit = PhpposItemKit::findOrFail($kitId);
            $this->addKitToCart($kit, 1, $cart);
        } else {
            $item = PhpposItem::findOrFail($itemIdStr);
            $this->addSingleItemToCart($item, 1, $cart);
        }

        Session::put('receiving_cart', $cart);

        return redirect()->route('purchases.create');
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
        if ($existingKey !== null) {
            $cart['items'][$existingKey]['quantity'] += $quantity;
        } else {
            $cart['items'][] = [
                'type' => 'kit',
                'item_kit_id' => $kitId,
                'name' => $kit->name,
                'quantity' => $quantity,
                'cost_price' => (float) ($kit->cost_price ?? 0),
                'discount' => 0,
            ];
        }
    }

    private function addSingleItemToCart($item, $quantity, &$cart, $variation = null)
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

        if ($existingKey !== null) {
            $cart['items'][$existingKey]['quantity'] += $quantity;
        } else {
            $entry = [
                'item_id' => $item->item_id,
                'name' => $variation ? $variation->name.' ('.$item->name.')' : $item->name,
                'quantity' => $quantity,
                'cost_price' => $variation?->cost_price ?? $item->cost_price,
                'discount' => 0,
            ];
            if ($variation) {
                $entry['variation_id'] = $variation->id;
            }
            $cart['items'][] = $entry;
        }
    }

    /**
     * @return array<int, array{item_id: int, quantity: float}>
     */
    private function explodeKitComponents(int $kitId, float $kitQty): array
    {
        $items = [];

        $kitItemRows = DB::table('phppos_item_kit_items')
            ->where('item_kit_id', $kitId)
            ->get();

        foreach ($kitItemRows as $row) {
            $itemId = (int) $row->item_id;
            $qty = (float) $row->quantity * $kitQty;
            $items[] = ['item_id' => $itemId, 'quantity' => $qty];
        }

        $nestedRows = DB::table('phppos_item_kit_item_kits')
            ->where('item_kit_id', $kitId)
            ->get();

        foreach ($nestedRows as $row) {
            $nestedKitQty = (float) $row->quantity * $kitQty;
            $child = $this->explodeKitComponents((int) $row->item_kit_item_kit, $nestedKitQty);
            $items = array_merge($items, $child);
        }

        return $items;
    }

    public function editItem(Request $request, int $index): RedirectResponse
    {
        $cart = $this->getCart();
        if (isset($cart['items'][$index])) {
            if ($request->has('quantity')) {
                $cart['items'][$index]['quantity'] = (float) $request->quantity;
            }
            if ($request->has('cost_price')) {
                $cart['items'][$index]['cost_price'] = (float) $request->cost_price;
            }
            if ($request->has('discount')) {
                $cart['items'][$index]['discount'] = (float) $request->discount;
            }
            Session::put('receiving_cart', $cart);
        }

        return redirect()->route('purchases.create');
    }

    public function removeItem(int $index): RedirectResponse
    {
        $cart = $this->getCart();
        if (isset($cart['items'][$index])) {
            unset($cart['items'][$index]);
            $cart['items'] = array_values($cart['items']);
            Session::put('receiving_cart', $cart);
        }

        return redirect()->route('purchases.create');
    }

    public function setSupplier(Request $request): RedirectResponse
    {
        $cart = $this->getCart();
        $cart['supplier_id'] = $request->supplier_id ?: null;
        Session::put('receiving_cart', $cart);

        return redirect()->route('purchases.create');
    }

    public function setMode(Request $request): RedirectResponse
    {
        $cart = $this->getCart();
        $cart['mode'] = $request->mode;
        Session::put('receiving_cart', $cart);

        return redirect()->route('purchases.create');
    }

    public function suspend(Request $request): RedirectResponse
    {
        $cart = $this->getCart();
        if (empty($cart['items'])) {
            return redirect()->back()->with('error', 'Cart is empty.');
        }

        $subtotal = 0;
        $totalQty = 0;
        foreach ($cart['items'] as $item) {
            $itemTotal = $item['cost_price'] * $item['quantity'] * (1 - $item['discount'] / 100);
            $subtotal += $itemTotal;
            $totalQty += $item['quantity'];
        }

        $locationId = $this->locationContextService->resolveLocationId($cart['location_id'] ?? null);

        $receiving = PhpposReceiving::create([
            'receiving_time' => now(),
            'supplier_id' => $cart['supplier_id'],
            'employee_id' => auth('employee')->id(),
            'comment' => $request->comment,
            'location_id' => $locationId,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'total_quantity_purchased' => $totalQty,
            'total_quantity_received' => 0,
            'mode' => $cart['mode'],
            'type' => PhpposReceiving::documentTypeFromMode($cart['mode']),
            'source' => 'manual',
            'suspended' => 1,
        ]);
        $receiving->syncDocumentIdentity();

        foreach ($cart['items'] as $item) {
            if (($item['type'] ?? 'item') === 'kit') {
                PhpposReceivingItem::create([
                    'receiving_id' => $receiving->receiving_id,
                    'item_kit_id' => $item['item_kit_id'],
                    'line' => 0,
                    'description' => $item['name'],
                    'quantity_purchased' => $item['quantity'],
                    'quantity_received' => 0,
                    'item_cost_price' => $item['cost_price'],
                    'item_unit_price' => $item['cost_price'],
                    'discount_percent' => $item['discount'],
                    'subtotal' => $item['cost_price'] * $item['quantity'] * (1 - $item['discount'] / 100),
                    'total' => $item['cost_price'] * $item['quantity'] * (1 - $item['discount'] / 100),
                ]);
            } else {
                PhpposReceivingItem::create([
                    'receiving_id' => $receiving->receiving_id,
                    'item_id' => $item['item_id'],
                    'item_variation_id' => $item['variation_id'] ?? null,
                    'line' => 0,
                    'quantity_purchased' => $item['quantity'],
                    'quantity_received' => 0,
                    'item_cost_price' => $item['cost_price'],
                    'item_unit_price' => $item['cost_price'],
                    'discount_percent' => $item['discount'],
                    'subtotal' => $item['cost_price'] * $item['quantity'] * (1 - $item['discount'] / 100),
                    'total' => $item['cost_price'] * $item['quantity'] * (1 - $item['discount'] / 100),
                ]);
            }
        }

        Session::forget('receiving_cart');

        return redirect()->route('purchases.index')
            ->with('status', 'Purchase suspended successfully. You can resume it later.');
    }

    public function resume(int $receivingId): RedirectResponse
    {
        $receiving = PhpposReceiving::with('items')->findOrFail($receivingId);

        if (! $receiving->suspended) {
            return redirect()->route('purchases.show', $receivingId)
                ->with('error', 'This purchase is not suspended.');
        }

        $cart = $this->getCart();
        $cart['items'] = [];
        $cart['supplier_id'] = $receiving->supplier_id;
        $cart['mode'] = $receiving->mode ?? 'receive';
        $cart['location_id'] = $receiving->location_id;
        $cart['suspended_receiving_id'] = $receivingId;

        foreach ($receiving->items as $line) {
            if ($line->item_kit_id) {
                $kit = PhpposItemKit::find($line->item_kit_id);
                $cart['items'][] = [
                    'type' => 'kit',
                    'item_kit_id' => $line->item_kit_id,
                    'name' => $kit?->name ?? $line->description ?? 'Kit',
                    'quantity' => (float) $line->quantity_purchased,
                    'cost_price' => (float) $line->item_cost_price,
                    'discount' => (float) $line->discount_percent,
                ];
            } elseif ($line->item_id) {
                $entry = [
                    'item_id' => (int) $line->item_id,
                    'name' => $line->item?->name ?? $line->description ?? 'Item',
                    'quantity' => (float) $line->quantity_purchased,
                    'cost_price' => (float) $line->item_cost_price,
                    'discount' => (float) $line->discount_percent,
                ];
                if ($line->item_variation_id) {
                    $entry['variation_id'] = (int) $line->item_variation_id;
                }
                $cart['items'][] = $entry;
            }
        }

        Session::put('receiving_cart', $cart);

        return redirect()->route('purchases.create')
            ->with('status', 'Suspended purchase loaded. Complete it when ready.');
    }

    public function complete(Request $request): RedirectResponse
    {
        $cart = $this->getCart();
        if (empty($cart['items'])) {
            return redirect()->back()->with('error', 'Cart is empty.');
        }

        return DB::transaction(function () use ($cart, $request) {
            $subtotal = 0;
            $totalQty = 0;

            $regularItems = [];
            $kitEntries = [];

            foreach ($cart['items'] as $item) {
                if (($item['type'] ?? 'item') === 'kit') {
                    $kitEntries[] = $item;
                } else {
                    $regularItems[] = $item;
                }
                $itemTotal = $item['cost_price'] * $item['quantity'] * (1 - $item['discount'] / 100);
                $subtotal += $itemTotal;
                $totalQty += $item['quantity'];
            }

            if (empty($regularItems) && empty($kitEntries)) {
                return redirect()->back()->with('error', 'Cart is empty.');
            }

            $locationId = $this->locationContextService->resolveLocationId($cart['location_id'] ?? null);

            if ($cart['suspended_receiving_id'] ?? null) {
                $receiving = PhpposReceiving::findOrFail($cart['suspended_receiving_id']);
                $receiving->update([
                    'closed_at' => now(),
                    'suspended' => 0,
                    'supplier_id' => $cart['supplier_id'],
                    'employee_id' => auth('employee')->id(),
                    'comment' => $request->comment,
                    'location_id' => $locationId,
                    'subtotal' => $subtotal,
                    'total' => $subtotal,
                    'total_quantity_purchased' => $totalQty,
                    'total_quantity_received' => in_array($cart['mode'], ['receive', 'transfer']) ? $totalQty : 0,
                    'mode' => $cart['mode'],
                    'type' => PhpposReceiving::documentTypeFromMode($cart['mode']),
                ]);
                $receiving->items()->delete();
                DB::table('phppos_receivings_items_taxes')
                    ->where('receiving_id', $receiving->receiving_id)
                    ->delete();
            } else {
                $receiving = PhpposReceiving::create([
                    'receiving_time' => now(),
                    'closed_at' => now(),
                    'supplier_id' => $cart['supplier_id'],
                    'employee_id' => auth('employee')->id(),
                    'comment' => $request->comment,
                    'location_id' => $locationId,
                    'subtotal' => $subtotal,
                    'total' => $subtotal,
                    'total_quantity_purchased' => $totalQty,
                    'total_quantity_received' => in_array($cart['mode'], ['receive', 'transfer']) ? $totalQty : 0,
                    'mode' => $cart['mode'],
                    'type' => PhpposReceiving::documentTypeFromMode($cart['mode']),
                    'source' => 'manual',
                ]);
                $receiving->syncDocumentIdentity();
            }

            // Collect all item IDs for tax class map (regular items + exploded kit components)
            $allItemIds = collect($regularItems)->pluck('item_id')->map(static fn ($id): int => (int) $id)->unique()->values()->all();

            // Resolve kit entries into component item IDs for tax lookup
            $kitComponentItemIds = [];
            foreach ($kitEntries as $kitEntry) {
                $components = $this->explodeKitComponents((int) $kitEntry['item_kit_id'], (float) $kitEntry['quantity']);
                foreach ($components as $comp) {
                    $kitComponentItemIds[] = $comp['item_id'];
                }
            }
            $allItemIds = array_values(array_unique([...$allItemIds, ...$kitComponentItemIds]));

            $itemTaxClassMap = $allItemIds === []
                ? []
                : DB::table('phppos_items')
                    ->whereIn('item_id', $allItemIds)
                    ->pluck('tax_class_id', 'item_id')
                    ->toArray();

            $defaultTaxClassRaw = DB::table('phppos_app_config')
                ->where('key', 'tax_class_id')
                ->value('value');
            $defaultTaxClassId = is_numeric($defaultTaxClassRaw) ? (int) $defaultTaxClassRaw : 0;

            $taxClassIds = array_values(array_unique(array_filter(array_merge(
                array_map(static fn ($id): int => is_numeric($id) ? (int) $id : 0, $itemTaxClassMap),
                [$defaultTaxClassId]
            ), static fn (int $id): bool => $id > 0)));

            $taxClassTaxes = $taxClassIds === []
                ? collect()
                : DB::table('phppos_tax_classes_taxes')
                    ->whereIn('tax_class_id', $taxClassIds)
                    ->orderBy('order')
                    ->orderBy('id')
                    ->get()
                    ->groupBy('tax_class_id');

            $totalVat = 0.0;
            $lineIndex = 0;

            // ---- Process regular items ----
            foreach ($regularItems as $item) {
                $lineSubtotal = $item['cost_price'] * $item['quantity'] * (1 - $item['discount'] / 100);

                $lineTaxClassId = $itemTaxClassMap[$item['item_id']] ?? 0;
                if (! is_numeric($lineTaxClassId) || (int) $lineTaxClassId <= 0) {
                    $lineTaxClassId = $defaultTaxClassId;
                }
                $lineTaxClassId = (int) $lineTaxClassId;

                $lineVat = 0.0;
                if ($lineTaxClassId > 0 && $taxClassTaxes->has($lineTaxClassId)) {
                    $rates = $taxClassTaxes->get($lineTaxClassId);

                    $baseCumulative = $lineSubtotal;
                    $totalTax = 0.0;
                    foreach ($rates as $rate) {
                        $rateDecimal = (float) $rate->percent / 100;
                        $lineTaxAmount = $baseCumulative * $rateDecimal;
                        $totalTax += $lineTaxAmount;
                        if ((bool) $rate->cumulative) {
                            $baseCumulative += $lineTaxAmount;
                        }
                    }
                    $lineTotal = $lineSubtotal + $totalTax;

                    if ($lineSubtotal > 0) {
                        $effectiveTaxRate = ($lineTotal - $lineSubtotal) / $lineSubtotal;
                        if ($effectiveTaxRate > 0) {
                            $lineVat = $lineSubtotal * $effectiveTaxRate;
                        }
                    }

                    $taxInsert = [];
                    foreach ($rates as $rate) {
                        $taxInsert[] = [
                            'receiving_id' => $receiving->receiving_id,
                            'item_id' => $item['item_id'],
                            'line' => $lineIndex,
                            'name' => $rate->name,
                            'percent' => $rate->percent,
                            'cumulative' => (bool) $rate->cumulative,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    DB::table('phppos_receivings_items_taxes')->insert($taxInsert);
                }

                $totalVat += $lineVat;

                PhpposReceivingItem::create([
                    'receiving_id' => $receiving->receiving_id,
                    'item_id' => $item['item_id'],
                    'item_variation_id' => $item['variation_id'] ?? null,
                    'line' => $lineIndex,
                    'quantity_purchased' => $item['quantity'],
                    'quantity_received' => in_array($cart['mode'], ['receive', 'transfer']) ? $item['quantity'] : 0,
                    'item_cost_price' => $item['cost_price'],
                    'item_unit_price' => $item['cost_price'],
                    'discount_percent' => $item['discount'],
                    'subtotal' => $lineSubtotal,
                    'total' => $lineSubtotal,
                    'vat' => round($lineVat, 10),
                ]);

                $multiplier = $cart['mode'] == 'return' ? -1 : 1;
                $inventoryToMove = $item['quantity'] * $multiplier;

                Log::debug('[RECV complete] mode='.$cart['mode'].' multiplier='.$multiplier.' item_id='.($item['item_id'] ?? 'null').' cart_qty='.$item['quantity'].' inventoryToMove='.$inventoryToMove.' location_id='.$locationId);

                $stock = DB::table('phppos_location_items')
                    ->where('item_id', $item['item_id'])
                    ->where('location_id', $locationId)
                    ->lockForUpdate()
                    ->first();

                $stockQty = $stock ? (float) $stock->quantity : 0.0;
                Log::debug('[RECV complete] stock row: '.json_encode($stock));
                Log::debug('[RECV complete] stockQty='.$stockQty.' computed_new_qty='.($stockQty + $inventoryToMove));
                if (! $stock) {
                    DB::table('phppos_location_items')->insert([
                        'item_id' => $item['item_id'],
                        'location_id' => $locationId,
                        'quantity' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('phppos_location_items')
                    ->where('item_id', $item['item_id'])
                    ->where('location_id', $locationId)
                    ->update([
                        'quantity' => $stockQty + $inventoryToMove,
                        'updated_at' => now(),
                    ]);

                $isReceive = in_array($cart['mode'], ['receive', 'transfer']);
                DB::table('phppos_inventory_movements')->insert([
                    'movement_type' => $isReceive ? 'receiving' : 'return',
                    'item_id' => $item['item_id'],
                    'from_location_id' => $cart['mode'] == 'return' ? $locationId : null,
                    'to_location_id' => $isReceive ? $locationId : null,
                    'quantity' => abs($inventoryToMove),
                    'reference_id' => $receiving->receiving_id,
                    'reference_type' => 'receiving',
                    'created_by_person_id' => auth('employee')->id(),
                    'notes' => ($isReceive ? 'RECV ' : 'RET ').$receiving->receiving_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $lineIndex++;
            }

            // ---- Process kit entries ----
            foreach ($kitEntries as $kitEntry) {
                $kitId = (int) $kitEntry['item_kit_id'];
                $kitQty = (float) $kitEntry['quantity'];
                $kitLineTotal = $kitEntry['cost_price'] * $kitQty * (1 - ($kitEntry['discount'] ?? 0) / 100);

                $multiplier = $cart['mode'] == 'return' ? -1 : 1;

                // Increment/decrement the kit's own stock (default_quantity)
                DB::table('phppos_item_kits')
                    ->where('id', $kitId)
                    ->increment('default_quantity', $kitQty * $multiplier);

                // Explode kit into component items
                $components = $this->explodeKitComponents($kitId, $kitQty);

                foreach ($components as $comp) {
                    $compItemId = $comp['item_id'];
                    $compQty = $comp['quantity'];

                    // Update inventory for each component
                    $inventoryToMove = $compQty * $multiplier;

                    Log::debug('[RECV complete KIT] mode='.$cart['mode'].' kit_id='.$kitId.' comp_item_id='.$compItemId.' comp_qty='.$compQty.' multiplier='.$multiplier.' inventoryToMove='.$inventoryToMove.' location_id='.$locationId);

                    $compStock = DB::table('phppos_location_items')
                        ->where('item_id', $compItemId)
                        ->where('location_id', $locationId)
                        ->lockForUpdate()
                        ->first();

                    $compStockQty = $compStock ? (float) $compStock->quantity : 0.0;
                    Log::debug('[RECV complete KIT] comp stock row: '.json_encode($compStock));
                    Log::debug('[RECV complete KIT] compStockQty='.$compStockQty.' computed_new_qty='.($compStockQty + $inventoryToMove));
                    if (! $compStock) {
                        DB::table('phppos_location_items')->insert([
                            'item_id' => $compItemId,
                            'location_id' => $locationId,
                            'quantity' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('phppos_location_items')
                        ->where('item_id', $compItemId)
                        ->where('location_id', $locationId)
                        ->update([
                            'quantity' => $compStockQty + $inventoryToMove,
                            'updated_at' => now(),
                        ]);

                    $isReceiveKit = in_array($cart['mode'], ['receive', 'transfer']);
                    DB::table('phppos_inventory_movements')->insert([
                        'movement_type' => $isReceiveKit ? 'receiving' : 'return',
                        'item_id' => $compItemId,
                        'from_location_id' => $cart['mode'] == 'return' ? $locationId : null,
                        'to_location_id' => $isReceiveKit ? $locationId : null,
                        'quantity' => abs($inventoryToMove),
                        'reference_id' => $receiving->receiving_id,
                        'reference_type' => 'kit_component_receiving',
                        'created_by_person_id' => auth('employee')->id(),
                        'notes' => ($isReceiveKit ? 'RECV ' : 'RET ').$receiving->receiving_id.' (kit #'.$kitId.' component)',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Write component item record
                    PhpposReceivingItem::create([
                        'receiving_id' => $receiving->receiving_id,
                        'item_id' => $compItemId,
                        'line' => $lineIndex,
                        'description' => 'Component of kit #'.$kitId,
                        'quantity_purchased' => $compQty,
                        'quantity_received' => in_array($cart['mode'], ['receive', 'transfer']) ? $compQty : 0,
                        'item_cost_price' => 0,
                        'item_unit_price' => 0,
                        'discount_percent' => 0,
                        'subtotal' => 0,
                        'total' => 0,
                        'vat' => 0,
                    ]);

                    $lineIndex++;
                }

                // Write kit header record (for receipt/report display)
                PhpposReceivingItem::create([
                    'receiving_id' => $receiving->receiving_id,
                    'item_id' => null,
                    'item_kit_id' => $kitId,
                    'line' => $lineIndex,
                    'description' => $kitEntry['name'],
                    'quantity_purchased' => $kitQty,
                    'quantity_received' => in_array($cart['mode'], ['receive', 'transfer']) ? $kitQty : 0,
                    'item_cost_price' => $kitEntry['cost_price'],
                    'item_unit_price' => $kitEntry['cost_price'],
                    'discount_percent' => $kitEntry['discount'] ?? 0,
                    'subtotal' => $kitLineTotal,
                    'total' => $kitLineTotal,
                    'vat' => 0,
                ]);

                $lineIndex++;
            }

            $receiving->vat = round($totalVat, 10);
            $receiving->saveQuietly();

            Session::forget('receiving_cart');
            $msg = $cart['mode'] === 'return'
                ? 'Return completed successfully.'
                : 'Purchase completed successfully.';

            $to = route('purchases.index', $cart['mode'] === 'return' ? ['tab' => 'return'] : []);

            return redirect()->to($to)->with('status', $msg);
        });
    }

    public function cancel(): RedirectResponse
    {
        Session::forget('receiving_cart');

        return redirect()->route('purchases.index');
    }

    public function show($receivingId): View
    {
        $receiving = PhpposReceiving::with(['items.item', 'items.kit', 'supplier', 'location', 'employee'])->findOrFail($receivingId);

        return view('receivings.show', compact('receiving'));
    }

    public function print($receivingId): View
    {
        $receiving = PhpposReceiving::with(['items.item', 'items.kit', 'supplier', 'location', 'employee'])->findOrFail($receivingId);

        return view('receivings.print', compact('receiving'));
    }

    public function closeTransferReceiving(int $receivingId, InventoryFlowService $inventoryFlowService): RedirectResponse
    {
        $receiving = PhpposReceiving::with('items')->findOrFail($receivingId);

        if ($receiving->closed_at) {
            return redirect()->route('purchases.show', $receivingId)
                ->with('error', 'Receiving is already closed.');
        }

        if ($receiving->source !== 'transfer') {
            return redirect()->route('purchases.show', $receivingId)
                ->with('error', 'Only transfer receivings can be closed via this action.');
        }

        $employeeId = auth('employee')->id();
        if (! $employeeId) {
            $employeeId = DB::table('phppos_employees')->value('person_id');
        }

        DB::transaction(function () use ($receiving, $inventoryFlowService, $employeeId): void {
            foreach ($receiving->items as $item) {
                if (! $item->item_id) {
                    // Kit header row — increment kit default_quantity, no inventory movement
                    if ($item->item_kit_id) {
                        DB::table('phppos_item_kits')
                            ->where('id', $item->item_kit_id)
                            ->increment('default_quantity', (float) $item->quantity_purchased);
                    }
                    $item->update(['quantity_received' => $item->quantity_purchased]);
                    continue;
                }
                $currentQty = (float) DB::table('phppos_location_items')
                    ->where('location_id', $receiving->location_id)
                    ->where('item_id', $item->item_id)
                    ->value('quantity') ?? 0;
                Log::debug('[closeTransferReceiving] item_id='.$item->item_id.' location_id='.$receiving->location_id.' current_qty='.$currentQty.' adding='.(float) $item->quantity_purchased);

                $inventoryFlowService->receive(
                    $receiving->location_id,
                    $item->item_id,
                    (float) $item->quantity_purchased,
                    $employeeId,
                    'Transfer in #'.$receiving->reference_id
                );

                $item->update(['quantity_received' => $item->quantity_purchased]);
            }

            $receiving->update([
                'closed_at' => now(),
                'total_quantity_received' => $receiving->total_quantity_purchased,
            ]);

            $transferIn = PhpposTransfer::where('transfer_type', 'in')
                ->where('external_transfer_id', $receiving->reference_id)
                ->where('to_location_id', $receiving->location_id)
                ->first();

            if ($transferIn) {
                $transferIn->update(['status' => 'closed', 'closed_at' => now()]);
            }

            try {
                Notification::create([
                    'type' => 'transfer_completed',
                    'reference_type' => 'receiving',
                    'reference_id' => $receiving->receiving_id,
                    'title' => 'Transfer receiving #'.$receiving->internal_code.' completed',
                    'body' => 'Items added to inventory.',
                    'action_url' => '/purchases/'.$receiving->receiving_id,
                ]);
            } catch (\Throwable) {
            }
        });

        $this->notifySenderTransferCompleted($receiving);

        return redirect()->route('purchases.show', $receivingId)
            ->with('status', 'Transfer receiving #'.$receiving->internal_code.' completed successfully. Items added to inventory.');
    }

    private function notifySenderTransferCompleted(PhpposReceiving $receiving): void
    {
        $transferIn = PhpposTransfer::where('transfer_type', 'in')
            ->where('external_transfer_id', $receiving->reference_id)
            ->first();

        $senderIp = $transferIn?->external_source;
        if (! $senderIp) {
            return;
        }

        $senderLocation = Location::where('ip', $senderIp)->first();
        if (! $senderLocation || ! $senderLocation->ip || ! $senderLocation->port) {
            return;
        }

        try {
            Http::timeout(5)
                ->asJson()
                ->withHeaders(['X-Sync-Token' => (string) config('sync.shared_token')])
                ->post('http://'.$senderLocation->ip.':'.$senderLocation->port.'/api/lan/transfer-completed', [
                    'transfer_out_id' => $receiving->reference_id,
                    'receiving_code' => $receiving->internal_code,
                ]);
        } catch (\Throwable) {
        }
    }
}
