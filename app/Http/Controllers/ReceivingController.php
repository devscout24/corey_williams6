<?php

namespace App\Http\Controllers;

use App\Models\PhpposItem;
use App\Models\PhpposItemKit;
use App\Models\PhpposCategory;
use App\Models\PhpposReceiving;
use App\Models\PhpposReceivingItem;
use App\Models\PhpposSupplier;
use App\Models\PhpposLocation;
use App\Models\PhpposSupplierStoreAccount;
use App\Services\InventoryFlowService;
use App\Services\LocationContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;


class ReceivingController extends Controller
{
    public function __construct(private readonly LocationContextService $locationContextService)
    {
    }

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

        if ($categoryId && !$isRootCategory) {
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
                        'name' => '[KIT] ' . $kit->name,
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
                        'name' => '[KIT] ' . $kit->name,
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
        
        $itemsQuery = PhpposItem::where('deleted', 0)
            ->where(function($query) use ($term) {
                $query->where('name', 'LIKE', "%$term%")
                      ->orWhere('item_id', $term)
                      ->orWhere('product_id', $term);
            });
        
        if ($supplierId) {
            $itemsQuery->where('supplier_id', $supplierId);
        }

        $items = $itemsQuery->limit(10)
            ->get(['item_id', 'name', 'cost_price']);

        $kitsQuery = PhpposItemKit::where('deleted', 0)
            ->where(function($query) use ($term) {
                $query->where('name', 'LIKE', "%$term%")
                      ->orWhere('item_kit_number', $term)
                      ->orWhere('product_id', $term);
            });
        
        if ($supplierId) {
            $kitsQuery->where('supplier_id', $supplierId);
        }

        $kits = $kitsQuery->limit(10)
            ->get(['id', 'name', 'cost_price'])
            ->map(function ($kit) {
                $kit->item_id = 'KIT ' . $kit->id; // Using item_id for frontend compatibility
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

            // If kit has no component items, add the kit itself as a single line
            $countAfter = count($cart['items']);
            $totalQtyAfter = array_sum(array_column($cart['items'], 'quantity'));
            if ($countAfter === $countBefore && $totalQtyAfter === $totalQtyBefore) {
                // Fallback: add kit as a named line item using its own cost/price
                $this->addKitAsLineItem($kit, 1, $cart);
            }
        } else {
            $item = PhpposItem::findOrFail($itemIdStr);
            $this->addSingleItemToCart($item, 1, $cart);
        }

        Session::put('receiving_cart', $cart);
        return redirect()->route('purchases.create');
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
                'item_id'    => $kitLineId,
                'name'       => '[KIT] ' . $kit->name,
                'quantity'   => $quantity,
                'cost_price' => (float) ($kit->cost_price ?? 0),
                'discount'   => 0,
            ];
        }
    }

    private function addSingleItemToCart($item, $quantity, &$cart)
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
                'cost_price' => $item->cost_price,
                'discount' => 0,
            ];
        }
    }

    public function editItem(Request $request, int $index): RedirectResponse
    {
        $cart = $this->getCart();
        if (isset($cart['items'][$index])) {
            if ($request->has('quantity')) $cart['items'][$index]['quantity'] = (float) $request->quantity;
            if ($request->has('cost_price')) $cart['items'][$index]['cost_price'] = (float) $request->cost_price;
            if ($request->has('discount')) $cart['items'][$index]['discount'] = (float) $request->discount;
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

    public function complete(Request $request): RedirectResponse
    {
        $cart = $this->getCart();
        if (empty($cart['items'])) {
            return redirect()->back()->with('error', 'Cart is empty.');
        }

        return DB::transaction(function () use ($cart, $request) {
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
                'closed_at' => now(),
                'supplier_id' => $cart['supplier_id'],
                'employee_id' => auth('employee')->id(),
                'comment' => $request->comment,
                'location_id' => $locationId,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'total_quantity_purchased' => $totalQty,
                'total_quantity_received' => $cart['mode'] == 'receive' ? $totalQty : 0,
                'mode' => $cart['mode'],
                'type' => PhpposReceiving::documentTypeFromMode($cart['mode']),
                'source' => 'manual',
            ]);
            $receiving->syncDocumentIdentity();

            foreach ($cart['items'] as $index => $item) {
                $isKitFallback = str_starts_with((string) ($item['item_id'] ?? ''), 'KIT_');

                if ($isKitFallback) {
                    // Store as a kit-level line item
                    $kitId = (int) str_replace('KIT_', '', $item['item_id']);
                    PhpposReceivingItem::create([
                        'receiving_id'   => $receiving->receiving_id,
                        'item_id'        => null,
                        'item_kit_id'    => $kitId,
                        'line'           => $index,
                        'description'    => $item['name'],
                        'quantity_purchased' => $item['quantity'],
                        'quantity_received'  => $cart['mode'] == 'receive' ? $item['quantity'] : 0,
                        'item_cost_price'    => $item['cost_price'],
                        'item_unit_price'    => $item['cost_price'],
                        'discount_percent'   => $item['discount'] ?? 0,
                        'subtotal' => $item['cost_price'] * $item['quantity'] * (1 - ($item['discount'] ?? 0) / 100),
                        'total'    => $item['cost_price'] * $item['quantity'] * (1 - ($item['discount'] ?? 0) / 100),
                    ]);
                    // Kits with no component items: no inventory movement (nothing to deduct)
                    continue;
                }

                PhpposReceivingItem::create([
                    'receiving_id'   => $receiving->receiving_id,
                    'item_id'        => $item['item_id'],
                    'line'           => $index,
                    'quantity_purchased' => $item['quantity'],
                    'quantity_received'  => $cart['mode'] == 'receive' ? $item['quantity'] : 0,
                    'item_cost_price'    => $item['cost_price'],
                    'item_unit_price'    => $item['cost_price'],
                    'discount_percent'   => $item['discount'],
                    'subtotal' => $item['cost_price'] * $item['quantity'] * (1 - $item['discount'] / 100),
                    'total'    => $item['cost_price'] * $item['quantity'] * (1 - $item['discount'] / 100),
                ]);

                // Update inventory
                $multiplier = $cart['mode'] == 'return' ? -1 : 1;
                $inventoryToMove = $item['quantity'] * $multiplier;

                DB::table('phppos_location_items')
                    ->updateOrInsert(
                        ['item_id' => $item['item_id'], 'location_id' => $locationId],
                        ['quantity' => DB::raw("quantity + $inventoryToMove")]
                    );

                DB::table('phppos_inventory_movements')->insert([
                    'movement_type'      => $cart['mode'] == 'receive' ? 'receiving' : 'return',
                    'item_id'            => $item['item_id'],
                    'from_location_id'   => $cart['mode'] == 'return' ? $locationId : null,
                    'to_location_id'     => $cart['mode'] == 'receive' ? $locationId : null,
                    'quantity'           => abs($inventoryToMove),
                    'reference_id'       => $receiving->receiving_id,
                    'reference_type'     => 'receiving',
                    'created_by_person_id' => auth('employee')->id(),
                    'notes'       => ($cart['mode'] == 'receive' ? 'RECV ' : 'RET ') . $receiving->receiving_id,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

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
}

