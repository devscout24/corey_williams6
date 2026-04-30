<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/attributes', 'GET');
$response = $kernel->handle($request);
echo "STATUS: " . $response->getStatusCode() . "\n";
echo substr($response->getContent(), 0, 500); // only print first 500 chars to see if error
$kernel->terminate($request, $response);
