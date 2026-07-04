<?php

namespace App\Jobs;

use App\Models\Location;
use App\Models\Notification;
use App\Models\PhpposItem;
use App\Models\PhpposItemKit;
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
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SendItem implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public TransferQueue $transfer) {}

    public function backoff(): array
    {
        return [10, 20, 40];
    }

    public function handle(LanLocationRegistry $registry): void
    {
        $transfer = $this->transfer->fresh();
        if (! $transfer) {
            $this->log('Transfer #'.$this->transfer->id.' vanished after fresh()');

            return;
        }

        $this->log('Handling SendItem #'.$transfer->id.' status='.$transfer->status);

        $location = $transfer->destination;
        if (! $location || empty($location->ip)) {
            $this->log('Destination not found for Transfer #'.$transfer->id);
            $transfer->update([
                'status' => 'failed',
                'error' => 'Destination location not found.',
            ]);

            return;
        }

        $this->log('Destination: '.$location->name.' ('.$location->ip.':'.$location->port.')');

        try {
            if (empty($location->port)) {
                $this->log('Destination port missing for '.$location->name);
                $transfer->update([
                    'status' => 'failed',
                    'error' => 'Destination location port is not configured.',
                ]);

                return;
            }

            $self = $registry->selfOrFail();
            $this->log('Self: '.$self->name.' @ '.$self->ip.':'.$self->port);

            $payload = $this->buildPayload($transfer, $self->ip, $self);
            if (! $payload) {
                $this->log('buildPayload returned null for Transfer #'.$transfer->id);
                $transfer->update([
                    'status' => 'failed',
                    'error' => 'Unsupported item_type or missing data.',
                ]);

                return;
            }

            $url = $registry->urlFor($location).'/api/lan/receive';
            $this->log('POST '.$url.' payload='.json_encode($payload));

            $response = Http::timeout(10)
                ->asJson()
                ->withHeaders($this->syncHeaders())
                ->post($url, $payload);

            $this->log('Response: HTTP '.$response->status().' body='.mb_strimwidth($response->body(), 0, 500));

            if ($response->successful()) {
                $transfer->update([
                    'status' => 'delivered',
                    'error' => null,
                ]);
                $this->log('Transfer #'.$transfer->id.' delivered successfully');

                try {
                    Notification::create([
                        'type' => 'transfer_delivered',
                        'reference_type' => 'transfer',
                        'reference_id' => $transfer->item_id,
                        'title' => 'Transfer #'.$transfer->id.' delivered',
                        'body' => 'Sent to '.$location->name.' ('.$location->ip.')',
                        'action_url' => '/lan/locations',
                    ]);
                } catch (\Throwable) {
                }

                return;
            }

            throw new RuntimeException('HTTP '.$response->status().': '.mb_strimwidth($response->body(), 0, 500));
        } catch (\Throwable $exception) {
            $this->log('Exception: '.$exception->getMessage());
            if ($this->attempts() >= $this->tries) {
                $this->markTransferFailed($exception->getMessage());

                return;
            }

            throw $exception;
        }
    }

    private function log(string $message): void
    {
        if (config('app.debug') || env('LAN_SYNC_DEBUG') === 'true') {
            Log::channel('lan_sync')->debug('[SendItem] '.$message);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        $this->markTransferFailed($exception?->getMessage() ?: 'Transfer send failed.');
    }

    private function buildPayload(TransferQueue $transfer, string $selfIp, ?Location $selfLocation = null): ?array
    {
        if ($transfer->item_type !== 'transfer_out') {
            return null;
        }

        $transferOut = PhpposTransfer::where('id', $transfer->item_id)
            ->where('transfer_type', 'out')
            ->first();

        if (! $transferOut) {
            return null;
        }

        $fromLocation = PhpposLocation::where('location_id', $transferOut->from_location_id)->first();
        $toLocation = PhpposLocation::where('location_id', $transferOut->to_location_id)->first();

        if (! $fromLocation || ! $toLocation) {
            return null;
        }

        $items = PhpposTransferItem::where('transfer_id', $transferOut->id)->get();
        $lines = $items->filter(function ($item) {
            return (float) $item->quantity > 0;
        })->map(function ($item) {
            $itemModel = $item->item_id ? PhpposItem::find($item->item_id) : null;

            // Kit header row — include full kit metadata and its components
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
        })->values()->toArray();

        return [
            'item_type' => 'transfer_out',
            'item_id' => $transferOut->id,
            'payload' => [
                'source_device_id' => $selfIp,
                'source_port' => $selfLocation ? (int) $selfLocation->port : null,
                'source_name' => $selfLocation ? $selfLocation->name : null,
                'transfer_out_id' => (string) $transferOut->id,
                'transfer_code' => $transferOut->internal_code ?? ('TRN-OUT-'.str_pad((string) $transferOut->id, 8, '0', STR_PAD_LEFT)),
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
                    'product_id'  => $compItem?->product_id,
                    'name'        => $compItem?->name ?? 'Item #'.$kitItem->item_id,
                    'quantity'    => (float) $kitItem->quantity,
                ];
            }
            foreach ($kitModel->nestedKits as $nested) {
                $components[] = $this->buildKitComponentPayload((int) $nested->item_kit_item_kit, (float) $nested->quantity);
            }
        }

        return [
            'type'                => 'kit',
            'item_kit_number'     => $kitModel?->item_kit_number,
            'item_kit_product_id' => $kitModel?->product_id,
            'name'                => $kitModel?->name ?? 'Kit #'.$kitId,
            'quantity'            => $quantity,
            'components'          => $components,
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

        Log::channel('queue_fail')->error('Transfer #'.$transfer->id.' failed: '.$error);

        try {
            Notification::create([
                'type' => 'queue_failed',
                'reference_type' => 'transfer',
                'reference_id' => $transfer->item_id,
                'title' => 'Transfer #'.$transfer->id.' failed',
                'body' => $error,
                'action_url' => '/lan/locations',
            ]);
        } catch (\Throwable) {
        }
    }
}
