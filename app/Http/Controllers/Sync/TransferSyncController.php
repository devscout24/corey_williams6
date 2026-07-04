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
        $lines = $items->filter(static function ($item): bool {
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
                            'type' => 'item',
                            'item_number' => $compItem?->item_number,
                            'product_id' => $compItem?->product_id,
                            'name' => $compItem?->name ?? 'Item #'.$kitItem->item_id,
                            'quantity' => (float) $kitItem->quantity,
                        ];
                    }

                    foreach ($kitModel->nestedKits as $nestedKit) {
                        $components[] = $this->buildKitComponentPayload((int) $nestedKit->item_kit_item_kit, (float) $nestedKit->quantity);
                    }
                }
            }

            return [
                'item_number' => $itemModel?->item_number,
                'product_id' => $itemModel?->product_id,
                'name' => $itemModel?->name ?? $item->item_kit_name,
                'cost_price' => $itemModel?->cost_price,
                'unit_price' => $itemModel?->unit_price,
                'markup' => $itemModel?->markup,
                'markup_type' => $itemModel?->markup_type,
                'item_kit_number' => $kitModel?->item_kit_number,
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
            'lines.*.item_number' => 'nullable|string|max:255',
            'lines.*.product_id' => 'nullable|string|max:255',
            'lines.*.name' => 'nullable|string|max:255',
            'lines.*.cost_price' => 'nullable|numeric',
            'lines.*.unit_price' => 'nullable|numeric',
            'lines.*.markup' => 'nullable|numeric',
            'lines.*.markup_type' => 'nullable|string|max:50',
            'lines.*.item_kit_number' => 'nullable|string|max:255',
            'lines.*.item_kit_product_id' => 'nullable|string|max:255',
            'lines.*.item_kit_cost_price' => 'nullable|numeric',
            'lines.*.item_kit_unit_price' => 'nullable|numeric',
            'lines.*.item_kit_default_quantity' => 'nullable|numeric',
            'lines.*.components' => 'nullable|array',
            'lines.*.components.*.item_number' => 'nullable|string|max:255',
            'lines.*.components.*.product_id' => 'nullable|string|max:255',
            'lines.*.components.*.name' => 'nullable|string|max:255',
            'lines.*.components.*.item_kit_number' => 'nullable|string|max:255',
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

        $lines = [];

        foreach ($data['lines'] as $index => $line) {
            $qty = (float) $line['quantity'];
            if ($qty <= 0) {
                continue;
            }

            $itemKitNumber = $line['item_kit_number'] ?? null;
            $itemKitProductId = $line['item_kit_product_id'] ?? null;

            if (empty($line['item_number']) && empty($line['product_id'])) {
                if (! $itemKitNumber && ! $itemKitProductId) {
                    throw ValidationException::withMessages([
                        "lines.$index.item_number" => 'item_number or kit number/product_id is required.',
                    ]);
                }

                $kit = null;
                if ($itemKitNumber) {
                    $kit = PhpposItemKit::where('item_kit_number', $itemKitNumber)->orderBy('id')->first();
                }
                if (! $kit && $itemKitProductId) {
                    $kit = PhpposItemKit::where('product_id', $itemKitProductId)->orderBy('id')->first();
                }

                if (! $kit && ($itemKitNumber || $itemKitProductId)) {
                    try {
                        $kit = PhpposItemKit::create([
                            'name' => $line['name'] ?? ($itemKitNumber ?? 'Kit'),
                            'item_kit_number' => $itemKitNumber,
                            'product_id' => $itemKitProductId ?? null,
                            'cost_price' => (float) ($line['item_kit_cost_price'] ?? 0),
                            'unit_price' => (float) ($line['item_kit_unit_price'] ?? 0),
                            'default_quantity' => 0,
                        ]);
                    } catch (\Throwable) {
                    }
                }

                if (! $kit) {
                    continue;
                }

                if (! empty($line['components'])) {
                    foreach ($line['components'] as $comp) {
                        $componentType = $comp['type'] ?? null;

                        if ($componentType === 'kit' || ($componentType === null && ! empty($comp['item_kit_number']) && empty($comp['item_number']) && empty($comp['product_id']))) {
                            $nestedKit = null;
                            if (! empty($comp['item_kit_number'])) {
                                $nestedKit = PhpposItemKit::where('item_kit_number', $comp['item_kit_number'])->orderBy('id')->first();
                            }
                            if (! $nestedKit && ! empty($comp['item_kit_product_id'])) {
                                $nestedKit = PhpposItemKit::where('product_id', $comp['item_kit_product_id'])->orderBy('id')->first();
                            }
                            if (! $nestedKit && ($comp['item_kit_number'] ?? null || $comp['item_kit_product_id'] ?? null)) {
                                try {
                                    $nestedKit = PhpposItemKit::create([
                                        'name' => $comp['name'] ?? ($comp['item_kit_number'] ?? 'Kit'),
                                        'item_kit_number' => $comp['item_kit_number'] ?? null,
                                        'product_id' => $comp['item_kit_product_id'] ?? null,
                                        'cost_price' => 0,
                                        'unit_price' => 0,
                                        'default_quantity' => 0,
                                    ]);
                                } catch (\Throwable) {
                                }
                            }

                            if ($nestedKit) {
                                DB::table('phppos_item_kit_item_kits')->insert([
                                    'item_kit_id' => $kit->id,
                                    'item_kit_item_kit' => $nestedKit->id,
                                    'quantity' => (float) $comp['quantity'],
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);

                                if (! empty($comp['components'])) {
                                    $this->processNestedKitComponents($nestedKit, $comp['components']);
                                }
                            }

                            continue;
                        }

                        $compItem = null;
                        if (! empty($comp['item_number'])) {
                            $compItem = PhpposItem::where('item_number', $comp['item_number'])->orderBy('item_id')->first();
                        }
                        if (! $compItem && ! empty($comp['product_id'])) {
                            $compItem = PhpposItem::where('product_id', $comp['product_id'])->orderBy('item_id')->first();
                        }
                        if (! $compItem && ! empty($comp['item_number'])) {
                            try {
                                $compItem = PhpposItem::create([
                                    'name' => $comp['name'] ?? $comp['item_number'],
                                    'item_number' => $comp['item_number'] ?? null,
                                    'product_id' => $comp['product_id'] ?? null,
                                    'cost_price' => (float) ($comp['cost_price'] ?? 0),
                                    'unit_price' => (float) ($comp['unit_price'] ?? 0),
                                    'default_quantity' => 0,
                                ]);
                            } catch (\Throwable) {
                            }
                        }

                        if (! $compItem) {
                            continue;
                        }

                        DB::table('phppos_item_kit_items')->insert([
                            'item_kit_id' => $kit->id,
                            'item_id' => $compItem->item_id,
                            'quantity' => (float) $comp['quantity'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $lines[] = [
                    'item_kit_number' => $kit->item_kit_number,
                    'product_id' => $kit->product_id,
                    'quantity' => $qty,
                ];

                continue;
            }

            $item = null;
            if (! empty($line['item_number'])) {
                $item = PhpposItem::where('item_number', $line['item_number'])->orderBy('item_id')->first();
            }
            if (! $item && ! empty($line['product_id'])) {
                $item = PhpposItem::where('product_id', $line['product_id'])->orderBy('item_id')->first();
            }
            if (! $item && ! empty($line['item_number'])) {
                try {
                    $item = PhpposItem::create([
                        'name' => $line['name'] ?? $line['item_number'],
                        'item_number' => $line['item_number'] ?? null,
                        'product_id' => $line['product_id'] ?? null,
                        'cost_price' => (float) ($line['cost_price'] ?? 0),
                        'unit_price' => (float) ($line['unit_price'] ?? 0),
                        'default_quantity' => 0,
                    ]);
                } catch (\Throwable) {
                    continue;
                }
            }

            if (! $item) {
                continue;
            }

            $lines[] = [
                'item_number' => $item->item_number,
                'product_id' => $item->product_id,
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

    private function buildKitComponentPayload(int $kitId, float $quantity): array
    {
        $kitModel = PhpposItemKit::with(['items.item', 'nestedKits'])->find($kitId);
        $components = [];

        if ($kitModel) {
            foreach ($kitModel->items as $kitItem) {
                $compItem = $kitItem->item;
                $components[] = [
                    'type' => 'item',
                    'item_number' => $compItem?->item_number,
                    'product_id' => $compItem?->product_id,
                    'name' => $compItem?->name ?? 'Item #'.$kitItem->item_id,
                    'quantity' => (float) $kitItem->quantity,
                ];
            }

            foreach ($kitModel->nestedKits as $nested) {
                $components[] = $this->buildKitComponentPayload((int) $nested->item_kit_item_kit, (float) $nested->quantity);
            }
        }

        return [
            'type' => 'kit',
            'item_kit_number' => $kitModel?->item_kit_number,
            'item_kit_product_id' => $kitModel?->product_id,
            'name' => $kitModel?->name ?? 'Kit #'.$kitId,
            'quantity' => $quantity,
            'components' => $components,
        ];
    }

    private function processNestedKitComponents(PhpposItemKit $parentKit, array $components): void
    {
        foreach ($components as $comp) {
            $componentType = $comp['type'] ?? null;

            if ($componentType === 'kit' || ($componentType === null && ! empty($comp['item_kit_number']) && empty($comp['item_number']) && empty($comp['product_id']))) {
                $subKit = null;
                if (! empty($comp['item_kit_number'])) {
                    $subKit = PhpposItemKit::where('item_kit_number', $comp['item_kit_number'])->orderBy('id')->first();
                }
                if (! $subKit && ! empty($comp['item_kit_product_id'])) {
                    $subKit = PhpposItemKit::where('product_id', $comp['item_kit_product_id'])->orderBy('id')->first();
                }
                if (! $subKit && ($comp['item_kit_number'] ?? null || $comp['item_kit_product_id'] ?? null)) {
                    try {
                        $subKit = PhpposItemKit::create([
                            'name' => $comp['name'] ?? ($comp['item_kit_number'] ?? 'Kit'),
                            'item_kit_number' => $comp['item_kit_number'] ?? null,
                            'product_id' => $comp['item_kit_product_id'] ?? null,
                            'cost_price' => 0,
                            'unit_price' => 0,
                            'default_quantity' => 0,
                        ]);
                    } catch (\Throwable) {
                    }
                }

                if ($subKit) {
                    DB::table('phppos_item_kit_item_kits')->insert([
                        'item_kit_id' => $parentKit->id,
                        'item_kit_item_kit' => $subKit->id,
                        'quantity' => (float) $comp['quantity'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    if (! empty($comp['components'])) {
                        $this->processNestedKitComponents($subKit, $comp['components']);
                    }
                }

                continue;
            }

            $compItem = null;
            if (! empty($comp['item_number'])) {
                $compItem = PhpposItem::where('item_number', $comp['item_number'])->orderBy('item_id')->first();
            }
            if (! $compItem && ! empty($comp['product_id'])) {
                $compItem = PhpposItem::where('product_id', $comp['product_id'])->orderBy('item_id')->first();
            }
            if (! $compItem && ! empty($comp['item_number'])) {
                try {
                    $compItem = PhpposItem::create([
                        'name' => $comp['name'] ?? $comp['item_number'],
                        'item_number' => $comp['item_number'] ?? null,
                        'product_id' => $comp['product_id'] ?? null,
                        'cost_price' => (float) ($comp['cost_price'] ?? 0),
                        'unit_price' => (float) ($comp['unit_price'] ?? 0),
                        'default_quantity' => 0,
                    ]);
                } catch (\Throwable) {
                }
            }

            if ($compItem) {
                DB::table('phppos_item_kit_items')->insert([
                    'item_kit_id' => $parentKit->id,
                    'item_id' => $compItem->item_id,
                    'quantity' => (float) $comp['quantity'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
