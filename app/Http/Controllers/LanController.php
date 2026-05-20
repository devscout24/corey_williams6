<?php

namespace App\Http\Controllers;

use App\Jobs\AnnouncePresence;
use App\Jobs\SendItem;
use App\Models\Location;
use App\Models\TransferQueue;
use App\Models\PhpposItem;
use App\Models\PhpposLocation;
use App\Services\InventoryFlowService;
use App\Services\LocationContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LanController extends Controller
{
    public function announce(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ip' => ['required', 'string', 'max:45'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $location = Location::firstOrCreate(
            ['ip' => $data['ip']],
            ['name' => $data['name']]
        );

        $location->name = $data['name'];
        $location->last_seen_at = now();
        $location->save();

        return response()->json(['ok' => true]);
    }

    public function sync(): JsonResponse
    {
        AnnouncePresence::dispatch();

        return response()->json(['ok' => true]);
    }

    public function receive(Request $request, InventoryFlowService $inventoryFlowService, LocationContextService $locationContextService): JsonResponse
    {
        $data = $request->validate([
            'item_type' => ['required', 'string', 'max:255'],
            'item_id' => ['required', 'integer'],
            'payload' => ['required', 'array'],
            'from_ip' => ['required', 'string', 'max:45'],
        ]);

        if ($data['item_type'] === 'transfer_out') {
            $transferPayload = $request->validate([
                'payload.source_device_id' => ['nullable', 'string', 'max:100'],
                'payload.transfer_out_id' => ['required', 'string', 'max:100'],
                'payload.from_location_ulid' => ['required', 'string', 'max:26'],
                'payload.to_location_ulid' => ['required', 'string', 'max:26'],
                'payload.notes' => ['nullable', 'string'],
                'payload.status' => ['nullable', 'string', 'in:open,closed'],
                'payload.created_at' => ['nullable', 'date'],
                'payload.lines' => ['required', 'array', 'min:1'],
                'payload.lines.*.item_id' => ['nullable', 'integer'],
                'payload.lines.*.item_number' => ['nullable', 'string', 'max:255'],
                'payload.lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            ]);

            $payload = $transferPayload['payload'];

            $fromLocation = PhpposLocation::where('ulid', $payload['from_location_ulid'])->first();
            $toLocation = PhpposLocation::where('ulid', $payload['to_location_ulid'])->first();

            $currentLocationId = $locationContextService->resolveLocationId(null);
            $currentLocation = PhpposLocation::where('location_id', $currentLocationId)->first();

            if (!$fromLocation || !$toLocation || !$currentLocation) {
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
            foreach ($payload['lines'] as $index => $line) {
                if (empty($line['item_id']) && empty($line['item_number'])) {
                    throw ValidationException::withMessages([
                        "payload.lines.$index.item_id" => 'Item identifier is required.',
                    ]);
                }

                $item = null;
                if (!empty($line['item_id'])) {
                    $item = PhpposItem::find($line['item_id']);
                }
                if (!$item && !empty($line['item_number'])) {
                    $item = PhpposItem::where('item_number', $line['item_number'])->first();
                }

                if (!$item) {
                    throw ValidationException::withMessages([
                        "payload.lines.$index.item_id" => 'Item not found for this transfer line.',
                    ]);
                }

                $lines[] = [
                    'item_id' => $item->item_id,
                    'quantity' => (float) $line['quantity'],
                ];
            }

            $inventoryFlowService->importTransferIn(
                $fromLocation->location_id,
                $currentLocation->location_id,
                $lines,
                (string) ($payload['source_device_id'] ?? $data['from_ip'] ?? 'unknown'),
                (string) $payload['transfer_out_id'],
                $payload['notes'] ?? null,
                $payload['created_at'] ?? null,
                null,
                $payload['status'] ?? 'closed'
            );

            return response()->json([
                'ok' => true,
                'message' => 'Transfer received',
            ]);
        }

        // TODO: Implement item-specific persistence/handling based on item_type.

        return response()->json([
            'ok' => true,
            'message' => 'Received',
        ]);
    }

    public function notifications(): JsonResponse
    {
        $transfers = TransferQueue::with('destination')
            ->latest()
            ->take(50)
            ->get()
            ->map(function (TransferQueue $transfer): array {
                $destination = $transfer->destination
                    ? trim($transfer->destination->name.' ('.$transfer->destination->ip.')')
                    : 'Unknown';

                return [
                    'id' => $transfer->id,
                    'destination' => $destination,
                    'item_type' => $transfer->item_type,
                    'item_id' => $transfer->item_id,
                    'status' => $transfer->status,
                    'created_at' => $transfer->created_at,
                ];
            });

        return response()->json($transfers);
    }

    public function locations(): JsonResponse
    {
        $locations = Location::orderByDesc('is_self')
            ->orderBy('name')
            ->get();

        return response()->json($locations);
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'item_type' => ['required', 'string', 'max:255'],
            'item_id' => ['required', 'integer'],
        ]);

        $transfer = TransferQueue::create([
            'location_id' => $data['location_id'],
            'item_type' => $data['item_type'],
            'item_id' => $data['item_id'],
            'status' => 'pending',
        ]);

        SendItem::dispatch($transfer);

        return response()->json(['ok' => true, 'id' => $transfer->id]);
    }

    public function retry(int $id): JsonResponse
    {
        $transfer = TransferQueue::where('status', 'failed')->findOrFail($id);

        $transfer->update([
            'status' => 'pending',
            'error' => null,
        ]);

        SendItem::dispatch($transfer);

        return response()->json(['ok' => true]);
    }
}
