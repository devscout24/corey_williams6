<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\TransferQueue;
use App\Models\Location;

echo "=== TransferQueue ===\n";
$all = TransferQueue::with('destination')->latest()->get();
if ($all->isEmpty()) {
    echo "(empty)\n";
} else {
    foreach ($all as $t) {
        $dest = $t->destination;
        $destStr = $dest ? "{$dest->name} ({$dest->ip}:{$dest->port})" : 'N/A';
        echo "  #{$t->id} type={$t->item_type} item_id={$t->item_id} status={$t->status} error=".($t->error ?? 'none')." dest={$destStr} created={$t->created_at}\n";
    }
}

echo "\n=== Locations ===\n";
$locs = Location::all();
foreach ($locs as $l) {
    echo "  #{$l->id} {$l->name} {$l->ip}:{$l->port} self=".($l->is_self?'yes':'no')." last_seen={$l->last_seen_at}\n";
}
