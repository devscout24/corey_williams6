<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\PhpposLocation;
use App\Models\PhpposReceiving;

$fromLoc = PhpposLocation::where('location_id', 2)->first();
$toLoc = PhpposLocation::where('location_id', 1)->first();

// Step 1: Send a transfer via LAN receive
echo "Step 1: POST /api/lan/receive\n";
$payload = [
    'item_type' => 'transfer_out',
    'item_id' => 8888,
    'from_ip' => '10.10.20.3',
    'payload' => [
        'source_device_id' => '10.10.20.3',
        'transfer_out_id' => '8888',
        'from_location_ulid' => $fromLoc->ulid,
        'to_location_ulid' => $toLoc->ulid,
        'notes' => 'Test close flow',
        'status' => 'open',
        'created_at' => date('c'),
        'lines' => [
            ['item_id' => 1, 'item_number' => 'ITEM-001', 'quantity' => 3],
        ],
    ],
];

$resp = Http::timeout(5)
    ->asJson()
    ->withHeaders(['X-Sync-Token' => config('sync.shared_token')])
    ->post('http://127.0.0.1:8080/api/lan/receive', $payload);

echo 'Status: '.$resp->status().PHP_EOL;
echo 'Body: '.$resp->body().PHP_EOL;

if ($resp->successful()) {
    $json = $resp->json();
    $rid = $json['receiving_id'] ?? null;
    echo "Receiving ID: $rid\n";

    if ($rid) {
        $r = PhpposReceiving::with('items')->find($rid);
        echo '  Code: '.$r->internal_code.PHP_EOL;
        echo '  Mode: '.$r->mode.PHP_EOL;
        echo '  Source: '.$r->source.PHP_EOL;
        echo '  Closed at: '.($r->closed_at ?? 'null').PHP_EOL;
        echo '  Items: '.$r->items->count().PHP_EOL;

        // Step 2: Close it via the Laravel session (simulate button click)
        echo "\nStep 2: Close receiving #$rid\n";

        // Use the controller directly
        $controller = app(\App\Http\Controllers\ReceivingController::class);
        $inventoryFlowService = app(\App\Services\InventoryFlowService::class);

        // Check qty before
        $beforeQty = \Illuminate\Support\Facades\DB::table('phppos_location_items')
            ->where('location_id', 1)->where('item_id', 1)->value('quantity');
        echo '  Item 1 qty before close: '.($beforeQty ?? 0).PHP_EOL;

        try {
            $controller->closeTransferReceiving($rid, $inventoryFlowService);
            echo "  Close successful\n";

            $r->refresh();
            echo '  Closed at: '.($r->closed_at ?? 'STILL NULL!').PHP_EOL;
            echo '  Items qty_received: '.$r->items->first()->quantity_received.PHP_EOL;

            $afterQty = \Illuminate\Support\Facades\DB::table('phppos_location_items')
                ->where('location_id', 1)->where('item_id', 1)->value('quantity');
            echo '  Item 1 qty after close: '.($afterQty ?? 0).PHP_EOL;
            echo '  Delta: '.(($afterQty ?? 0) - ($beforeQty ?? 0)).PHP_EOL;
        } catch (\Throwable $e) {
            echo '  ERROR: '.$e->getMessage().PHP_EOL;
        }
    }
}
