<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\View;

$outputTaxData = [
    'standard' => [
        'total_incl_vat' => 230.00,
        'vat_amount' => 30.00,
    ],
    'zero_rated' => [
        'total_incl_vat' => 100.00,
        'vat_amount' => 0.00,
    ],
    'exempt' => [
        'total_incl_vat' => 50.00,
        'vat_amount' => 0.00,
    ]
];

$inputTaxData = [
    'imports' => [
        'total_excl_vat' => 1000.00,
        'vat_amount' => 150.00,
    ],
    'domestic' => [
        'total_excl_vat' => 700.00,
        'vat_amount' => 105.00,
    ]
];

$title = 'VAT Report (Output & Input Tax)';
$startDate = '2026-05-01';
$endDate = '2026-05-31';
$report = 'output_tax';

try {
    $html = view('reports.output_tax', compact(
        'outputTaxData', 'inputTaxData', 'title', 'startDate', 'endDate', 'report'
    ))->render();
    
    echo "SUCCESS: Blade rendered successfully!\n";
    echo "Length of rendered HTML: " . strlen($html) . " characters\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
