<?php

namespace App\Http\Controllers;

use App\Jobs\AnnouncePresence;
use App\Jobs\SendItem;
use App\Models\Location;
use App\Models\Notification;
use App\Models\PhpposItem;
use App\Models\PhpposItemKit;
use App\Models\PhpposLocation;
use App\Models\PhpposReceiving;
use App\Models\PhpposReceivingItem;
use App\Models\PhpposTransfer;
use App\Models\PhpposTransferItem;
use App\Models\TransferQueue;
use App\Services\LanLocationRegistry;
use App\Services\LocationContextService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LanController extends Controller
{
    public function announce(Request $request, LanLocationRegistry $registry): JsonResponse
    {
        $data = $request->validate([
            'ip' => ['required', 'string', 'max:45'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'phppos_location_ulid' => ['nullable', 'string', 'max:26'],
        ]);

        $this->log('Announce from '.$data['name'].' ('.$data['ip'].':'.$data['port'].') ulid='.($data['phppos_location_ulid'] ?? 'null'));

        $registry->upsertPeer(
            $data['ip'],
            (int) $data['port'],
            $data['name'],
            $data['slug'] ?? null,
            $data['phppos_location_ulid'] ?? null
        );

        return response()->json(['ok' => true]);
    }

    public function pokeReceived(Request $request, LanLocationRegistry $registry): JsonResponse
    {
        $data = $request->validate([
            'ip' => ['required', 'string', 'max:45'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'phppos_location_ulid' => ['nullable', 'string', 'max:26'],
            'poke_id' => ['required', 'string', 'max:50'],
        ]);

        $this->log('Poke received from '.$data['name'].' ('.$data['ip'].':'.$data['port'].') poke_id='.$data['poke_id']);

        $location = $registry->upsertPeer(
            $data['ip'],
            (int) $data['port'],
            $data['name'],
            $data['slug'] ?? null,
            $data['phppos_location_ulid'] ?? null
        );

        $location->update(['last_poke_received_at' => now()]);

        try {
            Notification::create([
                'type' => 'poke_received',
                'title' => 'Poke received from '.$data['name'],
                'body' => $data['ip'].':'.$data['port'],
                'action_url' => '/lan/locations',
            ]);
        } catch (\Throwable) {
        }

        return response()->json(['ok' => true]);
    }

    public function sync(): JsonResponse
    {
        $this->log('Manual sync triggered');
        AnnouncePresence::dispatch();

        return response()->json(['ok' => true]);
    }

    public function receive(Request $request, LocationContextService $locationContextService, LanLocationRegistry $registry): JsonResponse
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
                'payload.source_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
                'payload.source_name' => ['nullable', 'string', 'max:255'],
                'payload.transfer_out_id' => ['required', 'string', 'max:100'],
                'payload.transfer_code' => ['nullable', 'string', 'max:50'],
                'payload.from_location_ulid' => ['required', 'string', 'max:26'],
                'payload.to_location_ulid' => ['required', 'string', 'max:26'],
                'payload.notes' => ['nullable', 'string'],
                'payload.status' => ['nullable', 'string', 'in:open,closed'],
                'payload.created_at' => ['nullable', 'date'],
                'payload.lines' => ['required', 'array', 'min:1'],
                'payload.lines.*.item_id' => ['nullable', 'integer'],
                'payload.lines.*.item_number' => ['nullable', 'string', 'max:255'],
                'payload.lines.*.product_id' => ['nullable', 'string', 'max:255'],
                'payload.lines.*.name' => ['nullable', 'string', 'max:255'],
                'payload.lines.*.cost_price' => ['nullable', 'numeric'],
                'payload.lines.*.unit_price' => ['nullable', 'numeric'],
                'payload.lines.*.markup' => ['nullable', 'numeric'],
                'payload.lines.*.markup_type' => ['nullable', 'string', 'max:50'],
                'payload.lines.*.item_kit_id' => ['nullable', 'integer'],
                'payload.lines.*.item_kit_name' => ['nullable', 'string', 'max:255'],
                'payload.lines.*.item_kit_product_id' => ['nullable', 'string', 'max:255'],
                'payload.lines.*.item_kit_cost_price' => ['nullable', 'numeric'],
                'payload.lines.*.item_kit_unit_price' => ['nullable', 'numeric'],
                'payload.lines.*.item_kit_default_quantity' => ['nullable', 'numeric'],
                'payload.lines.*.components' => ['nullable', 'array'],
                'payload.lines.*.components.*.item_id' => ['nullable', 'integer'],
                'payload.lines.*.components.*.item_number' => ['nullable', 'string', 'max:255'],
                'payload.lines.*.components.*.product_id' => ['nullable', 'string', 'max:255'],
                'payload.lines.*.components.*.name' => ['nullable', 'string', 'max:255'],
                'payload.lines.*.components.*.item_kit_id' => ['nullable', 'integer'],
                'payload.lines.*.components.*.item_kit_name' => ['nullable', 'string', 'max:255'],
                'payload.lines.*.components.*.quantity' => ['required', 'numeric', 'gt:0'],
                'payload.lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            ]);

            $payload = $transferPayload['payload'];
            $this->log('transfer_out_id='.$payload['transfer_out_id'].' from_ulid='.$payload['from_location_ulid'].' to_ulid='.$payload['to_location_ulid'].' lines='.count($payload['lines']));

            $fromLocation = PhpposLocation::where('ulid', $payload['from_location_ulid'])->first();
            $toLocation = PhpposLocation::where('ulid', $payload['to_location_ulid'])->first();

            $currentLocationId = $locationContextService->resolveLocationId(null);
            $currentLocation = PhpposLocation::where('location_id', $currentLocationId)->first();

            if (! $fromLocation || ! $toLocation || ! $currentLocation) {
                $this->log('FAIL: Location ULID not found — from='.($fromLocation ? 'ok' : 'MISSING').' to='.($toLocation ? 'ok' : 'MISSING').' current='.($currentLocation ? 'ok' : 'MISSING'));
                throw ValidationException::withMessages([
                    'location' => 'Location ULID not found on this device.',
                ]);
            }

            $this->log('from_location_id='.$fromLocation->location_id.' to_location_id='.$toLocation->location_id.' current_id='.$currentLocationId);

            $existing = DB::table('phppos_receivings')
                ->where('reference_id', $payload['transfer_out_id'])
                ->where('location_id', $currentLocationId)
                ->where('source', 'transfer')
                ->first();

            if ($existing) {
                $this->log('DUPLICATE: receiving #'.$existing->receiving_id.' already exists for transfer_out_id='.$payload['transfer_out_id']);

                return response()->json([
                    'ok' => true,
                    'message' => 'Already received',
                    'receiving_id' => $existing->receiving_id,
                ]);
            }

            if (! empty($payload['source_device_id']) && ! empty($payload['source_port'])) {
                $registry->upsertPeer(
                    $payload['source_device_id'],
                    (int) $payload['source_port'],
                    $payload['source_name'] ?? $payload['source_device_id'],
                    null,
                    $fromLocation->ulid,
                );
            }

            if ((int) $toLocation->location_id !== (int) $currentLocationId) {
                $this->log('FAIL: to_location_id '.$toLocation->location_id.' != current '.$currentLocationId);
                throw ValidationException::withMessages([
                    'location' => 'Transfer destination does not match the current node location.',
                ]);
            }

            $lines = [];
            foreach ($payload['lines'] as $index => $line) {
                $qty = (float) $line['quantity'];
                if ($qty <= 0) {
                    continue;
                }

                $itemKitId = ! empty($line['item_kit_id']) ? (int) $line['item_kit_id'] : null;
                $itemKitName = $line['item_kit_name'] ?? null;
                $itemKitProductId = $line['item_kit_product_id'] ?? null;

                // Kit header row — no item, just kit metadata
                if (empty($line['item_id']) && empty($line['item_number'])) {
                    // product_id is the only globally portable key in a distributed system — required.
                    if (! $itemKitProductId) {
                        $this->log('FAIL: line '.$index.' kit header missing item_kit_product_id — cannot resolve cross-device');
                        throw ValidationException::withMessages([
                            "payload.lines.$index.item_kit_product_id" => 'item_kit_product_id is required for kit lines in a distributed transfer.',
                        ]);
                    }

                    // Resolve by product_id only — no name/id fallbacks allowed.
                    $kit = PhpposItemKit::where('product_id', $itemKitProductId)->orderBy('id')->first();

                    // Auto-create kit if not found locally — product_id is guaranteed at this point.
                    if (! $kit && $itemKitName) {
                        try {
                            $kit = PhpposItemKit::create([
                                'name' => $itemKitName,
                                'product_id' => $itemKitProductId,
                                'cost_price' => (float) ($line['item_kit_cost_price'] ?? 0),
                                'unit_price' => (float) ($line['item_kit_unit_price'] ?? 0),
                                // 'default_quantity' => 0,
                                'default_quantity' => $qty,
                            ]);

                            // Wire up kit components — product_id → item_number only, no name/id fallbacks.
                            if (! empty($line['components'])) {
                                foreach ($line['components'] as $comp) {
                                    $compItem = null;
                                    if (! empty($comp['product_id'])) {
                                        $compItem = PhpposItem::where('product_id', $comp['product_id'])->orderBy('item_id')->first();
                                    }
                                    if (! $compItem && ! empty($comp['item_number'])) {
                                        $compItem = PhpposItem::where('item_number', $comp['item_number'])->orderBy('item_id')->first();
                                    }
                                    if (! $compItem) {
                                        $this->log('SKIP component: could not resolve product_id='.($comp['product_id'] ?? 'null').' item_number='.($comp['item_number'] ?? 'null'));
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
                        } catch (\Throwable $e) {
                            $this->log('FAIL: could not auto-create kit — '.$e->getMessage());
                        }
                    }

                    if (! $kit) {
                        $this->log('FAIL: line '.$index.' kit not found and could not be created — item_kit_product_id='.$itemKitProductId);
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

                // --- Regular item rows ---
                // product_id is the only portable key. item_id alone is local and cannot be trusted cross-device.
                $item = null;
                if (! empty($line['product_id'])) {
                    $item = PhpposItem::where('product_id', $line['product_id'])->orderBy('item_id')->first();
                } elseif (! empty($line['item_id'])) {
                    // item_id without product_id — cannot reliably resolve cross-device.
                    $this->log('SKIP line '.$index.': item_id present but no product_id — unreliable cross-device, skipping');
                    continue;
                }

                // item_number fallback (portable if unique, but no name fallback).
                if (! $item && ! empty($line['item_number'])) {
                    $item = PhpposItem::where('item_number', $line['item_number'])->orderBy('item_id')->first();
                }

                // Auto-create item if product_id is present but item not found locally.
                if (! $item && ! empty($line['product_id']) && ! empty($line['name'])) {
                    try {
                        $createData = [
                            'name' => $line['name'],
                            'item_number' => $line['item_number'] ?? null,
                            'product_id' => $line['product_id'],
                            'cost_price' => (float) ($line['cost_price'] ?? 0),
                            'unit_price' => (float) ($line['unit_price'] ?? 0),
                        ];
                        if (isset($line['markup'])) {
                            $createData['markup'] = (float) $line['markup'];
                        }
                        if (isset($line['markup_type'])) {
                            $createData['markup_type'] = $line['markup_type'];
                        }
                        $item = PhpposItem::create($createData);
                        $this->log('Auto-created item #'.$item->item_id.' ('.$line['name'].') product_id='.$line['product_id']);
                    } catch (\Throwable $e) {
                        $this->log('FAIL: could not auto-create item — '.$e->getMessage());
                        continue;
                    }
                }

                if (! $item) {
                    $this->log('FAIL: line '.$index.' item not resolved — product_id='.($line['product_id'] ?? 'null').' item_number='.($line['item_number'] ?? 'null'));
                    continue;
                }

                $this->log('Line '.$index.': resolved item #'.$item->item_id.' ('.$item->name.') qty='.$qty);

                $lines[] = [
                    'item_id' => $item->item_id,
                    'item_kit_id' => $itemKitId,
                    'item_kit_name' => $itemKitName,
                    'quantity' => $qty,
                ];
            }

            if (empty($lines)) {
                throw ValidationException::withMessages([
                    'payload.lines' => 'No valid lines with quantity > 0.',
                ]);
            }

            $this->log('Creating pending receiving from='.$fromLocation->location_id.' to='.$currentLocation->location_id.' transfer_out_id='.$payload['transfer_out_id']);

            $this->log('Full payload: '.json_encode($data));

            $employeeId = DB::table('phppos_employees')->value('person_id');
            $timestamp = isset($payload['created_at']) ? Carbon::parse($payload['created_at']) : now();

            $subtotal = 0.0;
            $totalQty = 0.0;
            foreach ($lines as $line) {
                if (! $line['item_id']) {
                    $totalQty += $line['quantity'];
                    continue;
                }
                $itemCost = (float) (PhpposItem::find($line['item_id'])?->cost_price ?? 0);
                $subtotal += $itemCost * $line['quantity'];
                $totalQty += $line['quantity'];
            }

            $senderName = DB::table('phppos_locations')
                ->where('location_id', $fromLocation->location_id)
                ->value('name') ?? $payload['source_device_id'] ?? 'Unknown';

            $receiving = DB::transaction(function () use ($currentLocationId, $payload, $employeeId, $timestamp, $lines, $subtotal, $totalQty, $fromLocation, $senderName): PhpposReceiving {
                $transferIn = PhpposTransfer::create([
                    'transfer_type' => 'in',
                    'from_location_id' => $fromLocation->location_id,
                    'to_location_id' => $currentLocationId,
                    'auto_generated' => false,
                    'status' => 'open',
                    'created_by_person_id' => $employeeId,
                    'notes' => 'Received from '.$senderName.' on '.($payload['source_device_id'] ?? '?').' — ref: '.($payload['transfer_code'] ?? $payload['transfer_out_id']),
                    'external_source' => $payload['source_device_id'] ?? null,
                    'external_transfer_id' => $payload['transfer_out_id'],
                ]);

                $receiving = PhpposReceiving::create([
                    'receiving_time' => $timestamp,
                    'closed_at' => null,
                    'supplier_id' => null,
                    'employee_id' => $employeeId,
                    'comment' => $payload['notes'] ?? 'Transfer from '.$senderName.' #'.($payload['transfer_code'] ?? $payload['transfer_out_id']),
                    'location_id' => $currentLocationId,
                    'subtotal' => $subtotal,
                    'total' => $subtotal,
                    'total_quantity_purchased' => $totalQty,
                    'total_quantity_received' => 0,
                    'mode' => 'transfer',
                    'type' => 'transfer',
                    'source' => 'transfer',
                    'reference_id' => $payload['transfer_out_id'],
                ]);
                $receiving->syncDocumentIdentity();

                $transferIn->update(['notes' => $receiving->internal_code]);
                try {
                    Notification::create([
                        'type' => 'transfer_received',
                        'reference_type' => 'receiving',
                        'reference_id' => $receiving->receiving_id,
                        'title' => 'Transfer received from '.$senderName,
                        'body' => ($payload['transfer_code'] ?? 'Transfer #'.$payload['transfer_out_id']).' — '.count($lines).' item(s), awaiting confirmation.',
                        'action_url' => '/purchases/'.$receiving->receiving_id,
                    ]);
                } catch (\Throwable) {
                }

                $lineNumber = 0;
                foreach ($lines as $line) {
                    $itemId = $line['item_id'];
                    $itemKitId = $line['item_kit_id'] ?? null;
                    $itemKitName = $line['item_kit_name'] ?? null;
                    $itemCost = $itemId ? (float) (PhpposItem::find($itemId)?->cost_price ?? 0) : 0;

                    PhpposReceivingItem::create([
                        'receiving_id' => $receiving->receiving_id,
                        'item_id' => $itemId,
                        'item_kit_id' => $itemKitId,
                        'line' => $lineNumber,
                        'quantity_purchased' => $line['quantity'],
                        'quantity_received' => 0,
                        'item_cost_price' => $itemCost,
                        'item_unit_price' => $itemCost,
                        'discount_percent' => 0,
                        'subtotal' => $itemCost * $line['quantity'],
                        'total' => $itemCost * $line['quantity'],
                    ]);

                    PhpposTransferItem::create([
                        'transfer_id' => $transferIn->id,
                        'item_id' => $itemId,
                        'item_kit_id' => $itemKitId,
                        'item_kit_name' => $itemKitName,
                        'quantity' => $line['quantity'],
                    ]);

                    $lineNumber++;
                }

                return $receiving;
            });

            $this->log('SUCCESS: Created receiving #'.$receiving->receiving_id.' ('.$receiving->internal_code.') for transfer_out_id='.$payload['transfer_out_id']);

            return response()->json([
                'ok' => true,
                'message' => 'Transfer received',
                'receiving_id' => $receiving->receiving_id,
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

    public function appNotifications(): JsonResponse
    {
        $recent = Notification::latest()->take(2)->get()->map(fn (Notification $n): array => [
            'id' => $n->id,
            'type' => $n->type,
            'reference_type' => $n->reference_type,
            'reference_id' => $n->reference_id,
            'title' => $n->title,
            'body' => $n->body,
            'action_url' => $n->action_url,
            'is_unread' => $n->read_at === null,
            'created_at' => $n->created_at,
            'time_ago' => $n->created_at?->diffForHumans(),
        ]);

        return response()->json([
            'unread_count' => Notification::unread()->count(),
            'notifications' => $recent,
        ]);
    }

    public function allNotifications(): View
    {
        $notifications = Notification::latest()->paginate(20);
        $canDelete = $this->canDeleteNotifications();

        return view('notifications.index', compact('notifications', 'canDelete'));
    }

    public function deleteNotification(int $id): JsonResponse|RedirectResponse
    {
        if (! $this->canDeleteNotifications()) {
            abort(403, 'You do not have permission to delete notifications.');
        }

        $notification = Notification::findOrFail($id);
        $notification->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('app.notifications.all')->with('status', 'Notification deleted.');
    }

    private function canDeleteNotifications(): bool
    {
        $employee = auth('employee')->user();

        return $employee && $employee->hasModulePermission('config');
    }

    public function readNotification(int $id): JsonResponse|RedirectResponse
    {
        $notification = Notification::findOrFail($id);
        $notification->markAsRead();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('app.notifications.all')->with('status', 'Notification marked as read.');
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

    public function transferCompleted(Request $request): JsonResponse
    {
        $data = $request->validate([
            'transfer_out_id' => ['required', 'string', 'max:100'],
            'receiving_code' => ['nullable', 'string', 'max:50'],
        ]);

        $this->log('Transfer completed signal for transfer_out_id='.$data['transfer_out_id'].' code='.($data['receiving_code'] ?? '?'));

        $transferOut = PhpposTransfer::where('id', $data['transfer_out_id'])
            ->where('transfer_type', 'out')
            ->first();

        if ($transferOut) {
            try {
                Notification::create([
                    'type' => 'transfer_completed',
                    'reference_type' => 'transfer',
                    'reference_id' => (int) $data['transfer_out_id'],
                    'title' => 'Transfer #'.$data['transfer_out_id'].' completed by receiver',
                    'body' => 'Receiving '.($data['receiving_code'] ?? '').' — items confirmed on remote location.',
                    'action_url' => '/transfers',
                ]);
            } catch (\Throwable) {
            }
        }

        return response()->json(['ok' => true]);
    }
}
