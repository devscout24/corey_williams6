<?php

namespace App\Http\Controllers;

use App\Models\PhpposItem;
use App\Models\PhpposItemKit;
use App\Models\PhpposSupplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        $suppliers = PhpposSupplier::query()->where('deleted', 0)->orderBy('company_name')->get();

        return view('orders.index', [
            'suppliers' => $suppliers,
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
            ->where('phppos_item_kits.supplier_id', $supplierId)
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
}
