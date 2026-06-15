<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ch = curl_init('http://127.0.0.1:8080/api/lan/receive');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'item_type' => 'transfer_out',
    'item_id' => 9999,
    'from_ip' => '10.10.20.3',
    'payload' => [
        'source_device_id' => '10.10.20.3',
        'transfer_out_id' => '9999',
        'from_location_ulid' => 'test',
        'to_location_ulid' => 'test',
        'notes' => 'Test',
        'status' => 'open',
        'created_at' => date('c'),
        'lines' => [['item_id' => 1, 'item_number' => 'TEST', 'quantity' => 1]],
    ],
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Sync-Token: test-token-123',
]);
$body = curl_exec($ch);
$info = curl_getinfo($ch);
echo 'Status: '.$info['http_code'].PHP_EOL;
echo 'Content-Type: '.($info['content_type'] ?? 'N/A').PHP_EOL;
echo 'Body (first 300): '.substr($body, 0, 300).PHP_EOL;
curl_close($ch);
