<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$contextService = app(\App\Services\LocationContextService::class);
$locationId = $contextService->resolveLocationId(null);
echo 'Current location_id from context: '.var_export($locationId, true).PHP_EOL;

$currentLocation = \App\Models\PhpposLocation::where('location_id', $locationId)->first();
if ($currentLocation) {
    echo '  name: '.$currentLocation->name.PHP_EOL;
    echo '  ulid: '.$currentLocation->ulid.PHP_EOL;
} else {
    echo '  No phppos_location found for this ID'.PHP_EOL;
}

// Also check what happens if we pass null to resolveLocationId differently
echo PHP_EOL.'All phppos_locations:'.PHP_EOL;
$all = \App\Models\PhpposLocation::all(['location_id', 'name', 'ulid'])->toArray();
echo json_encode($all, JSON_PRETTY_PRINT).PHP_EOL;
