<?php

namespace App\Http\Controllers;

use App\Models\PhpposItem;
use App\Models\PhpposItemKit;
use App\Models\PhpposSupplier;
use App\Models\PhpposReceiving;
use App\Models\PhpposReceivingItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'open');
        $suppliers = PhpposSupplier::query()->where('deleted', 0)->orderBy('company_name')->get();

        $query = PhpposReceiving::query()
            ->with(['supplier', 'items.item'])
            ->where('is_po', 1)
            ->where('deleted', 0)
            ->orderBy('receiving_time', 'desc');

        if ($status === 'open') {
            $query->where('suspended', 0);
        } elseif ($status === 'closed') {
            $query->where('suspended', 1);
        }

        $orders = $query->get();

        return view('orders.index', [
            'suppliers' => $suppliers,
            'orders' => $orders,
            'currentStatus' => $status,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer'],
            'items' => ['required', 'array'],
            'items.*.type' => ['required', 'string', 'in:item,kit'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        $employee = auth('employee')->user();

        $receiving = PhpposReceiving::create([
            'receiving_time' => now(),
            'supplier_id' => $data['supplier_id'],
            'employee_id' => $employee->person_id,
            'location_id' => $employee->location_id ?? 1,
            'is_po' => 1,
            'deleted' => 0,
            'suspended' => 0,
            'mode' => 'receive',
            'type' => 'receive',
            'subtotal' => 0,
            'total' => 0,
        ]);
        $receiving->syncDocumentIdentity();
        $total = 0;

        foreach ($data['items'] as $line => $item) {
            if ($item['type'] === 'item') {
                $dbItem = PhpposItem::find($item['item_id']);
                if ($dbItem) {
                    $lineTotal = $item['quantity'] * $dbItem->cost_price;
                    $total += $lineTotal;
                    PhpposReceivingItem::create([
                        'receiving_id' => $receiving->receiving_id,
                        'item_id' => $item['item_id'],
                        'description' => $dbItem->description ?? '',
                        'serialnumber' => '',
                        'line' => $line + 1,
                        'quantity_purchased' => $item['quantity'],
                        'quantity_received' => $item['quantity'], // Set received to purchased initially
                        'item_cost_price' => $dbItem->cost_price,
                        'item_unit_price' => $dbItem->unit_price,
                        'discount_percent' => 0,
                        'subtotal' => $lineTotal,
                        'total' => $lineTotal,
                    ]);
                }
            } else {
                $kit = PhpposItemKit::with('items')->find($item['item_id']);
                if ($kit) {
                    foreach ($kit->items as $kitItem) {
                        $dbItem = PhpposItem::find($kitItem->item_id);
                        if ($dbItem) {
                            $lineTotal = ($item['quantity'] * $kitItem->quantity) * $dbItem->cost_price;
                            $total += $lineTotal;
                            PhpposReceivingItem::create([
                                'receiving_id' => $receiving->receiving_id,
                                'item_id' => $kitItem->item_id,
                                'description' => $dbItem->description ?? '',
                                'serialnumber' => '',
                                'line' => $line + 1,
                                'quantity_purchased' => $item['quantity'] * $kitItem->quantity,
                                'quantity_received' => $item['quantity'] * $kitItem->quantity,
                                'item_cost_price' => $dbItem->cost_price,
                                'item_unit_price' => $dbItem->unit_price,
                                'discount_percent' => 0,
                                'subtotal' => $lineTotal,
                                'total' => $lineTotal,
                            ]);
                        }
                    }
                }
            }
        }

        $receiving->update([
            'subtotal' => $total,
            'total' => $total,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order saved successfully',
            'order_id' => $receiving->receiving_id,
        ]);
    }

    public function pullList(Request $request): JsonResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer'],
            'only_below' => ['nullable', 'boolean'],
        ]);

        $supplierId = (int) $data['supplier_id'];
        $onlyBelow = !empty($data['only_below']);
        $locationId = auth('employee')->user()?->location_id ?? 1;

        $itemsQuery = PhpposItem::query()
            ->where('phppos_items.deleted', 0)
            ->where('phppos_items.supplier_id', $supplierId)
            ->leftJoin('phppos_location_items as li', function ($join) use ($locationId) {
                $join->on('li.item_id', '=', 'phppos_items.item_id')
                    ->where('li.location_id', '=', $locationId);
            })
            ->select(
                'phppos_items.item_id as id',
                'phppos_items.name',
                'phppos_items.item_number',
                'phppos_items.product_id',
                'phppos_items.reorder_level',
                DB::raw('COALESCE(li.quantity, phppos_items.default_quantity, 0) as current_quantity')
            );

        if ($onlyBelow) {
            $itemsQuery
                ->whereNotNull('phppos_items.reorder_level')
                ->whereRaw('COALESCE(li.quantity, phppos_items.default_quantity, 0) <= phppos_items.reorder_level');
        }

        $items = $itemsQuery->orderBy('phppos_items.name')->get()->map(function ($item) {
            return [
                'type' => 'item',
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->item_number ?: ($item->product_id ?: ''),
                'current_quantity' => (float) $item->current_quantity,
                'reorder_level' => $item->reorder_level !== null ? (float) $item->reorder_level : null,
            ];
        });

        $kitsQuery = PhpposItemKit::query()
            ->where('phppos_item_kits.deleted', 0)
            ->where('phppos_item_kits.supplier_id', $supplierId)
            ->select(
                'phppos_item_kits.id as id',
                'phppos_item_kits.name',
                'phppos_item_kits.item_kit_number',
                'phppos_item_kits.product_id',
                'phppos_item_kits.reorder_level',
                DB::raw('COALESCE(phppos_item_kits.default_quantity, 0) as current_quantity')
            );

        if ($onlyBelow) {
            $kitsQuery
                ->whereNotNull('phppos_item_kits.reorder_level')
                ->whereRaw('COALESCE(phppos_item_kits.default_quantity, 0) <= phppos_item_kits.reorder_level');
        }

        $kits = $kitsQuery->orderBy('phppos_item_kits.name')->get()->map(function ($kit) {
            return [
                'type' => 'kit',
                'id' => $kit->id,
                'name' => $kit->name,
                'sku' => $kit->item_kit_number ?: ($kit->product_id ?: ''),
                'current_quantity' => (float) $kit->current_quantity,
                'reorder_level' => $kit->reorder_level !== null ? (float) $kit->reorder_level : null,
            ];
        });

        return response()->json([
            'items' => $items->values(),
            'kits' => $kits->values(),
        ]);
    }

    public function searchItems(Request $request): JsonResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $supplierId = (int) $data['supplier_id'];
        $term = trim((string) ($data['q'] ?? ''));
        $locationId = auth('employee')->user()?->location_id ?? 1;
        

        $itemsQuery = PhpposItem::query()
            ->where('phppos_items.deleted', 0)
            // ->where('phppos_items.supplier_id', $supplierId)
            ->leftJoin('phppos_location_items as li', function ($join) use ($locationId) {
                $join->on('li.item_id', '=', 'phppos_items.item_id')
                    ->where('li.location_id', '=', $locationId);
            })
            ->select(
                'phppos_items.item_id as id',
                'phppos_items.name',
                'phppos_items.item_number',
                'phppos_items.product_id',
                DB::raw('COALESCE(li.quantity, phppos_items.default_quantity, 0) as current_quantity')
            )
            ->orderBy('phppos_items.name')
            ->limit(25);

        if ($term !== '') {
            $itemsQuery->where(function ($query) use ($term) {
                $query->where('phppos_items.name', 'like', "%{$term}%")
                    ->orWhere('phppos_items.item_number', 'like', "%{$term}%")
                    ->orWhere('phppos_items.product_id', 'like', "%{$term}%");
            });
        }

        $kitsQuery = PhpposItemKit::query()
            ->where('phppos_item_kits.deleted', 0)
            // ->where('phppos_item_kits.supplier_id', $supplierId)
            ->select(
                'phppos_item_kits.id as id',
                'phppos_item_kits.name',
                'phppos_item_kits.item_kit_number',
                'phppos_item_kits.product_id',
                DB::raw('COALESCE(phppos_item_kits.default_quantity, 0) as current_quantity')
            )
            ->orderBy('phppos_item_kits.name')
            ->limit(25);

        if ($term !== '') {
            $kitsQuery->where(function ($query) use ($term) {
                $query->where('phppos_item_kits.name', 'like', "%{$term}%")
                    ->orWhere('phppos_item_kits.item_kit_number', 'like', "%{$term}%")
                    ->orWhere('phppos_item_kits.product_id', 'like', "%{$term}%");
            });
        }

        $items = $itemsQuery->get()->map(function ($item) {
            return [
                'type' => 'item',
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->item_number ?: ($item->product_id ?: ''),
                'current_quantity' => (float) $item->current_quantity,
            ];
        });

        $kits = $kitsQuery->get()->map(function ($kit) {
            return [
                'type' => 'kit',
                'id' => $kit->id,
                'name' => $kit->name,
                'sku' => $kit->item_kit_number ?: ($kit->product_id ?: ''),
                'current_quantity' => (float) $kit->current_quantity,
            ];
        });

        return response()->json([
            'items' => $items->values(),
            'kits' => $kits->values(),
        ]);
    }

    public function show($receivingId): View
    {
        $receiving = PhpposReceiving::with(['items.item', 'supplier', 'location', 'employee'])->findOrFail($receivingId);
        return view('receivings.show', compact('receiving'));
    }

    public function edit($receivingId)
    {
        return redirect()->route('purchases.index', ['receiving_id' => $receivingId]);
    }

    public function update(Request $request, $receivingId): JsonResponse
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.item_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($receivingId, $data) {
            try {
                $order = PhpposReceiving::with('items')->findOrFail($receivingId);
                
                foreach ($data['items'] as $inputItem) {
                    $item = $order->items->where('item_id', $inputItem['item_id'])->first();
                    if ($item) {
                        $item->quantity_purchased = $inputItem['quantity'];
                        $item->quantity_received = $inputItem['quantity'];
                        $lineTotal = $item->item_cost_price * $inputItem['quantity'];
                        $item->subtotal = $lineTotal;
                        $item->total = $lineTotal;
                        $item->save();
                    }
                }
                
                $total = $order->items()->sum('total');
                $order->update(['subtotal' => $total, 'total' => $total]);

                return response()->json(['success' => true, 'message' => 'Order items updated successfully']);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        });
    }

    public function close($receivingId): JsonResponse
    {
        return DB::transaction(function () use ($receivingId) {
            try {
                $order = PhpposReceiving::with('items')->findOrFail($receivingId);
                
                $receive = $order->replicate();
                $receive->is_po = 0;
                $receive->suspended = 0;
                $receive->source = 'order';
                $receive->reference_id = $order->internal_code ?? $order->receiving_id;
                $receive->receiving_time = now();
                $receive->save();
                $receive->syncDocumentIdentity();

                foreach ($order->items as $item) {
                    $new_item = $item->replicate();
                    $new_item->receiving_id = $receive->receiving_id;
                    $new_item->save();
                    
                    DB::table('phppos_location_items')
                        ->updateOrInsert(
                            ['item_id' => $new_item->item_id, 'location_id' => $receive->location_id],
                            ['quantity' => DB::raw("quantity + " . $new_item->quantity_received)]
                        );
                        
                    DB::table('phppos_inventory_movements')->insert([
                        'movement_type' => 'receiving',
                        'item_id' => $new_item->item_id,
                        'to_location_id' => $receive->location_id,
                        'quantity' => $new_item->quantity_received,
                        'reference_id' => $receive->receiving_id,
                        'reference_type' => 'receiving',
                        'created_by_person_id' => auth('employee')->id(),
                        'notes' => 'Generated from Order ' . ($order->internal_code ?? $order->receiving_id),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $order->update(['suspended' => 1]);

                return response()->json(['success' => true, 'message' => 'Order closed and receiving generated successfully']);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        });
    }

    public function print($receivingId): View
    {
        $receiving = PhpposReceiving::with(['items.item', 'supplier', 'location', 'employee'])->findOrFail($receivingId);
        return view('receivings.print', compact('receiving'));
    }

    public function destroy($receivingId): JsonResponse
    {
        try {
            $receiving = PhpposReceiving::findOrFail($receivingId);
            $receiving->update(['deleted' => 1]);
            return response()->json(['success' => true, 'message' => 'Order deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
