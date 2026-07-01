<?php

namespace App\Http\Controllers\Sync;

use App\Http\Controllers\Controller;
use App\Models\PhpposItem;
use App\Models\PhpposItemKit;
use App\Models\PhpposLocation;
use App\Models\PhpposTransfer;
use App\Models\PhpposTransferItem;
use App\Services\InventoryFlowService;
use App\Services\LocationContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferSyncController extends Controller
{
    public function __construct(
        private readonly InventoryFlowService $inventoryFlowService,
        private readonly LocationContextService $locationContextService,
    ) {}

    public function ping(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'device_id' => config('sync.device_id'),
        ]);
    }

    public function exportTransferOut(string $transferId): JsonResponse
    {
        $transfer = PhpposTransfer::where('id', $transferId)
            ->where('transfer_type', 'out')
            ->firstOrFail();

        $fromLocation = PhpposLocation::where('location_id', $transfer->from_location_id)->first();
        $toLocation = PhpposLocation::where('location_id', $transfer->to_location_id)->first();

        if (! $fromLocation || ! $toLocation) {
            return response()->json(['message' => 'Transfer locations not found.'], 422);
        }

        $items = PhpposTransferItem::where('transfer_id', $transfer->id)->get();
        $lines = $items->filter(function ($item) {
            return (float) $item->quantity > 0;
        })->map(function ($item) {
            $itemModel = $item->item_id ? PhpposItem::find($item->item_id) : null;

            $kitModel = null;
            $components = [];
            if ($item->item_kit_id && ! $item->item_id) {
                $kitModel = PhpposItemKit::with(['items.item', 'nestedKits'])->find((int) $item->item_kit_id);
                if ($kitModel) {
                    foreach ($kitModel->items as $kitItem) {
                        $compItem = $kitItem->item;
                        $components[] = [
                            'item_id' => (int) $kitItem->item_id,
                            'item_number' => $compItem?->item_number,
                            'product_id' => $compItem?->product_id,
                            'name' => $compItem?->name ?? 'Item #'.$kitItem->item_id,
                            'quantity' => (float) $kitItem->quantity,
                        ];
                    }
                    foreach ($kitModel->nestedKits as $nestedKit) {
                        $nestedKitModel = PhpposItemKit::find((int) $nestedKit->item_kit_item_kit);
                        $components[] = [
                            'item_kit_id'         => (int) $nestedKit->item_kit_item_kit,
                            'item_kit_name'       => $nestedKitModel?->name ?? 'Kit #'.$nestedKit->item_kit_item_kit,
                            'item_kit_product_id' => $nestedKitModel?->product_id,
                            'name'                => $nestedKitModel?->name ?? 'Kit #'.$nestedKit->item_kit_item_kit,
                            'quantity'            => (float) $nestedKit->quantity,
                        ];
                    }
                }
            }

            return [
                'item_id' => $item->item_id,
                'item_number' => $itemModel?->item_number,
                'product_id' => $itemModel?->product_id,
                'name' => $itemModel?->name ?? $item->item_kit_name,
                'cost_price' => $itemModel?->cost_price,
                'unit_price' => $itemModel?->unit_price,
                'markup' => $itemModel?->markup,
                'markup_type' => $itemModel?->markup_type,
                'item_kit_id' => $item->item_kit_id,
                'item_kit_name' => $item->item_kit_name,
                'item_kit_product_id' => $kitModel?->product_id,
                'item_kit_cost_price' => $kitModel?->cost_price,
                'item_kit_unit_price' => $kitModel?->unit_price,
                'item_kit_default_quantity' => $kitModel?->default_quantity,
                'components' => $components,
                'quantity' => (float) $item->quantity,
            ];
        })->values();

        return response()->json([
            'source_device_id' => config('sync.device_id'),
            'transfer_out_id' => (string) $transfer->id,
            'transfer_code' => $transfer->internal_code ?? ('TRN-OUT-'.str_pad((string) $transfer->id, 8, '0', STR_PAD_LEFT)),
            'from_location_ulid' => $fromLocation->ulid,
            'to_location_ulid' => $toLocation->ulid,
            'notes' => $transfer->notes,
            'created_at' => $transfer->created_at?->toISOString(),
            'lines' => $lines,
        ]);
    }

    public function receiveTransferOut(Request $request): JsonResponse
    {
        $data = $request->validate([
            'source_device_id' => 'required|string|max:100',
            'transfer_out_id' => 'required|string|max:100',
            'transfer_code' => 'nullable|string|max:50',
            'from_location_ulid' => 'required|string|max:26',
            'to_location_ulid' => 'required|string|max:26',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:open,closed',
            'created_at' => 'nullable|date',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'nullable|integer',
            'lines.*.item_number' => 'nullable|string|max:255',
            'lines.*.product_id' => 'nullable|string|max:255',
            'lines.*.name' => 'nullable|string|max:255',
            'lines.*.cost_price' => 'nullable|numeric',
            'lines.*.unit_price' => 'nullable|numeric',
            'lines.*.markup' => 'nullable|numeric',
            'lines.*.markup_type' => 'nullable|string|max:50',
            'lines.*.item_kit_id' => 'nullable|integer',
            'lines.*.item_kit_name' => 'nullable|string|max:255',
            'lines.*.item_kit_product_id' => 'nullable|string|max:255',
            'lines.*.item_kit_cost_price' => 'nullable|numeric',
            'lines.*.item_kit_unit_price' => 'nullable|numeric',
            'lines.*.item_kit_default_quantity' => 'nullable|numeric',
            'lines.*.components' => 'nullable|array',
            'lines.*.components.*.item_id' => 'nullable|integer',
            'lines.*.components.*.item_number' => 'nullable|string|max:255',
            'lines.*.components.*.product_id' => 'nullable|string|max:255',
            'lines.*.components.*.name' => 'nullable|string|max:255',
            'lines.*.components.*.item_kit_id' => 'nullable|integer',
            'lines.*.components.*.item_kit_name' => 'nullable|string|max:255',
            'lines.*.components.*.item_kit_product_id' => 'nullable|string|max:255',
            'lines.*.components.*.quantity' => 'required|numeric|gt:0',
            'lines.*.quantity' => 'required|numeric|gt:0',
        ]);

        $fromLocation = PhpposLocation::where('ulid', $data['from_location_ulid'])->first();
        $toLocation = PhpposLocation::where('ulid', $data['to_location_ulid'])->first();

        $currentLocationId = $this->locationContextService->resolveLocationId(null);
        $currentLocation = PhpposLocation::where('location_id', $currentLocationId)->first();

        if (! $fromLocation || ! $toLocation || ! $currentLocation) {
            throw ValidationException::withMessages([
                'location' => 'Location ULID not found on this device.',
            ]);
        }

        if ((int) $toLocation->location_id !== (int) $currentLocationId) {
            throw ValidationException::withMessages([
                'location' => 'Transfer destination does not match the current node location.',
            ]);
        }

        $toLocation = $currentLocation;

        $lines = [];
        foreach ($data['lines'] as $index => $line) {
            $qty = (float) $line['quantity'];
            if ($qty <= 0) {
                continue;
            }

            $itemKitId = ! empty($line['item_kit_id']) ? (int) $line['item_kit_id'] : null;
            $itemKitName = $line['item_kit_name'] ?? null;
            $itemKitProductId = $line['item_kit_product_id'] ?? null;

            // Kit header row — no item lookup needed
            if (empty($line['item_id']) && empty($line['item_number'])) {
                if (! $itemKitId && ! $itemKitProductId && ! $itemKitName) {
                    throw ValidationException::withMessages([
                        "lines.$index.item_id" => 'Item identifier or kit ID is required.',
                    ]);
                }

                // Resolve kit using portable identifiers first (product_id > name), then local ID
                $kit = null;
                if ($itemKitProductId) {
                    $kit = PhpposItemKit::where('product_id', $itemKitProductId)->orderBy('id')->first();
                }
                if (! $kit && $itemKitName) {
                    $kit = PhpposItemKit::where('name', $itemKitName)->orderBy('id')->first();
                }
                if (! $kit && $itemKitId) {
                    $kit = PhpposItemKit::find($itemKitId);
                }

                // Auto-create kit if still not found and we have a name
                if (! $kit && $itemKitName) {
                    try {
                        $kit = PhpposItemKit::create([
                            'name' => $itemKitName,
                            'product_id' => $itemKitProductId ?? null,
                            'cost_price' => (float) ($line['item_kit_cost_price'] ?? 0),
                            'unit_price' => (float) ($line['item_kit_unit_price'] ?? 0),
                            'default_quantity' => 0,
                        ]);

                        // Create kit component records from metadata
                        if (! empty($line['components'])) {
                            foreach ($line['components'] as $comp) {
                                // ── Nested kit component ──────────────────────
                                if (! empty($comp['item_kit_id'])) {
                                    $nestedKit = null;
                                    if (! empty($comp['item_kit_product_id'])) {
                                        $nestedKit = PhpposItemKit::where('product_id', $comp['item_kit_product_id'])->orderBy('id')->first();
                                    }
                                    if (! $nestedKit && ! empty($comp['item_kit_name'])) {
                                        $nestedKit = PhpposItemKit::where('name', $comp['item_kit_name'])->orderBy('id')->first();
                                    }
                                    if (! $nestedKit && ! empty($comp['item_kit_name'])) {
                                        try {
                                            $nestedKit = PhpposItemKit::create([
                                                'name'             => $comp['item_kit_name'],
                                                'product_id'       => $comp['item_kit_product_id'] ?? null,
                                                'cost_price'       => 0,
                                                'unit_price'       => 0,
                                                'default_quantity' => 0,
                                            ]);
                                        } catch (\Throwable) {
                                        }
                                    }
                                    if ($nestedKit) {
                                        DB::table('phppos_item_kit_item_kits')->insert([
                                            'item_kit_id'       => $kit->id,
                                            'item_kit_item_kit' => $nestedKit->id,
                                            'quantity'          => (float) $comp['quantity'],
                                            'created_at'        => now(),
                                            'updated_at'        => now(),
                                        ]);
                                    }
                                    continue;
                                }

                                // ── Item component (existing logic) ────────────
                                $compItemId = ! empty($comp['item_id']) ? (int) $comp['item_id'] : null;
                                $compItem = null;
                                if (! empty($comp['product_id'])) {
                                    $compItem = PhpposItem::where('product_id', $comp['product_id'])->orderBy('item_id')->first();
                                }
                                if (! $compItem && $compItemId) {
                                    $compItem = PhpposItem::find($compItemId);
                                }
                                if (! $compItem && ! empty($comp['item_number'])) {
                                    $compItem = PhpposItem::where('item_number', $comp['item_number'])->orderBy('item_id')->first();
                                }
                                if (! $compItem && ! empty($comp['name'])) {
                                    $compItem = PhpposItem::where('name', $comp['name'])->orderBy('item_id')->first();
                                }
                                if ($compItem) {
                                    DB::table('phppos_item_kit_items')->insert([
                                        'item_kit_id' => $kit->id,
                                        'item_id' => $compItem->item_id,
                                        'quantity' => (float) $comp['quantity'],
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                }
                            }
                        }
                    } catch (\Throwable) {
                    }
                }

                if (! $kit) {
                    continue;
                }

                $lines[] = [
                    'item_id' => null,
                    'item_kit_id' => (int) $kit->id,
                    'item_kit_name' => $kit->name,
                    'quantity' => $qty,
                ];

                continue;
            }

            // Resolve item: product_id is the portable cross-device identifier;
            // only trust item_id when product_id is also present (same device) or fall through chain.
            $item = null;
            if (! empty($line['item_id']) && ! empty($line['product_id'])) {
                // Both present — try product_id first (portable), then item_id as tiebreak
                $item = PhpposItem::where('product_id', $line['product_id'])->orderBy('item_id')->first();
                if (! $item) {
                    $item = PhpposItem::find($line['item_id']);
                }
            } elseif (! empty($line['product_id'])) {
                $item = PhpposItem::where('product_id', $line['product_id'])->orderBy('item_id')->first();
            } elseif (! empty($line['item_id'])) {
                $item = PhpposItem::find($line['item_id']);
            }

            // Fallback: match by item_number, then name before auto-creating
            if (! $item && ! empty($line['item_number'])) {
                $item = PhpposItem::where('item_number', $line['item_number'])->orderBy('item_id')->first();
            }
            if (! $item && ! empty($line['name'])) {
                $item = PhpposItem::where('name', $line['name'])->orderBy('item_id')->first();
            }

            // Auto-create item from metadata if not found
            if (! $item && ! empty($line['name'])) {
                try {
                    $data = [
                        'name' => $line['name'],
                        'item_number' => $line['item_number'] ?? null,
                        'product_id' => $line['product_id'] ?? null,
                        'cost_price' => (float) ($line['cost_price'] ?? 0),
                        'unit_price' => (float) ($line['unit_price'] ?? 0),
                    ];
                    if (isset($line['markup'])) {
                        $data['markup'] = (float) $line['markup'];
                    }
                    if (isset($line['markup_type'])) {
                        $data['markup_type'] = $line['markup_type'];
                    }
                    $item = PhpposItem::create($data);
                } catch (\Throwable) {
                    continue;
                }
            }

            if (! $item) {
                continue;
            }

            $lines[] = [
                'item_id' => $item->item_id,
                'item_kit_id' => $itemKitId,
                'item_kit_name' => $itemKitName,
                'quantity' => $qty,
            ];
        }

        if (empty($lines)) {
            throw ValidationException::withMessages([
                'lines' => 'No valid lines with quantity > 0.',
            ]);
        }

        $result = $this->inventoryFlowService->importTransferIn(
            $fromLocation->location_id,
            $toLocation->location_id,
            $lines,
            $data['source_device_id'],
            (string) $data['transfer_out_id'],
            $data['notes'] ?? null,
            $data['created_at'] ?? null,
            null,
            $data['status'] ?? 'closed',
            $data['transfer_code'] ?? null,
        );

        return response()->json([
            'success' => true,
            'transfer_in_id' => $result['transfer_in_id'],
            'already_imported' => $result['already_imported'],
            'receiving_id' => $result['receiving_id'] ?? null,
        ]);
    }
}
