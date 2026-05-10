<?php

namespace App\Http\Controllers;

use App\Models\PhpposItem;
use App\Models\PhpposItemKit;
use App\Models\PhpposCategory;
use App\Models\PhpposLocation;
use App\Models\PhpposReceiving;
use App\Models\PhpposReceivingItem;
use App\Services\InventoryFlowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function __construct(private readonly InventoryFlowService $inventoryFlowService) {}

    private function getCart(): array
    {
        $defaultCart = [
            'items' => [],
            'from_location_id' => auth('employee')->user()?->location_id ?? 1,
            'to_location_id' => null,
            'comment' => '',
        ];
        $cart = Session::get('transfer_cart');
        if (is_array($cart)) {
            $cart = array_merge($defaultCart, $cart);
            $cart['from_location_id'] = (int) $cart['from_location_id'];
            if ($cart['to_location_id']) {
                $cart['to_location_id'] = (int) $cart['to_location_id'];
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
        $locations = PhpposLocation::where('deleted', 0)->get();
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

        return view('transfers.create', compact('cart', 'locations', 'categories', 'subtotal', 'total'));
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

        Session::put('transfer_cart', $cart);
        return redirect()->route('transfers.create');
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

    public function setLocation(Request $request): RedirectResponse
    {
        $cart = $this->getCart();
        if ($request->has('from_location_id')) {
            $cart['from_location_id'] = (int) $request->from_location_id;
        }
        if ($request->has('to_location_id')) {
            $cart['to_location_id'] = $request->to_location_id ? (int) $request->to_location_id : null;
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
            $lines = collect($cart['items'])->map(fn($i) => ['item_id' => $i['item_id'], 'quantity' => $i['quantity']])->toArray();
            
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
                $lines = collect($cart['items'])->map(fn($i) => ['item_id' => $i['item_id'], 'quantity' => $i['quantity']])->toArray();
                
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
                ]);
                $receiving->syncDocumentIdentity();

                foreach ($cart['items'] as $index => $item) {
                    PhpposReceivingItem::create([
                        'receiving_id' => $receiving->receiving_id,
                        'item_id' => $item['item_id'],
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
