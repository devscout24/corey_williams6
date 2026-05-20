<?php

namespace App\Jobs;

use App\Models\TransferQueue;
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
            // TODO: Load the item and build the payload based on item_type/item_id.
            $payload = [
                'item_type' => $transfer->item_type,
                'item_id' => $transfer->item_id,
                'payload' => [],
                'from_ip' => config('app.node_ip'),
            ];

            $response = Http::timeout(10)->post("http://{$location->ip}/api/lan/receive", $payload);

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
}
