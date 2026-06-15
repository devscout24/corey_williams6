<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Items (first 5) ===\n";
$items = App\Models\PhpposItem::limit(5)->get(['item_id', 'item_number', 'name', 'deleted']);
echo json_encode($items->toArray(), JSON_PRETTY_PRINT) . "\n\n";

echo "=== PhpposTransfers (first 5) ===\n";
$transfers = App\Models\PhpposTransfer::limit(5)->get(['id', 'transfer_type', 'from_location_id', 'to_location_id', 'status']);
echo json_encode($transfers->toArray(), JSON_PRETTY_PRINT) . "\n\n";

echo "=== TransferQueues (all) ===\n";
$queues = App\Models\TransferQueue::all();
echo json_encode($queues->toArray(), JSON_PRETTY_PRINT) . "\n\n";

echo "=== Current Location state ===\n";
echo json_encode(App\Models\Location::all()->toArray(), JSON_PRETTY_PRINT) . "\n";
