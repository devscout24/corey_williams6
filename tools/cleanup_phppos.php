<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PhpposLocation;

$deleted = PhpposLocation::where('name', 'TestPeer-Fake')
    ->orWhere('name', 'E2ETestPeer')
    ->orWhere('name', 'LoopbackPeer')
    ->orWhere('name', 'SendTestPeer')
    ->delete();

echo "Deleted {$deleted} fake phppos_locations.\n";
