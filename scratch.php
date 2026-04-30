<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
try {
    echo view('layouts.app')->render();
} catch (\Exception $e) {
    echo $e->getMessage();
}
