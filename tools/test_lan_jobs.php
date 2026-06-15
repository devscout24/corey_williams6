<?php
/**
 * LAN Jobs Test Script
 * Run via: php artisan tinker --execute="require base_path('tools/test_lan_jobs.php');"
 *
 * This script tests:
 *  1. The /api/lan/announce endpoint (receiving side of AnnouncePresence)
 *  2. AnnouncePresence job dispatch (sending side)
 *  3. The /api/lan/receive endpoint (receiving side of SendItem)
 *  4. SendItem job end-to-end pre-flight checks
 */

use App\Jobs\AnnouncePresence;
use App\Models\Location;
use App\Models\TransferQueue;
use App\Services\LanLocationRegistry;
use Illuminate\Support\Facades\Http;

$registry = app(LanLocationRegistry::class);
$appBase  = 'http://127.0.0.1:8020'; // change if different
$token    = config('sync.shared_token');

echo "\n=======================================================\n";
echo " LAN JOBS DIAGNOSTIC\n";
echo "=======================================================\n";
echo " Token     : {$token}\n";
echo " App base  : {$appBase}\n";
echo "-------------------------------------------------------\n\n";

// ── 1. Self-location state ────────────────────────────────
echo "[1] Self Location in DB\n";
$self = Location::where('is_self', true)->first();
if (!$self) {
    echo "    ✗ No self-location row found. Run Resync IP first.\n\n";
} else {
    echo "    id    : {$self->id}\n";
    echo "    name  : {$self->name}\n";
    echo "    ip    : {$self->ip}\n";
    echo "    port  : " . ($self->port ?? '⚠ NULL — will cause selfOrFail() to throw') . "\n";
    echo "    phppos_location_id: " . ($self->phppos_location_id ?? '⚠ NULL') . "\n\n";
}

// ── 2. Peer locations ─────────────────────────────────────
echo "[2] Peer Locations\n";
$peers = Location::where('is_self', false)->whereNotNull('ip')->whereNotNull('port')->get();
if ($peers->isEmpty()) {
    echo "    (none) — AnnouncePresence will not send to any peer.\n\n";
} else {
    foreach ($peers as $p) {
        echo "    → {$p->name}  {$p->ip}:{$p->port}\n";
    }
    echo "\n";
}

// ── 3. Test /api/lan/announce endpoint (receiving side) ───
echo "[3] Testing /api/lan/announce endpoint (receiving end of AnnouncePresence)\n";
try {
    $resp = Http::timeout(5)
        ->withHeaders(['X-Sync-Token' => $token])
        ->post("{$appBase}/api/lan/announce", [
            'ip'   => '10.10.20.99',
            'port' => 8000,
            'name' => 'TestPeer-Fake',
        ]);
    $status = $resp->status();
    $body   = $resp->body();
    if ($resp->successful()) {
        echo "    ✓ 200 OK — announce accepted. Response: {$body}\n";
        echo "    Cleaning up fake peer...\n";
        Location::where('ip', '10.10.20.99')->where('name', 'TestPeer-Fake')->delete();
    } else {
        echo "    ✗ HTTP {$status}: {$body}\n";
    }
} catch (\Throwable $e) {
    echo "    ✗ Exception: " . $e->getMessage() . "\n";
    echo "    ⚠ Is the server running at {$appBase}?\n";
}
echo "\n";

// ── 4. announcePayload() check ────────────────────────────
echo "[4] AnnouncePresence job — payload build check\n";
try {
    $payload = $registry->announcePayload();
    echo "    ✓ Payload built: " . json_encode($payload) . "\n";
} catch (\Throwable $e) {
    echo "    ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// ── 5. Test /api/lan/receive endpoint (receiving side of SendItem) ─
echo "[5] Testing /api/lan/receive endpoint (receiving end of SendItem)\n";
// Find a real from/to location ulid pair for the test
$fromLoc = \App\Models\PhpposLocation::whereNotNull('ulid')->orderBy('location_id')->first();
$toLoc   = \App\Models\PhpposLocation::whereNotNull('ulid')->orderBy('location_id', 'desc')->first();
$item    = \App\Models\PhpposItem::first();

if (!$fromLoc || !$toLoc || !$item) {
    echo "    ⚠ Skipped — need at least 1 item and 2 locations with ulids in DB.\n";
    echo "    from_location: " . ($fromLoc ? $fromLoc->ulid : 'MISSING') . "\n";
    echo "    to_location  : " . ($toLoc   ? $toLoc->ulid   : 'MISSING') . "\n";
    echo "    item         : " . ($item    ? $item->item_id  : 'MISSING') . "\n";
} else {
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
        $body   = $resp->body();
        if ($resp->successful()) {
            echo "    ✓ HTTP {$status} — receive accepted. Response: {$body}\n";
        } else {
            echo "    ✗ HTTP {$status}: {$body}\n";
        }
    } catch (\Throwable $e) {
        echo "    ✗ Exception: " . $e->getMessage() . "\n";
    }
}
echo "\n";

// ── 6. SendItem job pre-flight check ─────────────────────
echo "[6] SendItem job — pre-flight check\n";
$pendingTransfers = TransferQueue::where('status', 'pending')->take(3)->get();
$failedTransfers  = TransferQueue::where('status', 'failed')->take(3)->get();
$allTransfers     = $pendingTransfers->merge($failedTransfers);

if ($allTransfers->isEmpty()) {
    echo "    (no pending/failed transfers in queue — job cannot be tested end-to-end without one)\n";
} else {
    foreach ($allTransfers as $t) {
        echo "    Transfer #{$t->id}: type={$t->item_type}, item_id={$t->item_id}, status={$t->status}\n";
        $dest = $t->destination;
        if (!$dest) {
            echo "      ✗ No destination location linked.\n";
        } elseif (!$dest->ip || !$dest->port) {
            echo "      ✗ Destination {$dest->name} missing ip/port.\n";
        } else {
            echo "      ✓ Destination: {$dest->name} → {$dest->ip}:{$dest->port}\n";
        }
    }
}
echo "\n";

echo "=======================================================\n";
echo " Done. Fix ✗ items above before dispatching jobs.\n";
echo "=======================================================\n\n";
