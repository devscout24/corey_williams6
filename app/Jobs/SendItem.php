<?php

namespace App\Jobs;

use App\Models\PhpposItem;
use App\Models\PhpposLocation;
use App\Models\PhpposTransfer;
use App\Models\PhpposTransferItem;
use App\Models\TransferQueue;
use App\Services\LanLocationRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SendItem implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public TransferQueue $transfer)
    {
    }

    public function backoff(): array
    {
        return [10, 20, 40];
    }

    public function handle(LanLocationRegistry $registry): void
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
            if (empty($location->port)) {
                $transfer->update([
                    'status' => 'failed',
                    'error' => 'Destination location port is not configured.',
                ]);
                return;
            }

            $self = $registry->selfOrFail();
            $payload = $this->buildPayload($transfer, $self->ip);
            if (!$payload) {
                $transfer->update([
                    'status' => 'failed',
                    'error' => 'Unsupported item_type or missing data.',
                ]);
                return;
            }

            $response = Http::timeout(10)
                ->withHeaders($this->syncHeaders())
                ->post($registry->urlFor($location).'/api/lan/receive', $payload);

            if ($response->successful()) {
                $transfer->update([
                    'status' => 'delivered',
                    'error' => null,
                ]);
                return;
            }

            throw new RuntimeException('HTTP '.$response->status().': '.mb_strimwidth($response->body(), 0, 500));
        } catch (\Throwable $exception) {
            if ($this->attempts() >= $this->tries) {
                $this->markTransferFailed($exception->getMessage());
                return;
            }

            throw $exception;
        }
    }

    public function failed(?\Throwable $exception): void
    {
        $this->markTransferFailed($exception?->getMessage() ?: 'Transfer send failed.');
    }

    private function buildPayload(TransferQueue $transfer, string $selfIp): ?array
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
                'source_device_id' => $selfIp,
                'transfer_out_id' => (string) $transferOut->id,
                'from_location_ulid' => $fromLocation->ulid,
                'to_location_ulid' => $toLocation->ulid,
                'notes' => $transferOut->notes,
                'status' => $transferOut->status,
                'created_at' => $transferOut->created_at?->toISOString(),
                'lines' => $lines,
            ],
            'from_ip' => $selfIp,
        ];
    }

    private function syncHeaders(): array
    {
        return [
            'X-Sync-Token' => (string) config('sync.shared_token'),
        ];
    }

    private function markTransferFailed(string $error): void
    {
        $transfer = $this->transfer->fresh();
        if (! $transfer) {
            return;
        }

        $transfer->update([
            'status' => 'failed',
            'error' => $error,
        ]);
    }
}
