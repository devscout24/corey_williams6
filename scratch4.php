<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$employee = \Illuminate\Support\Facades\DB::table('phppos_employees')->first();
if ($employee) {
    auth('employee')->loginUsingId($employee->id);
}

$request = Illuminate\Http\Request::create('/attributes', 'GET');
$response = $kernel->handle($request);
echo "STATUS: " . $response->getStatusCode() . "\n";
if ($response->getStatusCode() >= 400) {
    echo $response->getContent();
} else {
    echo "SUCCESS\n";
}
$kernel->terminate($request, $response);
