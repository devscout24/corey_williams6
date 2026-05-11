<?php
$cases = <<<'PHP'
            case 'graphical_summary_suppliers':
            case 'summary_suppliers':
                $query = DB::table('phppos_sales_items')
                    ->join('phppos_sales', 'phppos_sales_items.sale_id', '=', 'phppos_sales.sale_id')
                    ->join('phppos_items', 'phppos_sales_items.item_id', '=', 'phppos_items.item_id')
                    ->join('phppos_suppliers', 'phppos_items.supplier_id', '=', 'phppos_suppliers.id')
                    ->selectRaw('phppos_suppliers.company_name as supplier, SUM(phppos_sales_items.line_total) as total, SUM(phppos_sales_items.subtotal) as subtotal, SUM(phppos_sales_items.tax) as tax, SUM(phppos_sales_items.profit) as profit')
                    ->where('phppos_sales.deleted', 0);

                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $data = $query->groupBy('phppos_items.supplier_id', 'phppos_suppliers.company_name')->orderBy('total', 'desc')->get();

                if (str_starts_with($report, 'graphical_')) {
                    $chartData = ['labels' => $data->pluck('supplier')->toArray(), 'values' => $data->pluck('total')->toArray()];
                    $summary = ['Total Sales' => $data->sum('total'), 'Top Supplier' => $data->first() ? $data->first()->supplier : 'N/A'];
                    $title = "Graphical Summary Suppliers";
                    $chartType = 'pie';
                    return view('reports.graphical', compact('chartData', 'summary', 'title', 'startDate', 'endDate', 'report', 'chartType'));
                }
                
                $headers = ['Supplier', 'Subtotal', 'Total', 'Tax', 'Profit'];
                $title = "Summary Suppliers Report";
                break;

            case 'specific_supplier':
                $supplierId = request('supplier_id');
                $query = DB::table('phppos_sales_items')
                    ->join('phppos_sales', 'phppos_sales_items.sale_id', '=', 'phppos_sales.sale_id')
                    ->join('phppos_items', 'phppos_sales_items.item_id', '=', 'phppos_items.item_id')
                    ->selectRaw('phppos_sales.sale_id, phppos_sales.created_at as sale_date, phppos_items.name as item_name, phppos_sales_items.line_total as total')
                    ->where('phppos_sales.deleted', 0)
                    ->where('phppos_items.supplier_id', $supplierId);

                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $data = $query->orderBy('sale_date', 'desc')->get();

                $headers = ['Sale ID', 'Date', 'Item Name', 'Total'];
                $title = "Detailed Supplier Report";
                break;

            case 'graphical_summary_tags':
            case 'summary_tags':
                $query = DB::table('phppos_sales_items')
                    ->join('phppos_sales', 'phppos_sales_items.sale_id', '=', 'phppos_sales.sale_id')
                    ->join('phppos_items_tags', 'phppos_sales_items.item_id', '=', 'phppos_items_tags.item_id')
                    ->join('phppos_tags', 'phppos_items_tags.tag_id', '=', 'phppos_tags.id')
                    ->selectRaw('phppos_tags.name as tag, SUM(phppos_sales_items.line_total) as total, SUM(phppos_sales_items.subtotal) as subtotal, SUM(phppos_sales_items.tax) as tax, SUM(phppos_sales_items.profit) as profit')
                    ->where('phppos_sales.deleted', 0);

                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $data = $query->groupBy('phppos_tags.id', 'phppos_tags.name')->orderBy('total', 'desc')->get();

                if (str_starts_with($report, 'graphical_')) {
                    $chartData = ['labels' => $data->pluck('tag')->toArray(), 'values' => $data->pluck('total')->toArray()];
                    $summary = ['Total Sales' => $data->sum('total'), 'Top Tag' => $data->first() ? $data->first()->tag : 'N/A'];
                    $title = "Graphical Summary Tags";
                    $chartType = 'pie';
                    return view('reports.graphical', compact('chartData', 'summary', 'title', 'startDate', 'endDate', 'report', 'chartType'));
                }
                
                $headers = ['Tag', 'Subtotal', 'Total', 'Tax', 'Profit'];
                $title = "Summary Tags Report";
                break;

            case 'summary_taxes':
                $query = DB::table('phppos_sales')
                    ->selectRaw('SUM(phppos_sales.tax) as tax, SUM(phppos_sales.subtotal) as subtotal, SUM(phppos_sales.total) as total')
                    ->where('phppos_sales.deleted', 0);

                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $data = $query->get();

                $headers = ['Tax', 'Subtotal', 'Total'];
                $title = "Summary Taxes Report";
                break;

            case 'graphical_summary_registers':
            case 'summary_registers':
                $query = DB::table('phppos_sales')
                    ->leftJoin('phppos_registers', 'phppos_sales.register_id', '=', 'phppos_registers.register_id')
                    ->selectRaw('COALESCE(phppos_registers.name, "Unknown") as register_name, SUM(phppos_sales.total) as total')
                    ->where('phppos_sales.deleted', 0);

                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $data = $query->groupBy('phppos_sales.register_id', 'phppos_registers.name')->orderBy('total', 'desc')->get();

                if (str_starts_with($report, 'graphical_')) {
                    $chartData = ['labels' => $data->pluck('register_name')->toArray(), 'values' => $data->pluck('total')->toArray()];
                    $summary = ['Total Sales' => $data->sum('total'), 'Top Register' => $data->first() ? $data->first()->register_name : 'N/A'];
                    $title = "Graphical Summary Registers";
                    $chartType = 'pie';
                    return view('reports.graphical', compact('chartData', 'summary', 'title', 'startDate', 'endDate', 'report', 'chartType'));
                }

                $headers = ['Register', 'Total'];
                $title = "Summary Registers Report";
                break;

            case 'summary_profit_and_loss':
                $salesQuery = DB::table('phppos_sales')
                    ->where('deleted', 0)
                    ->whereBetween('created_at', [$startDateTime, $endDateTime]);
                if ($locationId !== 'all') $salesQuery->where('location_id', $locationId);
                $totalSales = $salesQuery->sum('total');
                $totalProfit = $salesQuery->sum('profit');
                $totalTax = $salesQuery->sum('tax');

                $data = collect([
                    (object)['category' => 'Sales', 'amount' => $totalSales],
                    (object)['category' => 'Tax', 'amount' => $totalTax],
                    (object)['category' => 'Profit', 'amount' => $totalProfit]
                ]);

                $headers = ['Category', 'Amount'];
                $title = "Summary Profit and Loss";
                break;

