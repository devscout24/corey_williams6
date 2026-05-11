<?php
$html = file_get_contents('resources/views/reports/listing.blade.php');
preg_match_all("/route\\('reports\\.generate', '([^']+)'\\)/", $html, $matches_blade);
$blade_reports = array_unique($matches_blade[1]);

$php = file_get_contents('app/Http/Controllers/ReportController.php');
preg_match_all("/case '([^']+)':/", $php, $matches_php);
$php_reports = array_unique($matches_php[1]);

$missing = array_diff($blade_reports, $php_reports);
print_r(array_values($missing));
