<?php

namespace App\Http\Controllers\Sync;

use App\Http\Controllers\Controller;
use App\Models\PhpposItem;
use App\Models\PhpposLocation;
use App\Models\PhpposTransfer;
use App\Models\PhpposTransferItem;
use App\Services\InventoryFlowService;
use App\Services\LocationContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransferSyncController extends Controller
{
    public function __construct(
        private readonly InventoryFlowService $inventoryFlowService,
        private readonly LocationContextService $locationContextService,
    ) {
    }

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
        $lines = $items->map(function ($item) {
            $itemModel = PhpposItem::find($item->item_id);
            return [
                'item_id' => $item->item_id,
                'item_number' => $itemModel?->item_number,
                'quantity' => (float) $item->quantity,
            ];
        })->values();

        return response()->json([
            'source_device_id' => config('sync.device_id'),
            'transfer_out_id' => (string) $transfer->id,
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
            'from_location_ulid' => 'required|string|max:26',
            'to_location_ulid' => 'required|string|max:26',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:open,closed',
            'created_at' => 'nullable|date',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'nullable|integer',
            'lines.*.item_number' => 'nullable|string|max:255',
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
            if (empty($line['item_id']) && empty($line['item_number'])) {
                throw ValidationException::withMessages([
                    "lines.$index.item_id" => 'Item identifier is required.',
                ]);
            }

            $item = null;
            if (! empty($line['item_id'])) {
                $item = PhpposItem::find($line['item_id']);
            }
            if (! $item && ! empty($line['item_number'])) {
                $item = PhpposItem::where('item_number', $line['item_number'])->first();
            }

            if (! $item) {
                throw ValidationException::withMessages([
                    "lines.$index.item_id" => 'Item not found for this transfer line.',
                ]);
            }

            $lines[] = [
                'item_id' => $item->item_id,
                'quantity' => (float) $line['quantity'],
            ];
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
            $data['status'] ?? 'closed'
        );

        return response()->json([
            'success' => true,
            'transfer_in_id' => $result['transfer_in_id'],
            'already_imported' => $result['already_imported'],
            'receiving_id' => $result['receiving_id'] ?? null,
        ]);
    }
}
