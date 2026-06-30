<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$modules = \Illuminate\Support\Facades\DB::table('phppos_modules')->get();
echo json_encode($modules, JSON_PRETTY_PRINT) . PHP_EOL;
