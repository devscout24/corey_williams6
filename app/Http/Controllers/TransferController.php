<?php

namespace App\Http\Controllers;

use App\Models\PhpposItem;
use App\Models\PhpposItemKit;
use App\Models\PhpposCategory;
use App\Models\PhpposLocation;
use App\Models\PhpposReceiving;
use App\Models\PhpposReceivingItem;
use App\Models\PhpposSupplier;
use App\Services\InventoryFlowService;
use App\Services\LocationContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function __construct(
        private readonly InventoryFlowService $inventoryFlowService,
        private readonly LocationContextService $locationContextService,
    ) {
    }

    private function getCart(): array
    {
        $defaultCart = [
            'items' => [],
            'from_location_id' => auth('employee')->user()?->location_id ?? 1,
            'to_location_id' => null,
            'supplier_id' => null,
            'comment' => '',
        ];
        $resolvedLocationId = $this->locationContextService->resolveLocationId($defaultCart['from_location_id']);
        $defaultCart['from_location_id'] = $resolvedLocationId;
        $cart = Session::get('transfer_cart');
        if (is_array($cart)) {
            $cart = array_merge($defaultCart, $cart);
            $cart['from_location_id'] = $resolvedLocationId;
            if ($cart['to_location_id']) {
                $cart['to_location_id'] = (int) $cart['to_location_id'];
            }
            if ($cart['to_location_id'] === $cart['from_location_id']) {
                $cart['to_location_id'] = null;
            }
            return $cart;
        }
        return $defaultCart;
    }

    public function outIndex(): View
    {
        $transfers = DB::table('phppos_transfers as t')
            ->leftJoin('phppos_locations as fl', 'fl.location_id', '=', 't.from_location_id')
            ->leftJoin('phppos_locations as tl', 'tl.location_id', '=', 't.to_location_id')
            ->select('t.*', 'fl.name as from_location_name', 'tl.name as to_location_name')
            ->where('t.transfer_type', 'out')
            ->orderByDesc('t.id')
            ->paginate(20);

        return view('transfers.out_index', compact('transfers'));
    }

    public function inIndex(): View
    {
        $transfers = DB::table('phppos_transfers as t')
            ->leftJoin('phppos_locations as fl', 'fl.location_id', '=', 't.from_location_id')
            ->leftJoin('phppos_locations as tl', 'tl.location_id', '=', 't.to_location_id')
            ->select('t.*', 'fl.name as from_location_name', 'tl.name as to_location_name')
            ->where('t.transfer_type', 'in')
            ->orderByDesc('t.id')
            ->paginate(20);

        return view('transfers.in_index', compact('transfers'));
    }

    public function create(): View
    {
        $cart = $this->getCart();
        $locations = PhpposLocation::where('deleted', 0)
            ->where('location_id', $cart['from_location_id'])
            ->get();
        $suppliers = PhpposSupplier::with('person')->orderBy('person_id')->get();
        $categories = PhpposCategory::where('deleted', 0)
            ->where('hide_from_grid', 0)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        $subtotal = 0;
        foreach ($cart['items'] as $item) {
            $subtotal += $item['cost_price'] * $item['quantity'];
        }
        $total = $subtotal;

        return view('transfers.create', compact('cart', 'locations', 'suppliers', 'categories', 'subtotal', 'total'));
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

        Session::put('transfer_cart', $cart);
        return redirect()->route('transfers.create');
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
            ];
        }
    }

    public function editItem(Request $request, int $index): RedirectResponse
    {
        $cart = $this->getCart();
        if (isset($cart['items'][$index])) {
            if ($request->has('quantity')) $cart['items'][$index]['quantity'] = (float) $request->quantity;
            Session::put('transfer_cart', $cart);
        }
        return redirect()->route('transfers.create');
    }

    public function removeItem(int $index): RedirectResponse
    {
        $cart = $this->getCart();
        if (isset($cart['items'][$index])) {
            unset($cart['items'][$index]);
            $cart['items'] = array_values($cart['items']);
            Session::put('transfer_cart', $cart);
        }
        return redirect()->route('transfers.create');
    }

    public function setSupplier(Request $request): RedirectResponse
    {
        $cart = $this->getCart();
        $cart['supplier_id'] = $request->supplier_id;
        Session::put('transfer_cart', $cart);
        return redirect()->route('transfers.create');
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
                $kit->item_id = 'KIT ' . $kit->id;
                $kit->name = '[KIT] ' . $kit->name;
                return $kit;
            });

        $results = $items->concat($kits)->sortBy('name')->values();

        return response()->json($results);
    }

    public function setLocation(Request $request): RedirectResponse
    {
        $cart = $this->getCart();
        if ($request->has('from_location_id')) {
            $cart['from_location_id'] = $this->locationContextService->resolveLocationId($cart['from_location_id'] ?? null);
        }
        if ($request->has('to_location_id')) {
            $cart['to_location_id'] = $request->to_location_id ? (int) $request->to_location_id : null;
        }
        if ($cart['to_location_id'] === $cart['from_location_id']) {
            $cart['to_location_id'] = null;
        }
        if ($request->has('comment')) {
            $cart['comment'] = $request->comment;
        }
        Session::put('transfer_cart', $cart);
        return redirect()->route('transfers.create');
    }

    public function edit(int $transferId): RedirectResponse
    {
        $transfer = \App\Models\PhpposTransfer::with('items')->where('id', $transferId)->where('transfer_type', 'out')->firstOrFail();
        if ($transfer->status === 'closed') {
            return redirect()->route('transfers.out')->with('error', 'Cannot edit a closed transfer.');
        }

        $cart = [
            'transfer_id' => $transfer->id,
            'from_location_id' => $transfer->from_location_id,
            'to_location_id' => $transfer->to_location_id,
            'comment' => $transfer->notes,
            'items' => [],
        ];

        foreach ($transfer->items as $tItem) {
            $item = \App\Models\PhpposItem::find($tItem->item_id);
            if ($item) {
                $cart['items'][] = [
                    'item_id' => $item->item_id,
                    'name' => $item->name,
                    'quantity' => $tItem->quantity,
                    'cost_price' => $item->cost_price,
                ];
            }
        }

        Session::put('transfer_cart', $cart);
        return redirect()->route('transfers.create');
    }

    public function save(Request $request): RedirectResponse
    {
        $cart = $this->getCart();
        if (empty($cart['items'])) {
            return redirect()->back()->with('error', 'Cart is empty.');
        }

        if (!$cart['to_location_id'] || $cart['from_location_id'] == $cart['to_location_id']) {
            return redirect()->back()->with('error', 'Please select a valid destination location different from the source.');
        }

        try {
            // Strip kit-level fallback rows — no real integer item_id
            $lines = collect($cart['items'])
                ->filter(fn($i) => !str_starts_with((string) ($i['item_id'] ?? ''), 'KIT_'))
                ->map(fn($i) => ['item_id' => $i['item_id'], 'quantity' => $i['quantity']])
                ->toArray();
            
            if (isset($cart['transfer_id'])) {
                $this->inventoryFlowService->updateTransferOut($cart['transfer_id'], $lines, $request->comment ?? $cart['comment']);
                $transferOutId = $cart['transfer_id'];
            } else {
                $transferOutId = $this->inventoryFlowService->createTransferOut(
                    $cart['from_location_id'],
                    $cart['to_location_id'],
                    $lines,
                    auth('employee')->id(),
                    $request->comment ?? $cart['comment']
                );
            }

            // Sync the event
            $this->inventoryFlowService->syncTransferEvent($transferOutId);

            Session::forget('transfer_cart');
            return redirect()->route('transfers.out')->with('status', 'Transfer saved successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function complete(Request $request): RedirectResponse
    {
        $cart = $this->getCart();
        if (empty($cart['items'])) {
            return redirect()->back()->with('error', 'Cart is empty.');
        }

        if (!$cart['to_location_id'] || $cart['from_location_id'] == $cart['to_location_id']) {
            return redirect()->back()->with('error', 'Please select a valid destination location different from the source.');
        }

        try {
            DB::transaction(function () use ($cart, $request) {
                // Strip kit-level fallback rows — no real integer item_id
                $realItems = collect($cart['items'])
                    ->filter(fn($i) => !str_starts_with((string) ($i['item_id'] ?? ''), 'KIT_'))
                    ->values()
                    ->all();

                $lines = collect($realItems)
                    ->map(fn($i) => ['item_id' => $i['item_id'], 'quantity' => $i['quantity']])
                    ->toArray();
                
                if (isset($cart['transfer_id'])) {
                    $this->inventoryFlowService->updateTransferOut($cart['transfer_id'], $lines, $request->comment ?? $cart['comment']);
                    $transferOutId = $cart['transfer_id'];
                } else {
                    $transferOutId = $this->inventoryFlowService->createTransferOut(
                        $cart['from_location_id'],
                        $cart['to_location_id'],
                        $lines,
                        auth('employee')->id(),
                        $request->comment ?? $cart['comment']
                    );
                }

                $this->inventoryFlowService->completeTransferOut($transferOutId, auth('employee')->id());

                // 2. Create a "Return" record in receivings for audit/reporting purposes (as requested)
                $subtotal = 0;
                $totalQty = 0;
                foreach ($cart['items'] as $item) {
                    $subtotal += $item['cost_price'] * $item['quantity'];
                    $totalQty += $item['quantity'];
                }

                $receiving = PhpposReceiving::create([
                    'receiving_time' => now(),
                    'supplier_id' => null, // Not bound to supplier
                    'employee_id' => auth('employee')->id(),
                    'comment' => 'Transfer Out #' . $transferOutId . ($request->comment ? ' - ' . $request->comment : ''),
                    'location_id' => $cart['from_location_id'],
                    'subtotal' => $subtotal,
                    'total' => $subtotal,
                    'total_quantity_purchased' => $totalQty,
                    'total_quantity_received' => 0, // Mode is return
                    'mode' => 'return',
                    'type' => 'return',
                    'source' => 'transfer',
                    'reference_id' => $transferOutId,
                ]);
                $receiving->syncDocumentIdentity();

                foreach ($cart['items'] as $index => $item) {
                    $isKit = str_starts_with((string) ($item['item_id'] ?? ''), 'KIT_');
                    
                    PhpposReceivingItem::create([
                        'receiving_id' => $receiving->receiving_id,
                        'item_id' => $isKit ? null : $item['item_id'],
                        'item_kit_id' => $isKit ? (int) str_replace('KIT_', '', $item['item_id']) : null,
                        'line' => $index,
                        'quantity_purchased' => $item['quantity'],
                        'quantity_received' => 0,
                        'item_cost_price' => $item['cost_price'],
                        'item_unit_price' => $item['cost_price'],
                        'discount_percent' => 0,
                        'subtotal' => $item['cost_price'] * $item['quantity'],
                        'total' => $item['cost_price'] * $item['quantity'],
                    ]);
                }

                $this->inventoryFlowService->syncTransferEvent($transferOutId);
            });

            Session::forget('transfer_cart');

            return redirect()->route('transfers.out')->with('status', 'Transfer posted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function cancel(): RedirectResponse
    {
        Session::forget('transfer_cart');
        return redirect()->route('transfers.out');
    }
}
