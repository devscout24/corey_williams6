<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.listing');
    }

    public function generate(string $report): View
    {
        $title = str_replace('_', ' ', ucfirst($report));
        $locations = DB::table('phppos_locations')->get();
        $paymentTypes = ['Cash', 'Check', 'Debit Card', 'Credit Card', 'Gift Card', 'Store Account'];
        
        return view('reports.generate', compact('report', 'title', 'locations', 'paymentTypes'));
    }

    public function store(Request $request, string $report)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime = $endDate . ' 23:59:59';

        $saleType = $request->input('sale_type', 'all');
        $paymentType = $request->input('payment_type', 'all');
        $locationId = $request->input('location_id', 'all');

        $groupBy = $request->input('group_by', 'day');

        $applySalesFilters = function ($query) use ($saleType, $paymentType, $locationId, $startDateTime, $endDateTime) {
            $query->whereBetween('created_at', [$startDateTime, $endDateTime]);
            if ($locationId !== 'all') $query->where('location_id', $locationId);
            if ($saleType !== 'all') $query->where('sale_type', $saleType === 'sales' ? 'sale' : 'return');
            if ($paymentType !== 'all') $query->where('payment_type', $paymentType);
            return $query;
        };

        switch ($report) {
            case 'summary_sales':
                $select = match($groupBy) {
                    'week' => 'YEARWEEK(created_at) as group_key, MIN(DATE(created_at)) as sale_date',
                    'month' => 'DATE_FORMAT(created_at, "%Y-%m") as group_key, MIN(DATE(created_at)) as sale_date',
                    'year' => 'YEAR(created_at) as group_key, MIN(DATE(created_at)) as sale_date',
                    default => 'DATE(created_at) as group_key, DATE(created_at) as sale_date',
                };
                
                $query = DB::table('phppos_sales')
                    ->selectRaw($select . ', SUM(subtotal) as subtotal, SUM(total) as total, SUM(tax) as tax, SUM(profit) as profit, COUNT(*) as sales_count')
                    ->where('deleted', 0);
                $data = $applySalesFilters($query)
                    ->groupBy('group_key')
                    ->orderBy('sale_date', 'desc')
                    ->get();

                $headers = ['Date', 'Sales Count', 'Subtotal', 'Tax', 'Total', 'Profit'];
                $title = "Summary Sales Report (" . ucfirst($groupBy) . ")";
                break;

            case 'graphical_summary_sales':
                $select = match($groupBy) {
                    'week' => 'YEARWEEK(created_at) as group_key, MIN(DATE(created_at)) as sale_date',
                    'month' => 'DATE_FORMAT(created_at, "%Y-%m") as group_key, MIN(DATE(created_at)) as sale_date',
                    'year' => 'YEAR(created_at) as group_key, MIN(DATE(created_at)) as sale_date',
                    default => 'DATE(created_at) as group_key, DATE(created_at) as sale_date',
                };
                
                $query = DB::table('phppos_sales')
                    ->selectRaw($select . ', SUM(total) as total, SUM(profit) as profit, COUNT(*) as count')
                    ->where('deleted', 0);
                $rawData = $applySalesFilters($query)
                    ->groupBy('group_key')
                    ->orderBy('sale_date', 'asc')
                    ->get();

                $chartData = [
                    'labels' => $rawData->pluck('sale_date')->toArray(),
                    'values' => $rawData->pluck('total')->toArray(),
                ];
                $summary = [
                    'Total Sales' => $rawData->sum('total'),
                    'Total Profit' => $rawData->sum('profit'),
                    'Total Transactions' => $rawData->sum('count'),
                ];
                $title = "Graphical Summary Sales (" . ucfirst($groupBy) . ")";
                $chartType = 'line';
                return view('reports.graphical', compact('chartData', 'summary', 'title', 'startDate', 'endDate', 'report', 'chartType'));

            case 'graphical_summary_sales_time':
                $query = DB::table('phppos_sales')
                    ->selectRaw('HOUR(created_at) as hour, SUM(total) as total, COUNT(*) as count')
                    ->where('deleted', 0);
                $rawData = $applySalesFilters($query)
                    ->groupBy('hour')
                    ->orderBy('hour', 'asc')
                    ->get();

                $labels = [];
                $values = array_fill(0, 24, 0);
                for($i=0; $i<24; $i++) {
                    $labels[] = $i . ':00';
                    $match = $rawData->firstWhere('hour', $i);
                    if ($match) $values[$i] = $match->total;
                }

                $chartData = [
                    'labels' => $labels,
                    'values' => $values,
                ];
                $summary = [
                    'Total Sales' => $rawData->sum('total'),
                    'Busiest Hour' => ($rawData->sortByDesc('count')->first()->hour ?? 'N/A') . ':00',
                    'Total Transactions' => $rawData->sum('count'),
                ];
                $title = "Graphical Sales by Time";
                $chartType = 'bar';
                return view('reports.graphical', compact('chartData', 'summary', 'title', 'startDate', 'endDate', 'report', 'chartType'));

            case 'detailed_sales':
                $query = DB::table('phppos_sales')
                    ->select('sale_id', 'created_at', 'customer_name', 'subtotal', 'total', 'tax', 'profit', 'payment_type')
                    ->where('deleted', 0);
                $data = $applySalesFilters($query)
                    ->orderBy('created_at', 'desc')
                    ->get();

                $headers = ['Sale ID', 'Date', 'Customer', 'Subtotal', 'Tax', 'Total', 'Profit', 'Payment'];
                $title = "Detailed Sales Report";
                break;

            case 'summary_sales_time':
                $query = DB::table('phppos_sales')
                    ->selectRaw('HOUR(created_at) as hour, COUNT(*) as transactions, SUM(subtotal) as subtotal, SUM(total) as total, SUM(tax) as tax, SUM(profit) as profit')
                    ->where('deleted', 0);
                $data = $applySalesFilters($query)
                    ->groupBy('hour')
                    ->orderBy('hour')
                    ->get();

                $headers = ['Hour', 'Transactions', 'Subtotal', 'Tax', 'Total', 'Profit'];
                $title = "Sales by Time Report";
                break;

            case 'summary_sales_day_of_week':
                $data = DB::table('phppos_sales')
                    ->selectRaw('DAYNAME(created_at) as day_of_week, COUNT(*) as transactions, SUM(subtotal) as subtotal, SUM(total) as total, SUM(tax) as tax')
                    ->whereBetween('created_at', [$startDateTime, $endDateTime])
                    ->where('deleted', 0)
                    ->groupBy('day_of_week')
                    ->orderByRaw("FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')")
                    ->get();

                $headers = ['Day of Week', 'Transactions', 'Subtotal', 'Total', 'Tax'];
                $title = "Sales by Day of Week Report";
                break;

            case 'summary_journal':
                $data = DB::table('phppos_sales')
                    ->selectRaw('DATE(created_at) as date, SUM(subtotal) as subtotal, SUM(tax) as tax, SUM(total) as total')
                    ->whereBetween('created_at', [$startDateTime, $endDateTime])
                    ->where('deleted', 0)
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->get();

                $headers = ['Date', 'Subtotal', 'Tax', 'Total'];
                $title = "Summary Journal Report";
                break;

            case 'deleted_sales':
                $data = DB::table('phppos_sales')
                    ->select('sale_id', 'created_at', 'customer_name', 'total', 'comment')
                    ->whereBetween('created_at', [$startDateTime, $endDateTime])
                    ->where('deleted', 1)
                    ->orderBy('created_at', 'desc')
                    ->get();

                $headers = ['Sale ID', 'Date', 'Customer', 'Total', 'Comment'];
                $title = "Deleted Sales Report";
                break;

            case 'summary_sales_locations':
                $query = DB::table('phppos_sales')
                    ->join('phppos_locations', 'phppos_sales.location_id', '=', 'phppos_locations.location_id')
                    ->selectRaw('phppos_locations.name as location, COUNT(*) as sales_count, SUM(subtotal) as subtotal, SUM(tax) as tax, SUM(total) as total, SUM(profit) as profit')
                    ->where('phppos_sales.deleted', 0);
                
                // Manual filters since we have a join and might have ambiguous columns
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);
                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                if ($saleType !== 'all') $query->where('phppos_sales.sale_type', $saleType === 'sales' ? 'sale' : 'return');
                if ($paymentType !== 'all') $query->where('phppos_sales.payment_type', $paymentType);

                $data = $query->groupBy('phppos_locations.location_id', 'phppos_locations.name')
                    ->get();

                $headers = ['Location', 'Sales Count', 'Subtotal', 'Tax', 'Total', 'Profit'];
                $title = "Sales by Location Report";
                break;

            case 'summary_tips':
                $data = DB::table('phppos_sales')
                    ->selectRaw('employee_id, SUM(tip) as total_tips')
                    ->whereBetween('created_at', [$startDateTime, $endDateTime])
                    ->where('deleted', 0)
                    ->where('tip', '>', 0)
                    ->groupBy('employee_id')
                    ->get();

                $headers = ['Employee ID', 'Total Tips'];
                $title = "Summary Tips Report";
                break;

            case 'detailed_ecommerce_sales':
                $data = DB::table('phppos_sales')
                    ->select('sale_id', 'created_at', 'total', 'payment_type')
                    ->whereBetween('created_at', [$startDateTime, $endDateTime])
                    ->where('deleted', 0)
                    ->whereNotNull('ecommerce_order_id')
                    ->orderBy('created_at', 'desc')
                    ->get();

                $headers = ['Sale ID', 'Date', 'Total', 'Payment'];
                $title = "Detailed Ecommerce Sales Report";
                break;

            case 'detailed_last_4_cc':
                $data = DB::table('phppos_sales')
                    ->select('sale_id', 'created_at', 'total', 'cc_ref_no')
                    ->whereBetween('created_at', [$startDateTime, $endDateTime])
                    ->where('deleted', 0)
                    ->whereNotNull('cc_ref_no')
                    ->orderBy('created_at', 'desc')
                    ->get();

                $headers = ['Sale ID', 'Date', 'Total', 'CC Ref No'];
                $title = "Detailed Last 4 CC Report";
                break;

            case 'voided_transactions':
                $data = DB::table('phppos_sales')
                    ->select('sale_id', 'created_at', 'total', 'comment')
                    ->whereBetween('created_at', [$startDateTime, $endDateTime])
                    ->where('deleted', 1)
                    ->orderBy('created_at', 'desc')
                    ->get();

                $headers = ['Sale ID', 'Date', 'Total', 'Reason'];
                $title = "Voided Transactions Report";
                break;

            case 'summary_discounts':
                $data = DB::table('phppos_sales_items')
                    ->selectRaw('discount_percent, COUNT(*) as count, SUM(subtotal * discount_percent / 100) as total_discount')
                    ->whereBetween('created_at', [$startDateTime, $endDateTime])
                    ->where('discount_percent', '>', 0)
                    ->groupBy('discount_percent')
                    ->orderBy('discount_percent', 'desc')
                    ->get();

                $headers = ['Discount %', 'Count', 'Total Discount Amount'];
                $title = "Summary Discounts Report";
                break;

            case 'detailed_suspended_sales':
                $query = DB::table('phppos_sales')
                    ->select('sale_id', 'created_at', 'customer_name', 'subtotal', 'total', 'tax', 'payment_type', 'comment')
                    ->where('deleted', 0)
                    ->where('suspended', '>', 0);
                $data = $applySalesFilters($query)
                    ->orderBy('created_at', 'desc')
                    ->get();

                $headers = ['Sale ID', 'Date', 'Customer', 'Subtotal', 'Total', 'Tax', 'Payment', 'Comment'];
                $title = "Detailed Suspended Sales Report";
                break;

            case 'summary_items':
                $query = DB::table('phppos_sales_items')
                    ->join('phppos_sales', 'phppos_sales_items.sale_id', '=', 'phppos_sales.sale_id')
                    ->join('phppos_items', 'phppos_sales_items.item_id', '=', 'phppos_items.item_id')
                    ->selectRaw('phppos_items.name, SUM(phppos_sales_items.quantity_purchased) as qty_sold, SUM(phppos_sales_items.subtotal) as subtotal, SUM(phppos_sales_items.tax) as tax, SUM(phppos_sales_items.line_total) as total, SUM(phppos_sales_items.profit) as profit')
                    ->where('phppos_sales.deleted', 0);

                // Use a custom version of the filter for items since it's joined
                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                if ($saleType !== 'all') $query->where('phppos_sales.sale_type', $saleType === 'sales' ? 'sale' : 'return');
                if ($paymentType !== 'all') $query->where('phppos_sales.payment_type', $paymentType);
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $data = $query->groupBy('phppos_items.item_id', 'phppos_items.name')
                    ->orderBy('total', 'desc')
                    ->get();

                $headers = ['Item Name', 'Qty Sold', 'Subtotal', 'Tax', 'Total', 'Profit'];
                $title = "Summary Items Report";
                break;

            case 'summary_customers':
                $query = DB::table('phppos_sales')
                    ->selectRaw('COALESCE(customer_name, "Walk-in") as customer, COUNT(*) as sales_count, SUM(subtotal) as subtotal, SUM(tax) as tax, SUM(total) as total, SUM(profit) as profit')
                    ->where('deleted', 0);
                $data = $applySalesFilters($query)
                    ->groupBy('customer_id', 'customer_name')
                    ->orderBy('total', 'desc')
                    ->get();

                $headers = ['Customer', 'Sales Count', 'Subtotal', 'Tax', 'Total', 'Profit'];
                $title = "Summary Customers Report";
                break;

            case 'summary_employees':
                $query = DB::table('phppos_sales')
                    ->join('phppos_employees', 'phppos_sales.employee_id', '=', 'phppos_employees.person_id')
                    ->selectRaw('phppos_employees.username as employee, COUNT(*) as sales_count, SUM(subtotal) as subtotal, SUM(tax) as tax, SUM(total) as total, SUM(profit) as profit')
                    ->where('phppos_sales.deleted', 0);
                
                // Manual filters for joined query
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);
                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                if ($saleType !== 'all') $query->where('phppos_sales.sale_type', $saleType === 'sales' ? 'sale' : 'return');
                if ($paymentType !== 'all') $query->where('phppos_sales.payment_type', $paymentType);

                $data = $query->groupBy('phppos_employees.person_id', 'phppos_employees.username')
                    ->orderBy('total', 'desc')
                    ->get();

                $headers = ['Employee', 'Sales Count', 'Subtotal', 'Tax', 'Total', 'Profit'];
                $title = "Summary Employees Report";
                break;

            case 'inventory_summary':
                $data = DB::table('phppos_items')
                    ->select('name', 'cost_price', 'unit_price')
                    ->where('deleted', 0)
                    ->orderBy('name')
                    ->get();

                $headers = ['Item Name', 'Cost Price', 'Selling Price'];
                $title = "Inventory Summary Report";
                break;

            case 'summary_expenses':
                $data = DB::table('phppos_expenses')
                    ->selectRaw('expense_type as category, SUM(expense_amount) as total, COUNT(*) as count')
                    ->whereBetween('expense_date', [$startDate, $endDate])
                    ->groupBy('expense_type')
                    ->orderBy('total', 'desc')
                    ->get();

                $headers = ['Category', 'Count', 'Total Expenses'];
                $title = "Summary Expenses Report";
                break;

            default:
                return redirect()->back()->with('error', 'Report type not implemented yet.');
        }

        return view('reports.tabular', compact('data', 'headers', 'title', 'startDate', 'endDate', 'report'));
    }
}
