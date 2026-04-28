<?php

namespace App\Http\Controllers;

use App\Models\PhpposItem;
use App\Models\PhpposItemKit;
use App\Models\PhpposReceiving;
use App\Models\PhpposReceivingItem;
use App\Models\PhpposSupplier;
use App\Models\PhpposLocation;
use App\Models\PhpposSupplierStoreAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        
        $subtotal = 0;
        foreach ($cart['items'] as $item) {
            $subtotal += $item['cost_price'] * $item['quantity'] * (1 - $item['discount'] / 100);
        }
        $total = $subtotal;

        return view('receivings.register', compact('cart', 'suppliers', 'locations', 'subtotal', 'total'));
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
                    'item_id' => $item['item_id'],
                    'user_id' => auth('employee')->id(),
                    'trans_date' => now(),
                    'trans_comment' => ($cart['mode'] == 'receive' ? 'RECV ' : 'RET ') . $receiving->receiving_id,
                    'trans_inventory' => $inventoryToMove,
                    'location_id' => $cart['location_id'],
                ]);
            }

            Session::forget('receiving_cart');
            return redirect()->route('receivings.index')->with('status', 'Receiving completed successfully.');
        });
    }

    public function cancel(): RedirectResponse
    {
        Session::forget('receiving_cart');
        return redirect()->route('receivings.index');
    }
}

