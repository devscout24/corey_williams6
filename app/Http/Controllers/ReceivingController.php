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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class ReceivingController extends Controller
{
    private function getCart(): array
    {
        $defaultCart = [
            'items' => [],
            'supplier_id' => null,
            'mode' => 'receive',
            'location_id' => auth('employee')->user()?->location_id ?? 1,
        ];
        $cart = Session::get('receiving_cart');
        return is_array($cart) ? array_merge($defaultCart, $cart) : $defaultCart;
    }

    public function index(): View
    {
        $cart = $this->getCart();

        $suppliers = PhpposSupplier::with('person')->get();
        $locations = PhpposLocation::where('deleted', 0)->get();
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

        if ($categoryId && !$isRootCategory) {
            $items = PhpposItem::where('deleted', 0)
                ->where('category_id', $categoryId)
                ->orderBy('name')
                ->get(['item_id', 'name', 'cost_price'])
                ->map(function ($item) {
                    return [
                        'type' => 'item',
                        'id' => $item->item_id,
                        'name' => $item->name,
                        'price' => $item->cost_price,
                    ];
                });

            $kits = PhpposItemKit::where('deleted', 0)
                ->where('category_id', $categoryId)
                ->orderBy('name')
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
            $items = PhpposItem::where('deleted', 0)
                ->where('category_id', $categoryId)
                ->orderBy('name')
                ->get(['item_id', 'name', 'cost_price'])
                ->map(function ($item) {
                    return [
                        'type' => 'item',
                        'id' => $item->item_id,
                        'name' => $item->name,
                        'price' => $item->cost_price,
                    ];
                });

            $kits = PhpposItemKit::where('deleted', 0)
                ->where('category_id', $categoryId)
                ->orderBy('name')
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
        
        $items = PhpposItem::where('deleted', 0)
            ->where(function($query) use ($term) {
                $query->where('name', 'LIKE', "%$term%")
                      ->orWhere('item_id', $term)
                      ->orWhere('product_id', $term);
            })
            ->limit(10)
            ->get(['item_id', 'name', 'cost_price']);

        $kits = PhpposItemKit::where('deleted', 0)
            ->where(function($query) use ($term) {
                $query->where('name', 'LIKE', "%$term%")
                      ->orWhere('item_kit_number', $term)
                      ->orWhere('product_id', $term);
            })
            ->limit(10)
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

        if (str_starts_with($itemIdStr, 'KIT ')) {
            $kitId = str_replace('KIT ', '', $itemIdStr);
            $kit = PhpposItemKit::with(['items', 'nestedKits'])->findOrFail($kitId);
            $this->addKitItemsToCart($kit, 1, $cart);
        } else {
            $item = PhpposItem::findOrFail($itemIdStr);
            $this->addSingleItemToCart($item, 1, $cart);
        }

        Session::put('receiving_cart', $cart);
        return redirect()->route('receivings.index');
    }

    private function addKitItemsToCart($kit, $quantity, &$cart)
    {
        foreach ($kit->items as $kitItem) {
            $item = PhpposItem::find($kitItem->item_id);
            if ($item) {
                $this->addSingleItemToCart($item, $kitItem->quantity * $quantity, $cart);
            }
        }
        
        foreach ($kit->nestedKits as $nestedKit) {
            $nKit = PhpposItemKit::with(['items', 'nestedKits'])->find($nestedKit->item_kit_item_kit);
            if ($nKit) {
                $this->addKitItemsToCart($nKit, $nestedKit->quantity * $quantity, $cart);
            }
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
        return redirect()->route('receivings.index');
    }

    public function removeItem(int $index): RedirectResponse
    {
        $cart = $this->getCart();
        if (isset($cart['items'][$index])) {
            unset($cart['items'][$index]);
            $cart['items'] = array_values($cart['items']);
            Session::put('receiving_cart', $cart);
        }
        return redirect()->route('receivings.index');
    }

    public function setSupplier(Request $request): RedirectResponse
    {
        $cart = $this->getCart();
        $cart['supplier_id'] = $request->supplier_id ?: null;
        Session::put('receiving_cart', $cart);
        return redirect()->route('receivings.index');
    }

    public function setMode(Request $request): RedirectResponse
    {
        $cart = $this->getCart();
        $cart['mode'] = $request->mode;
        Session::put('receiving_cart', $cart);
        return redirect()->route('receivings.index');
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

            $receiving = PhpposReceiving::create([
                'receiving_time' => now(),
                'supplier_id' => $cart['supplier_id'],
                'employee_id' => auth('employee')->id(),
                'comment' => $request->comment,
                'location_id' => $cart['location_id'],
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'total_quantity_purchased' => $totalQty,
                'total_quantity_received' => $cart['mode'] == 'receive' ? $totalQty : 0,
                'mode' => $cart['mode'],
            ]);

            foreach ($cart['items'] as $index => $item) {
                PhpposReceivingItem::create([
                    'receiving_id' => $receiving->receiving_id,
                    'item_id' => $item['item_id'],
                    'line' => $index,
                    'quantity_purchased' => $item['quantity'],
                    'quantity_received' => $cart['mode'] == 'receive' ? $item['quantity'] : 0,
                    'item_cost_price' => $item['cost_price'],
                    'item_unit_price' => $item['cost_price'],
                    'discount_percent' => $item['discount'],
                    'subtotal' => $item['cost_price'] * $item['quantity'] * (1 - $item['discount'] / 100),
                    'total' => $item['cost_price'] * $item['quantity'] * (1 - $item['discount'] / 100),
                ]);

                // Update inventory
                $multiplier = $cart['mode'] == 'return' ? -1 : 1;
                $inventoryToMove = $item['quantity'] * $multiplier;

                DB::table('phppos_location_items')
                    ->updateOrInsert(
                        ['item_id' => $item['item_id'], 'location_id' => $cart['location_id']],
                        ['quantity' => DB::raw("quantity + $inventoryToMove")]
                    );

                DB::table('phppos_inventory_movements')->insert([
                    'movement_type' => $cart['mode'] == 'receive' ? 'receiving' : 'return',
                    'item_id' => $item['item_id'],
                    'from_location_id' => $cart['mode'] == 'return' ? $cart['location_id'] : null,
                    'to_location_id' => $cart['mode'] == 'receive' ? $cart['location_id'] : null,
                    'quantity' => abs($inventoryToMove),
                    'reference_id' => $receiving->receiving_id,
                    'reference_type' => 'receiving',
                    'created_by_person_id' => auth('employee')->id(),
                    'notes' => ($cart['mode'] == 'receive' ? 'RECV ' : 'RET ') . $receiving->receiving_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Session::forget('receiving_cart');
            return redirect()->route('receivings.index')->with('status', 'Receiving completed successfully.');
        });
    }

    public function syncTransfer(Request $request, InventoryFlowService $inventoryFlowService): RedirectResponse
    {
        $data = $request->validate([
            'sender_base_url' => 'required|string|max:255',
            'transfer_out_id' => 'required|string|max:100',
        ]);

        $token = config('sync.shared_token');
        if (! $token || $token === 'INSTALL_SET_TOKEN') {
            return redirect()->back()->with('error', 'Sync token is not configured on this device.');
        }

        $baseUrl = rtrim($data['sender_base_url'], '/');
        if (! preg_match('/^https?:\/\//i', $baseUrl)) {
            $baseUrl = 'http://'.$baseUrl;
        }

        try {
            $response = Http::withHeaders(['X-Sync-Token' => $token])
                ->timeout(15)
                ->get($baseUrl.'/api/sync/transfer-out/'.$data['transfer_out_id']);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Unable to reach sender device.');
        }

        if (! $response->ok()) {
            $message = $response->json('message') ?? 'Unable to fetch transfer from sender.';
            return redirect()->back()->with('error', $message);
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return redirect()->back()->with('error', 'Invalid transfer payload.');
        }

        $fromLocation = PhpposLocation::where('ulid', $payload['from_location_ulid'] ?? null)->first();
        $toLocation = PhpposLocation::where('ulid', $payload['to_location_ulid'] ?? null)->first();

        if (! $fromLocation || ! $toLocation) {
            return redirect()->back()->with('error', 'Location ULID not found on this device.');
        }

        $lines = [];
        foreach (($payload['lines'] ?? []) as $index => $line) {
            if (empty($line['item_id']) && empty($line['item_number'])) {
                return redirect()->back()->with('error', 'Item identifier missing for line '.($index + 1).'.');
            }

            $item = null;
            if (! empty($line['item_id'])) {
                $item = PhpposItem::find($line['item_id']);
            }
            if (! $item && ! empty($line['item_number'])) {
                $item = PhpposItem::where('item_number', $line['item_number'])->first();
            }

            if (! $item) {
                return redirect()->back()->with('error', 'Item not found for line '.($index + 1).'.');
            }

            $lines[] = [
                'item_id' => $item->item_id,
                'quantity' => (float) ($line['quantity'] ?? 0),
            ];
        }

        $result = $inventoryFlowService->importTransferIn(
            $fromLocation->location_id,
            $toLocation->location_id,
            $lines,
            (string) ($payload['source_device_id'] ?? 'unknown'),
            (string) ($payload['transfer_out_id'] ?? $data['transfer_out_id']),
            $payload['notes'] ?? null,
            $payload['created_at'] ?? null,
            auth('employee')->id()
        );

        $message = $result['already_imported']
            ? 'Transfer already imported. Transfer In #'.$result['transfer_in_id']
            : 'Transfer synced. Transfer In #'.$result['transfer_in_id'];

        if (! empty($result['receiving_id'])) {
            $message .= ' / Receiving #'.$result['receiving_id'];
        }

        return redirect()->back()->with('status', $message);
    }

    public function cancel(): RedirectResponse
    {
        Session::forget('receiving_cart');
        return redirect()->route('receivings.index');
    }
}

