<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Str;

$locs = App\Models\PhpposLocation::whereNull('ulid')->get();
foreach ($locs as $loc) {
    $loc->ulid = (string) Str::ulid();
    $loc->save();
    echo 'Set ulid for location_id ' . $loc->location_id . ': ' . $loc->ulid . PHP_EOL;
}
echo 'Done.' . PHP_EOL;
