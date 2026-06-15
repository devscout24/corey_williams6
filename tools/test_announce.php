<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$resp = Http::timeout(5)
    ->withHeaders(['X-Sync-Token' => 'test-token-123'])
    ->post('http://127.0.0.1:8080/api/lan/announce', [
        'ip' => '10.10.20.99',
        'port' => 8000,
        'name' => 'TestPeer-Fake',
    ]);
echo 'Status: '.$resp->status().PHP_EOL;
echo 'Body: '.$resp->body().PHP_EOL;
