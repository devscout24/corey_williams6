<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\PhpposLocation;
use App\Models\PhpposReceiving;
use App\Models\PhpposItem;

$fromLoc = PhpposLocation::where('location_id', 2)->first();
$toLoc = PhpposLocation::where('location_id', 1)->first();

$payload = [
    'item_type' => 'transfer_out',
    'item_id' => 9999,
    'from_ip' => '10.10.20.3',
    'payload' => [
        'source_device_id' => '10.10.20.3',
        'transfer_out_id' => '9999',
        'from_location_ulid' => $fromLoc->ulid,
        'to_location_ulid' => $toLoc->ulid,
        'notes' => 'Test transfer via file',
        'status' => 'open',
        'created_at' => date('c'),
        'lines' => [
            ['item_id' => 1, 'item_number' => 'ITEM-001', 'quantity' => 5],
        ],
    ],
];

echo 'Sending to http://127.0.0.1:8080/api/lan/receive'.PHP_EOL;

$resp = Http::timeout(5)
    ->asJson()
    ->withHeaders(['X-Sync-Token' => config('sync.shared_token')])
    ->post('http://127.0.0.1:8080/api/lan/receive', $payload);

echo 'Status: '.$resp->status().PHP_EOL;
echo 'Body: '.$resp->body().PHP_EOL;

if ($resp->successful()) {
    $json = $resp->json();
    if (isset($json['receiving_id'])) {
        $r = PhpposReceiving::find($json['receiving_id']);
        echo 'Created receiving: #'.$r->receiving_id.' '.$r->internal_code.' mode='.$r->mode.' source='.$r->source.' closed_at='.($r->closed_at ?? 'null').PHP_EOL;
        echo 'Items: '.$r->items()->count().PHP_EOL;
    }
}