PHP;

// Now for all other missing reports, we just add a "Under Construction" stub so they don't crash
$missing = ['sales_generator', 'summary_appointments', 'detailed_appointments', 'closeout', 'closeout_condensed', 'detailed_profit_and_loss', 'transfers', 'detailed_suspended_receivings', 'deleted_receivings', 'summary_taxes_receivings', 'graphical_summary_taxes_receivings', 'cheapest_supplier', 'graphical_summary_items_receivings', 'summary_items_receivings', 'receivings_graphical_summary_payments', 'receivings_summary_payments', 'receivings_detailed_payments', 'detailed_register_log', 'store_account_statements', 'summary_store_accounts', 'specific_customer_store_account', 'store_account_activity', 'store_account_activity_summary', 'store_account_outstanding', 'supplier_store_account_statements', 'supplier_summary_store_accounts', 'supplier_specific_store_account', 'supplier_store_account_activity', 'supplier_store_account_activity_summary', 'supplier_store_account_outstanding', 'specific_supplier_summary', 'graphical_summary_suppliers_receivings', 'summary_suppliers_receivings', 'specific_supplier_receivings', 'layaway_statements', 'summary_tiers', 'time_off', 'summary_timeclock', 'detailed_timeclock'];

foreach($missing as $m) {
    $title = ucwords(str_replace('_', ' ', $m));
    $cases .= "
            case '$m':
                \$data = collect([]);
                \$headers = ['Notice'];
                \$title = '$title Report (Under Construction)';
                break;
";
}

$controller_path = 'app/Http/Controllers/ReportController.php';
$content = file_get_contents($controller_path);
$content = str_replace("default:\n                return redirect()->back()->with('error', 'Report type not implemented yet.');", $cases . "\n            default:\n                return redirect()->back()->with('error', 'Report type not implemented yet.');", $content);
file_put_contents($controller_path, $content);

echo "Done\n";
