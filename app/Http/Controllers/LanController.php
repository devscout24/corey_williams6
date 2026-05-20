<?php

namespace App\Http\Controllers;

use App\Jobs\AnnouncePresence;
use App\Jobs\SendItem;
use App\Models\Location;
use App\Models\TransferQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function receive(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_type' => ['required', 'string', 'max:255'],
            'item_id' => ['required', 'integer'],
            'payload' => ['required', 'array'],
            'from_ip' => ['required', 'string', 'max:45'],
        ]);

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
