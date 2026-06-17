<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Locations ===\n";
echo json_encode(App\Models\Location::all()->toArray(), JSON_PRETTY_PRINT) . "\n\n";

echo "=== PhpposLocations (not deleted) ===\n";
$locs = App\Models\PhpposLocation::where('deleted', 0)->get(['location_id', 'name', 'slug', 'ulid']);
echo json_encode($locs->toArray(), JSON_PRETTY_PRINT) . "\n\n";

echo "=== Config ===\n";
echo "APP_URL: " . config('app.url') . "\n";
echo "APP_NODE_IP: " . config('app.node_ip') . "\n";
echo "APP_NODE_PORT: " . config('app.node_port') . "\n";
echo "APP_NODE_NAME: " . config('app.node_name') . "\n";
echo "sync.shared_token: " . config('sync.shared_token') . "\n";
echo "sync.device_id: " . config('sync.device_id') . "\n";
