<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\PhpposLocation;
use App\Models\PhpposItem;

$token = config('sync.shared_token');
$fromLoc = PhpposLocation::whereNotNull('ulid')->orderBy('location_id')->first();
$toLoc = PhpposLocation::whereNotNull('ulid')->orderBy('location_id', 'desc')->first();
$item = PhpposItem::first();

echo 'fromLoc: '.$fromLoc->ulid.PHP_EOL;
echo 'toLoc: '.$toLoc->ulid.PHP_EOL;
echo 'item: '.$item->item_id.PHP_EOL;

$resp = Http::timeout(5)
    ->withHeaders(['X-Sync-Token' => $token])
    ->post('http://127.0.0.1:8080/api/lan/receive', [
        'item_type' => 'transfer_out',
        'item_id'   => 9999,
        'from_ip'   => '10.10.20.3',
        'payload'   => [
            'source_device_id'  => '10.10.20.3',
            'transfer_out_id'   => '9999',
            'from_location_ulid'=> $fromLoc->ulid,
            'to_location_ulid'  => $toLoc->ulid,
            'notes'             => 'Test transfer',
            'status'            => 'open',
            'created_at'        => date('c'),
            'lines'             => [
                ['item_id' => $item->item_id, 'item_number' => $item->item_number, 'quantity' => 1],
            ],
        ],
    ]);
echo 'Status: '.$resp->status().PHP_EOL;
echo 'Body (truncated): '.substr($resp->body(), 0, 500).PHP_EOL;
