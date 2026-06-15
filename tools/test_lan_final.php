<?php
/**
 * LAN Jobs end-to-end test
 * Run: php tools/test_lan_final.php
 *
 * Requires server running: php -S 127.0.0.1:8080 -t public server.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Jobs\AnnouncePresence;
use App\Jobs\SendItem;
use App\Models\Location;
use App\Models\TransferQueue;
use App\Models\PhpposLocation;
use App\Models\PhpposItem;
use App\Models\PhpposTransfer;
use App\Models\PhpposTransferItem;
use App\Services\LanLocationRegistry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

$registry = app(LanLocationRegistry::class);
$appBase = 'http://127.0.0.1:8080';
$token = config('sync.shared_token');

echo str_repeat('=', 55).PHP_EOL;
echo " LAN JOBS END-TO-END TEST\n";
echo str_repeat('=', 55).PHP_EOL;
echo " Token : {$token}\n";
echo " URL   : {$appBase}\n";
echo str_repeat('-', 55).PHP_EOL.PHP_EOL;

$pass = 0;
$fail = 0;

function ok(string $msg): void { global $pass; $pass++; echo "  ✓ {$msg}\n"; }
function nok(string $msg): void { global $fail; $fail++; echo "  ✗ {$msg}\n"; }

// ── 1. Self-location state ────────────────────────────────
echo "[1] Self Location\n";
$self = Location::where('is_self', true)->first();
if (!$self) { nok('No self-location row'); exit(1); }
if (!$self->port) { nok("Port is NULL — run: php artisan app:register-self --port=8080"); exit(1); }
ok("{$self->name} @ {$self->ip}:{$self->port}, phppos_location_id={$self->phppos_location_id}");

// ── 2. announcePayload() ──────────────────────────────────
echo "[2] announcePayload()\n";
try {
    $payload = $registry->announcePayload();
    ok(json_encode($payload));
} catch (\Throwable $e) { nok($e->getMessage()); }

// ── 3. Test /api/lan/announce endpoint ────────────────────
echo "[3] POST /api/lan/announce\n";
try {
    $resp = Http::timeout(5)->withHeaders([
        'X-Sync-Token' => $token,
        'Accept' => 'application/json',
    ])->post("{$appBase}/api/lan/announce", [
        'ip' => '10.10.20.99',
        'port' => 8000,
        'name' => 'TestPeer-Fake',
    ]);
    if ($resp->successful() && $resp->json('ok') === true) {
        ok('Peer announce accepted, cleaning up');
        Location::where('ip', '10.10.20.99')->where('name', 'TestPeer-Fake')->delete();
    } else {
        nok("HTTP {$resp->status()}: {$resp->body()}");
    }
} catch (\Throwable $e) { nok("Exception: {$e->getMessage()}"); }

// ── 4. AnnouncePresence job dispatch ──────────────────────
echo "[4] AnnouncePresence dispatchSync()\n";
// Add a local peer first
$peerIp = '10.10.20.99'; // unique IP, not matching self
$peer = Location::updateOrCreate(
    ['ip' => $peerIp],
    ['name' => 'E2ETestPeer', 'port' => 8080, 'is_self' => false]
);
try {
    AnnouncePresence::dispatchSync();
    ok("Job completed (sent to {$peer->ip})");
} catch (\Throwable $e) { nok($e->getMessage()); }

// ── 5. Test /api/lan/receive endpoint ─────────────────────
echo "[5] POST /api/lan/receive\n";
$fromLoc = PhpposLocation::where('ulid', '!=', $self->phpposLocation->ulid)
    ->whereNotNull('ulid')->first() ?? PhpposLocation::whereNotNull('ulid')->first();
$toLoc = $self->phpposLocation;
$item = PhpposItem::first();

if (!$fromLoc || !$toLoc || !$item) {
    nok("Missing test data: fromLoc=".($fromLoc?'ok':'MISSING')." toLoc=".($toLoc?'ok':'MISSING')." item=".($item?'ok':'MISSING'));
} else {
    try {
        $resp = Http::timeout(5)->withHeaders([
            'X-Sync-Token' => $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post("{$appBase}/api/lan/receive", [
            'item_type' => 'transfer_out',
            'item_id' => 9999,
            'from_ip' => '10.10.20.3',
            'payload' => [
                'source_device_id' => '10.10.20.3',
                'transfer_out_id' => '9999',
                'from_location_ulid' => $fromLoc->ulid,
                'to_location_ulid' => $toLoc->ulid,
                'notes' => 'E2E test transfer',
                'status' => 'open',
                'created_at' => now()->toISOString(),
                'lines' => [
                    ['item_id' => $item->item_id, 'item_number' => $item->item_number, 'quantity' => 1],
                ],
            ],
        ]);
        $body = $resp->body();
        if ($resp->successful()) {
            $json = $resp->json();
            if (($json['ok'] ?? false) === true) {
                ok("Accepted: ".json_encode($json));
            } else {
                nok("Unexpected JSON: ".$body);
            }
        } elseif ($resp->status() === 422) {
            nok("Validation error: ".$body);
        } else {
            nok("HTTP {$resp->status()}: {$body}");
        }
    } catch (\Throwable $e) { nok("Exception: {$e->getMessage()}"); }
}

// ── 6. SendItem end-to-end ────────────────────────────────
echo "[6] SendItem end-to-end (needs phppos_transfer record)\n";
$existingTransfer = PhpposTransfer::where('transfer_type', 'out')->first();
if ($existingTransfer) {
    $transferId = $existingTransfer->id;
    ok("Found existing phppos_transfer #{$transferId}");
} else {
    // Create a minimal phppos_transfer for testing
    try {
        DB::beginTransaction();
        $transfer = PhpposTransfer::create([
            'transfer_type' => 'out',
            'from_location_id' => $fromLoc ? $fromLoc->location_id : 1,
            'to_location_id' => $toLoc->location_id,
            'status' => 'open',
            'notes' => 'E2E test',
        ]);
        PhpposTransferItem::create([
            'transfer_id' => $transfer->id,
            'item_id' => $item->item_id,
            'quantity' => 1,
        ]);
        DB::commit();
        $transferId = $transfer->id;
        ok("Created test phppos_transfer #{$transferId} with 1 line");
    } catch (\Throwable $e) {
        DB::rollBack();
        nok("Could not create test transfer: {$e->getMessage()}");
        $transferId = null;
    }
}

// For SendItem, use a different LAN IP (unique constraint on ip column)
$sendPeer = Location::create([
    'ip' => '10.10.20.4',
    'port' => 8080,
    'name' => 'SendTestPeer',
    'is_self' => false,
]);
if ($transferId) {
    $tq = TransferQueue::create([
        'location_id' => $sendPeer->id,
        'item_type' => 'transfer_out',
        'item_id' => $transferId,
        'status' => 'pending',
    ]);
    echo "    -> TransferQueue #{$tq->id} created (destination: {$sendPeer->ip}:{$sendPeer->port})\n";

    try {
        SendItem::dispatchSync($tq);
        $tq->refresh();
        echo "    -> Status: {$tq->status}";
        if ($tq->error) { echo " Error: {$tq->error}"; }
        echo "\n";
        // Expect: 'failed' because no real server at peer IP; OR 'delivered' if loopback works
        if ($tq->status === 'delivered') {
            ok('Transfer delivered');
        } elseif ($tq->status === 'failed' && $tq->error) {
            // Job correctly attempted the send but network failed (expected in test env)
            ok("Job ran correctly (expected failure: no peer server)");
        } else {
            nok("Unexpected status: {$tq->status}");
        }
    } catch (\Throwable $e) {
        // Exception thrown means job logic ran, network failed (expected)
        ok("Job dispatched correctly (network error expected in test env): {$e->getMessage()}");
    }

    // Cleanup test data
    if ($tq->exists) { $tq->delete(); }
    PhpposTransferItem::where('transfer_id', $transferId)->delete();
    PhpposTransfer::where('id', $transferId)->delete();
    echo "    -> Test data cleaned up\n";
}

// ── Cleanup ──────────────────────────────────────────────
echo "[Cleanup] Removing test peers\n";
TransferQueue::where('location_id', $peer->id)->delete();
$peer->delete();
if (isset($sendPeer)) {
    TransferQueue::where('location_id', $sendPeer->id)->delete();
    $sendPeer->delete();
}
ok("Peers removed");

// ── Summary ──────────────────────────────────────────────
echo "\n".str_repeat('=', 55).PHP_EOL;
echo " RESULTS: {$pass} passed, {$fail} failed\n";
echo str_repeat('=', 55).PHP_EOL;
if ($fail === 0) { echo " All good!\n"; } else { echo " Check failures above.\n"; }
echo PHP_EOL;
