<?php
/**
 * LAN Jobs test — standalone (no tinker dependency).
 * Run: php tools/test_lan_jobs_standalone.php
 *
 * Tests:
 *   1. Self-location state (port/IP)
 *   2. announcePayload() check
 *   3. /api/lan/announce endpoint (receiving side)
 *   4. /api/lan/receive endpoint (receiving side)
 *   5. TransferQueue pre-flight for SendItem
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Jobs\AnnouncePresence;
use App\Models\Location;
use App\Models\TransferQueue;
use App\Models\PhpposLocation;
use App\Models\PhpposItem;
use App\Services\LanLocationRegistry;
use Illuminate\Support\Facades\Http;

$registry = app(LanLocationRegistry::class);
$appBase = 'http://127.0.0.1:8080';
$token = config('sync.shared_token');

echo str_repeat('=', 55).PHP_EOL;
echo " LAN JOBS DIAGNOSTIC\n";
echo str_repeat('=', 55).PHP_EOL;
echo " Token     : {$token}\n";
echo " App base  : {$appBase}\n";
echo str_repeat('-', 55).PHP_EOL.PHP_EOL;

// ── 1. Self-location state ────────────────────────────────
echo "[1] Self Location in DB\n";
$self = Location::where('is_self', true)->first();
if (!$self) {
    echo "    ✗ No self-location row found. Run: php artisan app:register-self --ip=<LAN_IP> --port=8000\n\n";
} else {
    echo "    id    : {$self->id}\n";
    echo "    name  : {$self->name}\n";
    echo "    ip    : {$self->ip}\n";
    echo "    port  : ".($self->port ?? '⚠ NULL')."\n";
    echo "    phppos_location_id: ".($self->phppos_location_id ?? '⚠ NULL')."\n\n";
}

// ── 2. announcePayload() check ────────────────────────────
echo "[2] AnnouncePresence job — payload build check\n";
try {
    $payload = $registry->announcePayload();
    echo "    ✓ Payload: ".json_encode($payload)."\n\n";
} catch (\Throwable $e) {
    echo "    ✗ ".$e->getMessage()."\n\n";
}

// ── 3. Test /api/lan/announce endpoint ────────────────────
echo "[3] Testing /api/lan/announce endpoint (receiving end of AnnouncePresence)\n";

// Add a temporary peer on 127.0.0.1 so AnnouncePresence has somewhere to send
$peer = Location::updateOrCreate(
    ['ip' => '127.0.0.1'],
    [
        'name' => 'LocalTestPeer',
        'port' => 8000,
        'is_self' => false,
    ]
);
echo "    Added peer: {$peer->name} @ {$peer->ip}:{$peer->port}\n";

try {
    $resp = Http::timeout(5)
        ->withHeaders(['X-Sync-Token' => $token])
        ->post("{$appBase}/api/lan/announce", [
            'ip'   => '10.10.20.99',
            'port' => 8080,
            'name' => 'TestPeer-Fake',
        ]);
    $status = $resp->status();
    $body = $resp->body();
    if ($resp->successful()) {
        echo "    ✓ 200 OK — announce accepted. Response: {$body}\n";
        Location::where('ip', '10.10.20.99')->where('name', 'TestPeer-Fake')->delete();
        echo "    Cleaned up fake peer.\n";
    } else {
        echo "    ✗ HTTP {$status}: {$body}\n";
    }
} catch (\Throwable $e) {
    echo "    ✗ Exception: ".$e->getMessage()."\n";
    echo "    ⚠ Is the server running at {$appBase}?\n";
}
echo "\n";

// ── 3b. Actually dispatch AnnouncePresence job ────────────
echo "[3b] Dispatching AnnouncePresence job (sends to peer 127.0.0.1)\n";
try {
    AnnouncePresence::dispatchSync();
    echo "    ✓ AnnouncePresence dispatched and completed.\n";
} catch (\Throwable $e) {
    echo "    ✗ ".$e->getMessage()."\n";
}
echo "\n";

// ── 4. Test /api/lan/receive endpoint ─────────────────────
echo "[4] Testing /api/lan/receive endpoint (receiving end of SendItem)\n";
$fromLoc = PhpposLocation::whereNotNull('ulid')->orderBy('location_id')->first();
$toLoc = PhpposLocation::whereNotNull('ulid')->orderBy('location_id', 'desc')->first();
$item = PhpposItem::first();

if (!$fromLoc || !$toLoc || !$item) {
    echo "    ⚠ Skipped — need >=1 item and 2 locations with ulids.\n";
} else {
    echo "    from_location: {$fromLoc->location_id} ulid={$fromLoc->ulid}\n";
    echo "    to_location  : {$toLoc->location_id} ulid={$toLoc->ulid}\n";
    echo "    item         : {$item->item_id} ({$item->item_number})\n";
    try {
        $resp = Http::timeout(5)
            ->withHeaders(['X-Sync-Token' => $token])
            ->post("{$appBase}/api/lan/receive", [
                'item_type' => 'transfer_out',
                'item_id'   => 9999,
                'from_ip'   => '10.10.20.3',
                'payload'   => [
                    'source_device_id'  => '10.10.20.3',
                    'transfer_out_id'   => '9999',
                    'from_location_ulid'=> $fromLoc->ulid,
                    'to_location_ulid'  => $toLoc->ulid,
                    'notes'             => 'Test transfer from diagnostic script',
                    'status'            => 'open',
                    'created_at'        => now()->toISOString(),
                    'lines'             => [
                        ['item_id' => $item->item_id, 'item_number' => $item->item_number, 'quantity' => 1],
                    ],
                ],
            ]);
        $status = $resp->status();
        $body = $resp->body();
        if ($resp->successful()) {
            echo "    ✓ HTTP {$status} — receive accepted. Response: {$body}\n";
        } else {
            echo "    ✗ HTTP {$status}: {$body}\n";
        }
    } catch (\Throwable $e) {
        echo "    ✗ Exception: ".$e->getMessage()."\n";
    }
}
echo "\n";

// ── 5. SendItem pre-flight check ─────────────────────────
echo "[5] SendItem job — pre-flight check\n";
$pendingTransfers = TransferQueue::where('status', 'pending')->take(3)->get();
$failedTransfers = TransferQueue::where('status', 'failed')->take(3)->get();
$allTransfers = $pendingTransfers->merge($failedTransfers);

if ($allTransfers->isEmpty()) {
    echo "    (no pending/failed transfers — creating a test TransferQueue entry)\n";
    // Create a test transfer queue entry pointing to self (for loopback test)
    $testTransfer = TransferQueue::create([
        'location_id' => $peer->id,
        'item_type' => 'transfer_out',
        'item_id' => 9999,
        'status' => 'pending',
    ]);
    echo "    Created TransferQueue #{$testTransfer->id} (pending, points to local peer)\n\n";

    echo "[6] Dispatching SendItem for TransferQueue #{$testTransfer->id}\n";
    try {
        \App\Jobs\SendItem::dispatchSync($testTransfer);
        $testTransfer->refresh();
        echo "    ✓ SendItem completed. Status: {$testTransfer->status}\n";
        if ($testTransfer->error) {
            echo "    Error: {$testTransfer->error}\n";
        }
    } catch (\Throwable $e) {
        echo "    ✗ ".$e->getMessage()."\n";
    }
    echo "\n";
} else {
    foreach ($allTransfers as $t) {
        echo "    Transfer #{$t->id}: type={$t->item_type}, item_id={$t->item_id}, status={$t->status}\n";
        $dest = $t->destination;
        if (!$dest) {
            echo "      ✗ No destination location linked.\n";
        } elseif (!$dest->ip || !$dest->port) {
            echo "      ✗ Destination {$dest->name} missing ip/port.\n";
        } else {
            echo "      ✓ Destination: {$dest->name} -> {$dest->ip}:{$dest->port}\n";
        }
    }
    echo "\n";
}

// ── Cleanup peer ──────────────────────────────────────────
echo "[Cleanup] Removing test peer...\n";
$peer->delete();
echo "    Done.\n\n";

echo str_repeat('=', 55).PHP_EOL;
echo " Done.\n";
echo str_repeat('=', 55).PHP_EOL;
