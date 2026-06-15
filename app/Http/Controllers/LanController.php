<?php

namespace App\Http\Controllers;

use App\Jobs\AnnouncePresence;
use App\Jobs\SendItem;
use App\Models\Location;
use App\Models\TransferQueue;
use App\Models\PhpposItem;
use App\Models\PhpposLocation;
use App\Services\LanLocationRegistry;
use App\Services\InventoryFlowService;
use App\Services\LocationContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LanController extends Controller
{
    public function announce(Request $request, LanLocationRegistry $registry): JsonResponse
    {
        $data = $request->validate([
            'ip' => ['required', 'string', 'max:45'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'name' => ['required', 'string', 'max:255'],
            'phppos_location_ulid' => ['nullable', 'string', 'max:26'],
        ]);

        $this->log('Announce from '.$data['name'].' ('.$data['ip'].':'.$data['port'].') ulid='.($data['phppos_location_ulid'] ?? 'null'));

        $registry->upsertPeer($data['ip'], (int) $data['port'], $data['name'], $data['phppos_location_ulid'] ?? null);

        return response()->json(['ok' => true]);
    }

    public function sync(): JsonResponse
    {
        $this->log('Manual sync triggered');
        AnnouncePresence::dispatch();

        return response()->json(['ok' => true]);
    }

    public function receive(Request $request, InventoryFlowService $inventoryFlowService, LocationContextService $locationContextService): JsonResponse
    {
        $this->log('Incoming receive from '.$request->input('from_ip', '?'));

        $data = $request->validate([
            'item_type' => ['required', 'string', 'max:255'],
            'item_id' => ['required', 'integer'],
            'payload' => ['required', 'array'],
            'from_ip' => ['required', 'string', 'max:45'],
        ]);

        $this->log('Validated: item_type='.$data['item_type'].' item_id='.$data['item_id']);

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
            $this->log('transfer_out_id='.$payload['transfer_out_id'].' from_ulid='.$payload['from_location_ulid'].' to_ulid='.$payload['to_location_ulid'].' lines='.count($payload['lines']));

            $fromLocation = PhpposLocation::where('ulid', $payload['from_location_ulid'])->first();
            $toLocation = PhpposLocation::where('ulid', $payload['to_location_ulid'])->first();

            $currentLocationId = $locationContextService->resolveLocationId(null);
            $currentLocation = PhpposLocation::where('location_id', $currentLocationId)->first();

            if (!$fromLocation || !$toLocation || !$currentLocation) {
                $this->log('FAIL: Location ULID not found — from='.($fromLocation?'ok':'MISSING').' to='.($toLocation?'ok':'MISSING').' current='.($currentLocation?'ok':'MISSING'));
                throw ValidationException::withMessages([
                    'location' => 'Location ULID not found on this device.',
                ]);
            }

            $this->log('from_location_id='.$fromLocation->location_id.' to_location_id='.$toLocation->location_id.' current_id='.$currentLocationId);

            if ((int) $toLocation->location_id !== (int) $currentLocationId) {
                $this->log('FAIL: to_location_id '.$toLocation->location_id.' != current '.$currentLocationId);
                throw ValidationException::withMessages([
                    'location' => 'Transfer destination does not match the current node location.',
                ]);
            }

            $lines = [];
            foreach ($payload['lines'] as $index => $line) {
                if (empty($line['item_id']) && empty($line['item_number'])) {
                    $this->log('FAIL: line '.$index.' has no item_id or item_number');
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
                    $this->log('FAIL: line '.$index.' item not found — item_id='.($line['item_id'] ?? 'null').' item_number='.($line['item_number'] ?? 'null'));
                    throw ValidationException::withMessages([
                        "payload.lines.$index.item_id" => 'Item not found for this transfer line.',
                    ]);
                }

                $lines[] = [
                    'item_id' => $item->item_id,
                    'quantity' => (float) $line['quantity'],
                ];
            }

            $this->log('Calling importTransferIn from='.$fromLocation->location_id.' to='.$currentLocation->location_id.' external_transfer_id='.$payload['transfer_out_id']);

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

            $this->log('SUCCESS: Transfer received for transfer_out_id='.$payload['transfer_out_id']);

            return response()->json([
                'ok' => true,
                'message' => 'Transfer received',
            ]);
        }

        $this->log('Unhandled item_type: '.$data['item_type']);

        return response()->json([
            'ok' => true,
            'message' => 'Received',
        ]);
    }

    private function log(string $message): void
    {
        if (config('app.debug') || env('LAN_SYNC_DEBUG') === 'true') {
            Log::channel('lan_sync')->debug('[Receive] '.$message);
        }
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
