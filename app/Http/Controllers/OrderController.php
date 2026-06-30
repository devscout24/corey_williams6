<?php

namespace App\Http\Controllers;

use App\Models\PhpposItem;
use App\Models\PhpposItemKit;
use App\Models\PhpposOrder;
use App\Models\PhpposOrderItem;
use App\Models\PhpposSupplier;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'open');
        $q = trim((string) $request->query('q', ''));
        $suppliers = PhpposSupplier::query()->where('deleted', 0)->orderBy('company_name')->get();

        $query = PhpposOrder::query()
            ->with(['supplier', 'items.item', 'items.kit'])
            ->where('deleted', 0)
            ->orderBy('order_time', 'desc');

        if ($status === 'open') {
            $query->where('suspended', 0);
        } elseif ($status === 'closed') {
            $query->where('suspended', 1);
        }

        if ($q !== '') {
            $query->where(function ($sq) use ($q) {
                $sq->where('internal_code', 'like', "%{$q}%")
                    ->orWhere('order_id', 'like', "%{$q}%")
                    ->orWhereHas('supplier', function ($sq2) use ($q) {
                        $sq2->where('company_name', 'like', "%{$q}%");
                    });
            });
        }

        $orders = $query->paginate(15)->withQueryString();

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

        $order = PhpposOrder::create([
            'order_time' => now(),
            'supplier_id' => $data['supplier_id'],
            'employee_id' => $employee->person_id,
            'location_id' => $employee->location_id ?? 1,
            'deleted' => 0,
            'suspended' => 0,
            'subtotal' => 0,
            'total' => 0,
        ]);

        $order->internal_code = 'PO-'.str_pad((string) $order->order_id, 8, '0', STR_PAD_LEFT);
        $order->saveQuietly();

        $total = 0;

        foreach ($data['items'] as $line => $item) {
            if ($item['type'] === 'item') {
                $dbItem = PhpposItem::find($item['item_id']);
                if ($dbItem) {
                    $lineTotal = $item['quantity'] * $dbItem->cost_price;
                    $total += $lineTotal;
                    PhpposOrderItem::create([
                        'order_id' => $order->order_id,
                        'item_id' => $item['item_id'],
                        'description' => $dbItem->description ?? '',
                        'line' => $line + 1,
                        'quantity_purchased' => $item['quantity'],
                        'quantity_received' => $item['quantity'],
                        'item_cost_price' => $dbItem->cost_price,
                        'item_unit_price' => $dbItem->unit_price,
                        'subtotal' => $lineTotal,
                        'total' => $lineTotal,
                    ]);
                }
            } else {
                $kit = PhpposItemKit::with(['items.item', 'nestedKits'])->find($item['item_id']);
                if ($kit) {
                    $itemsAdded = 0;
                    foreach ($kit->items as $kitItem) {
                        $dbItem = $kitItem->item ?? PhpposItem::find($kitItem->item_id);
                        if ($dbItem) {
                            $lineTotal = ($item['quantity'] * $kitItem->quantity) * $dbItem->cost_price;
                            $total += $lineTotal;
                            PhpposOrderItem::create([
                                'order_id' => $order->order_id,
                                'item_id' => $kitItem->item_id,
                                'item_kit_id' => null,
                                'description' => $dbItem->description ?? '',
                                'line' => $line + 1,
                                'quantity_purchased' => $item['quantity'] * $kitItem->quantity,
                                'quantity_received' => $item['quantity'] * $kitItem->quantity,
                                'item_cost_price' => $dbItem->cost_price,
                                'item_unit_price' => $dbItem->unit_price,
                                'subtotal' => $lineTotal,
                                'total' => $lineTotal,
                            ]);
                            $itemsAdded++;
                        }
                    }

                    if ($itemsAdded === 0) {
                        $lineTotal = $item['quantity'] * ($kit->cost_price ?? 0);
                        $total += $lineTotal;
                        PhpposOrderItem::create([
                            'order_id' => $order->order_id,
                            'item_id' => null,
                            'item_kit_id' => $kit->id,
                            'description' => $kit->description ?? '',
                            'line' => $line + 1,
                            'quantity_purchased' => $item['quantity'],
                            'quantity_received' => $item['quantity'],
                            'item_cost_price' => $kit->cost_price ?? 0,
                            'item_unit_price' => $kit->unit_price ?? 0,
                            'subtotal' => $lineTotal,
                            'total' => $lineTotal,
                        ]);
                    }
                }
            }
        }

        $order->update([
            'subtotal' => $total,
            'total' => $total,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order saved successfully',
            'order_id' => $order->order_id,
        ]);
    }

    public function pullList(Request $request): JsonResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer'],
            'only_below' => ['nullable', 'boolean'],
        ]);

        $supplierId = (int) $data['supplier_id'];
        $onlyBelow = ! empty($data['only_below']);
        $locationId = auth('employee')->user()?->location_id ?? 1;

        $itemsQuery = PhpposItem::query()
            ->where('phppos_items.deleted', 0)
            ->where(function ($query) use ($supplierId) {
                $query->where('phppos_items.supplier_id', $supplierId)
                    ->orWhereHas('secondarySuppliers', function ($sq) use ($supplierId) {
                        $sq->where('phppos_suppliers.person_id', $supplierId);
                    });
            })
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

    public function show(PhpposOrder $order): View
    {
        $order->load(['items.item', 'items.kit', 'supplier', 'location', 'employee']);
        $qtyOnHand = $this->getQtyOnHand($order);

        return view('orders.show', compact('order', 'qtyOnHand'));
    }

    public function edit(PhpposOrder $order)
    {
        return redirect()->route('orders.index');
    }

    public function update(Request $request, PhpposOrder $order): JsonResponse
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.item_id' => 'nullable|integer',
            'items.*.item_kit_id' => 'nullable|integer',
            'items.*.quantity' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($order, $data) {
            try {
                $order->load('items');

                foreach ($data['items'] as $inputItem) {
                    $item = null;
                    if (! empty($inputItem['item_id'])) {
                        $item = $order->items->where('item_id', $inputItem['item_id'])->first();
                    } elseif (! empty($inputItem['item_kit_id'])) {
                        $item = $order->items->where('item_kit_id', $inputItem['item_kit_id'])->first();
                    }

                    if ($item) {
                        $item->quantity_purchased = $inputItem['quantity'];
                        $item->quantity_received = $inputItem['quantity'];
                        $cost = $item->item_id ? $item->item_cost_price : ($item->kit?->cost_price ?? 0);
                        $lineTotal = $cost * $inputItem['quantity'];
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

    public function close(PhpposOrder $order): JsonResponse
    {
        return DB::transaction(function () use ($order) {
            try {
                $order->update([
                    'suspended' => 1,
                    'closed_at' => now(),
                ]);

                return response()->json(['success' => true, 'message' => 'Order closed successfully']);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        });
    }

    public function print(PhpposOrder $order): View
    {
        $order->load(['items.item', 'items.kit', 'supplier', 'location', 'employee']);
        $qtyOnHand = $this->getQtyOnHand($order);

        return view('orders.print', compact('order', 'qtyOnHand'));
    }

    public function export(Request $request)
    {
        $status = $request->query('status', 'open');
        $q = trim((string) $request->query('q', ''));

        $query = PhpposOrder::query()
            ->with(['supplier', 'items.item', 'items.kit'])
            ->where('deleted', 0)
            ->orderBy('order_time', 'desc');

        if ($status === 'open') {
            $query->where('suspended', 0);
        } elseif ($status === 'closed') {
            $query->where('suspended', 1);
        }

        if ($q !== '') {
            $query->where(function ($sq) use ($q) {
                $sq->where('internal_code', 'like', "%{$q}%")
                    ->orWhere('order_id', 'like', "%{$q}%")
                    ->orWhereHas('supplier', function ($sq2) use ($q) {
                        $sq2->where('company_name', 'like', "%{$q}%");
                    });
            });
        }

        $orders = $query->get();

        $filename = 'orders-export-'.now()->format('Y-m-d-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($orders) {
            $handle = fopen('php://output', 'w+');

            fputcsv($handle, [
                'Order ID', 'Supplier', 'Created Date', 'Status', 'Product ID', 'Item', 'Qty Ordered',
            ]);

            foreach ($orders as $order) {
                foreach ($order->items as $line) {
                    $itemName = $line->item_id
                        ? ($line->item->name ?? 'Unknown Item')
                        : ($line->item_kit_id ? ($line->kit->name ?? 'Unknown Kit') : ($line->description ?? 'Unknown'));
                    $productId = $line->item_id ? ($line->item->product_id ?? '') : '';
                    fputcsv($handle, [
                        $order->internal_code ?? 'PO-'.str_pad($order->order_id, 8, '0', STR_PAD_LEFT),
                        $order->supplier->company_name ?? '—',
                        Carbon::parse($order->order_time)->format('Y-m-d H:i:s'),
                        $order->suspended ? 'Closed' : 'Open',
                        $productId,
                        $itemName,
                        (float) $line->quantity_purchased,
                    ]);
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportXls(Request $request)
    {
        $status = $request->query('status', 'open');
        $q = trim((string) $request->query('q', ''));

        $query = PhpposOrder::query()
            ->with(['supplier', 'items.item', 'items.kit'])
            ->where('deleted', 0)
            ->orderBy('order_time', 'desc');

        if ($status === 'open') {
            $query->where('suspended', 0);
        } elseif ($status === 'closed') {
            $query->where('suspended', 1);
        }

        if ($q !== '') {
            $query->where(function ($sq) use ($q) {
                $sq->where('internal_code', 'like', "%{$q}%")
                    ->orWhere('order_id', 'like', "%{$q}%")
                    ->orWhereHas('supplier', function ($sq2) use ($q) {
                        $sq2->where('company_name', 'like', "%{$q}%");
                    });
            });
        }

        $orders = $query->get();

        $filename = 'orders-export-'.now()->format('Y-m-d-His').'.xls';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($orders) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Orders</x:Name></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';
            echo '<body><table>';
            echo '<thead><tr>';
            echo '<th>Order ID</th><th>Supplier</th><th>Created Date</th><th>Status</th><th>Product ID</th><th>Item</th><th>Qty Ordered</th>';
            echo '</tr></thead><tbody>';

            foreach ($orders as $order) {
                foreach ($order->items as $line) {
                    $itemName = $line->item_id
                        ? e($line->item->name ?? 'Unknown Item')
                        : ($line->item_kit_id ? e($line->kit->name ?? 'Unknown Kit') : e($line->description ?? 'Unknown'));
                    $productId = $line->item_id ? e($line->item->product_id ?? '') : '';
                    echo '<tr>';
                    echo '<td>'.e($order->internal_code ?? 'PO-'.str_pad($order->order_id, 8, '0', STR_PAD_LEFT)).'</td>';
                    echo '<td>'.e($order->supplier->company_name ?? '—').'</td>';
                    echo '<td>'.\Carbon\Carbon::parse($order->order_time)->format('Y-m-d H:i:s').'</td>';
                    echo '<td>'.($order->suspended ? 'Closed' : 'Open').'</td>';
                    echo '<td>'.$productId.'</td>';
                    echo '<td>'.$itemName.'</td>';
                    echo '<td>'.(float) $line->quantity_purchased.'</td>';
                    echo '</tr>';
                }
            }

            echo '</tbody></table></body></html>';
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getQtyOnHand(PhpposOrder $order): array
    {
        $locationId = $order->location_id;
        $itemIds = $order->items->pluck('item_id')->filter()->values()->toArray();

        $locationQtys = [];
        if (! empty($itemIds)) {
            $locationQtys = DB::table('phppos_location_items')
                ->where('location_id', $locationId)
                ->whereIn('item_id', $itemIds)
                ->pluck('quantity', 'item_id')
                ->toArray();
        }

        $qtyOnHand = [];
        foreach ($order->items as $item) {
            if ($item->item_id) {
                $qtyOnHand[$item->item_id] = (float) ($locationQtys[$item->item_id] ?? $item->item?->default_quantity ?? 0);
            } elseif ($item->item_kit_id) {
                $qtyOnHand['kit_'.$item->item_kit_id] = (float) ($item->kit?->default_quantity ?? 0);
            }
        }

        return $qtyOnHand;
    }

    public function destroy(PhpposOrder $order): JsonResponse
    {
        try {
            $order->update(['deleted' => 1]);

            return response()->json(['success' => true, 'message' => 'Order deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
