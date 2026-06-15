<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Location;
use App\Models\PhpposLocation;
use App\Models\TransferQueue;
use App\Models\PhpposTransferItem;
use App\Models\PhpposTransfer;

// Cleanup fake phppos_locations
PhpposLocation::where('name', 'TestPeer-Fake')
    ->orWhere('name', 'E2ETestPeer')
    ->orWhere('name', 'LoopbackPeer')
    ->orWhere('name', 'SendTestPeer')
    ->delete();

// Delete test transfer queues first (FK to locations)
TransferQueue::truncate();

// Delete test transfer items first (FK to transfers)
$testTransferIds = PhpposTransfer::where('notes', 'E2E test')
    ->orWhere('notes', 'E2E test transfer')
    ->pluck('id');
PhpposTransferItem::whereIn('transfer_id', $testTransferIds)->delete();
PhpposTransfer::whereIn('id', $testTransferIds)->delete();

// Delete old location entries except the current self
$self = Location::where('is_self', true)->first();
Location::where('id', '!=', $self?->id)->delete();

echo 'Cleaned up test data.'.PHP_EOL;
echo 'Self location: '.($self ? "{$self->name} @ {$self->ip}:{$self->port}" : 'NONE').PHP_EOL;
