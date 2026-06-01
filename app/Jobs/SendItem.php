<?php

namespace App\Jobs;

use App\Models\TransferQueue;
use App\Models\PhpposItem;
use App\Models\PhpposLocation;
use App\Models\PhpposTransfer;
use App\Models\PhpposTransferItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendItem implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public TransferQueue $transfer)
    {
    }

    public function handle(): void
    {
        $transfer = $this->transfer->fresh();
        if (!$transfer) {
            return;
        }

        $location = $transfer->destination;
        if (!$location || empty($location->ip)) {
            $transfer->update([
                'status' => 'failed',
                'error' => 'Destination location not found.',
            ]);
            return;
        }

        try {
            $payload = $this->buildPayload($transfer);
            if (!$payload) {
                $transfer->update([
                    'status' => 'failed',
                    'error' => 'Unsupported item_type or missing data.',
                ]);
                return;
            }

            $response = Http::timeout(10)->post("{$location->url}/api/lan/receive", $payload);

            if ($response->successful()) {
                $transfer->update([
                    'status' => 'delivered',
                    'error' => null,
                ]);
                return;
            }

            $transfer->update([
                'status' => 'failed',
                'error' => 'HTTP '.$response->status(),
            ]);
        } catch (\Throwable $exception) {
            $transfer->update([
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function buildPayload(TransferQueue $transfer): ?array
    {
        if ($transfer->item_type !== 'transfer_out') {
            return null;
        }

        $transferOut = PhpposTransfer::where('id', $transfer->item_id)
            ->where('transfer_type', 'out')
            ->first();

        if (!$transferOut) {
            return null;
        }

        $fromLocation = PhpposLocation::where('location_id', $transferOut->from_location_id)->first();
        $toLocation = PhpposLocation::where('location_id', $transferOut->to_location_id)->first();

        if (!$fromLocation || !$toLocation) {
            return null;
        }

        $items = PhpposTransferItem::where('transfer_id', $transferOut->id)->get();
        $lines = $items->map(function ($item) {
            $itemModel = PhpposItem::find($item->item_id);

            return [
                'item_id' => $item->item_id,
                'item_number' => $itemModel?->item_number,
                'quantity' => (float) $item->quantity,
            ];
        })->values()->toArray();

        return [
            'item_type' => 'transfer_out',
            'item_id' => $transferOut->id,
            'payload' => [
                'source_device_id' => config('app.node_ip') ?: config('app.node_name', 'unknown'),
                'transfer_out_id' => (string) $transferOut->id,
                'from_location_ulid' => $fromLocation->ulid,
                'to_location_ulid' => $toLocation->ulid,
                'notes' => $transferOut->notes,
                'status' => $transferOut->status,
                'created_at' => $transferOut->created_at?->toISOString(),
                'lines' => $lines,
            ],
            'from_ip' => config('app.node_ip'),
        ];
    }
}
