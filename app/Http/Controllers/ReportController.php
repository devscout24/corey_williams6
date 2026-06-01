<?php

namespace App\Http\Controllers;

use App\Services\LocationContextService;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct(private readonly LocationContextService $locationContextService)
    {
    }

    public function index(): View
    {
        return view('reports.listing');
    }

    public function generate(Request $request, string $report): View
    {
        // Support legacy CI3-style links that directly generate a report via query string.
        // Example: /reports/generate/detailed_sales?report_type=simple&report_date_range_simple=TODAY&sale_type=all&with_time=1
        if ($request->query->count() > 0 && $request->hasAny(['report_date_range_simple', 'start_date', 'end_date', 'with_time', 'sale_type', 'payment_type'])) {
            return $this->store($request, $report);
        }

        $title = str_replace('_', ' ', ucfirst($report));
        $currentLocationId = $this->locationContextService->resolveLocationId(null);
        $locations = DB::table('phppos_locations')
            ->where('location_id', $currentLocationId)
            ->get();
        $paymentTypes = ['Cash', 'Check', 'Debit Card', 'Credit Card', 'Gift Card', 'Store Account'];
        $customers = DB::table('phppos_customers')
            ->join('phppos_people', 'phppos_customers.person_id', '=', 'phppos_people.person_id')
            ->select('phppos_customers.person_id', 'phppos_people.first_name', 'phppos_people.last_name')
            ->get();

        $employees = DB::table('phppos_employees')
            ->join('phppos_people', 'phppos_employees.person_id', '=', 'phppos_people.person_id')
            ->select('phppos_employees.person_id', 'phppos_people.first_name', 'phppos_people.last_name')
            ->get();
        
        return view('reports.generate', compact('report', 'title', 'locations', 'paymentTypes', 'customers', 'employees'));
    }

    public function store(Request $request, string $report)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Legacy support: allow CI3-style simple date ranges (TODAY, LAST_7, etc.)
        // when explicit start/end dates are not provided.
        if ((! $startDate || ! $endDate) && $request->filled('report_date_range_simple')) {
            [$startDate, $endDate] = $this->resolveSimpleDateRange($request->input('report_date_range_simple'));
        }

        // Sensible default (prevents invalid SQL if no dates passed at all).
        $startDate = $startDate ?: now()->toDateString();
        $endDate = $endDate ?: now()->toDateString();

        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime = $endDate . ' 23:59:59';

        $saleType = $request->input('sale_type', 'all');
        $paymentType = $request->input('payment_type', 'all');
        // Single-location POS: always filter reports to the current node's resolved location.
        $locationId = $this->locationContextService->resolveLocationId(null);

        $groupBy = $request->input('group_by', 'day');

        $employeeId = $request->input('employee_id', 'all');
        $employeeType = $request->input('employee_type', 'sold_by_employee_id');

        $applySalesFilters = function ($query) use ($saleType, $paymentType, $locationId, $startDateTime, $endDateTime, $employeeId, $employeeType, $request) {
            $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);
            $query->where('phppos_sales.location_id', $locationId);
            if ($saleType !== 'all') $query->where('phppos_sales.sale_type', $saleType === 'sales' ? 'sale' : 'return');
            if ($paymentType !== 'all') $query->where('phppos_sales.payment_type', $paymentType);
            if ($employeeId !== 'all') $query->where('phppos_sales.' . $employeeType, $employeeId);
            if ($request->filled('customer_id') && $request->input('customer_id') !== 'all') {
                $query->where('phppos_sales.customer_id', $request->input('customer_id'));
            }
            return $query;
        };

        $mapToTopLevel = function ($data) {
            $categories = DB::table('phppos_categories')->get()->keyBy('id');
            $topLevelData = [];
            
            foreach ($data as $row) {
                $currentId = $row->category_id ?? null;
                if (!$currentId) {
                    $topLevelData['Unknown'] = $row;
                    continue;
                }

                // Traverse up to the root
                while (isset($categories[$currentId]) && $categories[$currentId]->parent_id) {
                    $currentId = $categories[$currentId]->parent_id;
                }

                $topName = $categories[$currentId]->name ?? 'Unknown';
                if (!isset($topLevelData[$topName])) {
                    $topLevelData[$topName] = (object)[
                        'category' => $topName,
                        'sales_count' => 0,
                        'subtotal' => 0,
                        'tax' => 0,
                        'total' => 0,
                        'profit' => 0,
                        'count' => 0 // For expenses/receivings
                    ];
                }

                if (isset($row->sales_count)) $topLevelData[$topName]->sales_count += $row->sales_count;
                if (isset($row->subtotal)) $topLevelData[$topName]->subtotal += $row->subtotal;
                if (isset($row->tax)) $topLevelData[$topName]->tax += $row->tax;
                if (isset($row->total)) $topLevelData[$topName]->total += $row->total;
                if (isset($row->profit)) $topLevelData[$topName]->profit += $row->profit;
                if (isset($row->count)) $topLevelData[$topName]->count += $row->count;
            }
            return array_values($topLevelData);
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
                    'Busiest Hour' => ($rawData->count() > 0 ? $rawData->sortByDesc('count')->first()->hour : 'N/A') . ':00',
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

            case 'summary_categories':
                $query = DB::table('phppos_sales_items')
                    ->join('phppos_sales', 'phppos_sales_items.sale_id', '=', 'phppos_sales.sale_id')
                    ->join('phppos_items', 'phppos_sales_items.item_id', '=', 'phppos_items.item_id')
                    ->join('phppos_categories', 'phppos_items.category_id', '=', 'phppos_categories.id')
                    ->selectRaw('phppos_categories.name as category, COUNT(DISTINCT phppos_sales.sale_id) as sales_count, SUM(phppos_sales_items.subtotal) as subtotal, SUM(phppos_sales_items.tax) as tax, SUM(phppos_sales_items.line_total) as total, SUM(phppos_sales_items.profit) as profit')
                    ->where('phppos_sales.deleted', 0);

                // Apply filters manually for joined query
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);
                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                if ($saleType !== 'all') $query->where('phppos_sales.sale_type', $saleType === 'sales' ? 'sale' : 'return');
                if ($paymentType !== 'all') $query->where('phppos_sales.payment_type', $paymentType);

                $data = $query->groupBy('phppos_categories.id', 'phppos_categories.name')
                    ->orderBy('total', 'desc')
                    ->get();

                if ($request->has('top_level_categories_only')) {
                    // We need category_id for mapping
                    $query->addSelect('phppos_categories.id as category_id');
                    $data = $mapToTopLevel($query->get());
                }

                $headers = ['Category', 'Sales Count', 'Subtotal', 'Tax', 'Total', 'Profit'];
                $title = "Summary Categories Report";
                break;

            case 'graphical_summary_categories':
                $query = DB::table('phppos_sales_items')
                    ->join('phppos_sales', 'phppos_sales_items.sale_id', '=', 'phppos_sales.sale_id')
                    ->join('phppos_items', 'phppos_sales_items.item_id', '=', 'phppos_items.item_id')
                    ->join('phppos_categories', 'phppos_items.category_id', '=', 'phppos_categories.id')
                    ->selectRaw('phppos_categories.name as category, SUM(phppos_sales_items.line_total) as total')
                    ->where('phppos_sales.deleted', 0);

                // Apply filters manually for joined query
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);
                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                if ($saleType !== 'all') $query->where('phppos_sales.sale_type', $saleType === 'sales' ? 'sale' : 'return');
                if ($paymentType !== 'all') $query->where('phppos_sales.payment_type', $paymentType);

                $rawData = $query->groupBy('phppos_categories.id', 'phppos_categories.name')
                    ->orderBy('total', 'desc')
                    ->get();

                if ($request->has('top_level_categories_only')) {
                    $rawData = collect($mapToTopLevel($rawData));
                }

                $chartData = [
                    'labels' => $rawData->pluck('category')->toArray(),
                    'values' => $rawData->pluck('total')->toArray(),
                ];
                $summary = [
                    'Total Sales' => $rawData->sum('total'),
                    'Top Category' => $rawData->first() ? $rawData->first()->category : 'N/A',
                ];
                $title = "Graphical Summary Categories";
                $chartType = 'pie';
                return view('reports.graphical', compact('chartData', 'summary', 'title', 'startDate', 'endDate', 'report', 'chartType'));

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

            case 'summary_categories_receivings':
                $query = DB::table('phppos_receivings_items')
                    ->join('phppos_receivings', 'phppos_receivings_items.receiving_id', '=', 'phppos_receivings.receiving_id')
                    ->join('phppos_items', 'phppos_receivings_items.item_id', '=', 'phppos_items.item_id')
                    ->join('phppos_categories', 'phppos_items.category_id', '=', 'phppos_categories.id')
                    ->selectRaw('phppos_categories.name as category, phppos_categories.id as category_id, COUNT(DISTINCT phppos_receivings.receiving_id) as count, SUM(phppos_receivings_items.subtotal) as subtotal, SUM(phppos_receivings_items.tax) as tax, SUM(phppos_receivings_items.total) as total')
                    ->where('phppos_receivings.deleted', 0);

                $query->whereBetween('phppos_receivings.created_at', [$startDateTime, $endDateTime]);
                if ($locationId !== 'all') $query->where('phppos_receivings.location_id', $locationId);

                $data = $query->groupBy('phppos_categories.id', 'phppos_categories.name')
                    ->orderBy('total', 'desc')
                    ->get();

                if ($request->has('top_level_categories_only')) {
                    $data = $mapToTopLevel($data);
                }

                $headers = ['Category', 'Receivings Count', 'Subtotal', 'Tax', 'Total'];
                $title = "Summary Categories Receivings Report";
                break;

            case 'summary_commissions':
                $query = DB::table('phppos_sales_items')
                    ->join('phppos_sales', 'phppos_sales_items.sale_id', '=', 'phppos_sales.sale_id')
                    ->join('phppos_employees', 'phppos_employees.person_id', '=', 'phppos_sales.' . $employeeType)
                    ->join('phppos_people', 'phppos_employees.person_id', '=', 'phppos_people.person_id')
                    ->selectRaw('CONCAT(phppos_people.first_name, " ", phppos_people.last_name) as employee, SUM(phppos_sales_items.subtotal) as subtotal, SUM(phppos_sales_items.line_total) as total, SUM(phppos_sales_items.tax) as tax, SUM(phppos_sales_items.profit) as profit, SUM(phppos_sales_items.commission) as commission')
                    ->where('phppos_sales.deleted', 0);

                $applySalesFilters($query);

                $data = $query->groupBy('phppos_employees.person_id', 'phppos_people.first_name', 'phppos_people.last_name')
                    ->get();

                $headers = ['Employee', 'Subtotal', 'Total', 'Tax', 'Profit', 'Commission'];
                $title = "Summary Commissions Report";
                break;

            case 'graphical_summary_commissions':
                $query = DB::table('phppos_sales_items')
                    ->join('phppos_sales', 'phppos_sales_items.sale_id', '=', 'phppos_sales.sale_id')
                    ->join('phppos_employees', 'phppos_employees.person_id', '=', 'phppos_sales.' . $employeeType)
                    ->join('phppos_people', 'phppos_employees.person_id', '=', 'phppos_people.person_id')
                    ->selectRaw('CONCAT(phppos_people.first_name, " ", phppos_people.last_name) as employee, SUM(phppos_sales_items.commission) as total')
                    ->where('phppos_sales.deleted', 0);

                $applySalesFilters($query);

                $rawData = $query->groupBy('phppos_employees.person_id', 'phppos_people.first_name', 'phppos_people.last_name')
                    ->get();

                $chartData = [
                    'labels' => $rawData->pluck('employee')->toArray(),
                    'values' => $rawData->pluck('total')->toArray(),
                ];

                return view('reports.graphical', [
                    'report' => $report,
                    'title' => "Graphical Summary Commissions",
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'chartData' => $chartData,
                    'summary' => [
                        'Total Employees' => count($chartData['labels']),
                        'Total Commission' => array_sum($chartData['values'])
                    ]
                ]);

            case 'detailed_commissions':
                $query = DB::table('phppos_sales')
                    ->leftJoin('phppos_people as customer', 'phppos_sales.customer_id', '=', 'customer.person_id')
                    ->join('phppos_locations', 'phppos_sales.location_id', '=', 'phppos_locations.location_id')
                    ->selectRaw('phppos_sales.sale_id, phppos_locations.name as location, phppos_sales.created_at as sale_time, CONCAT(customer.first_name, " ", customer.last_name) as customer, phppos_sales.subtotal, phppos_sales.total, phppos_sales.tax, phppos_sales.profit, (SELECT SUM(commission) FROM phppos_sales_items WHERE sale_id = phppos_sales.sale_id) as commission, phppos_sales.payment_type, phppos_sales.comment')
                    ->where('phppos_sales.deleted', 0);

                $applySalesFilters($query);

                $data = $query->orderBy('phppos_sales.created_at', 'desc')->get();

                $headers = ['Sale ID', 'Location', 'Time', 'Customer', 'Subtotal', 'Total', 'Tax', 'Profit', 'Commission', 'Payment Type', 'Comment'];
                $title = "Detailed Commissions Report";
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

            case 'graphical_summary_customers':
                $query = DB::table('phppos_sales')
                    ->leftJoin('phppos_people', 'phppos_sales.customer_id', '=', 'phppos_people.person_id')
                    ->selectRaw('COALESCE(CONCAT(phppos_people.first_name, " ", phppos_people.last_name), "Walk-in") as customer, SUM(total) as total')
                    ->where('phppos_sales.deleted', 0);

                $applySalesFilters($query);

                $rawData = $query->groupBy('phppos_sales.customer_id', 'phppos_people.first_name', 'phppos_people.last_name')
                    ->orderBy('total', 'desc')
                    ->limit(10)
                    ->get();

                $chartData = [
                    'labels' => $rawData->pluck('customer')->toArray(),
                    'values' => $rawData->pluck('total')->toArray(),
                ];
                $summary = [
                    'Total Sales' => $rawData->sum('total'),
                    'Top Customer' => $rawData->first() ? $rawData->first()->customer : 'N/A',
                ];
                $title = "Graphical Summary Customers (Top 10)";
                $chartType = 'bar';
                return view('reports.graphical', compact('chartData', 'summary', 'title', 'startDate', 'endDate', 'report', 'chartType'));

            case 'summary_customers':
                $query = DB::table('phppos_sales')
                    ->leftJoin('phppos_people', 'phppos_sales.customer_id', '=', 'phppos_people.person_id')
                    ->selectRaw('COALESCE(CONCAT(phppos_people.first_name, " ", phppos_people.last_name), "Walk-in") as customer, COUNT(*) as sales_count, SUM(subtotal) as subtotal, SUM(tax) as tax, SUM(total) as total, SUM(profit) as profit')
                    ->where('phppos_sales.deleted', 0);
                $data = $applySalesFilters($query)
                    ->groupBy('phppos_sales.customer_id', 'phppos_people.first_name', 'phppos_people.last_name')
                    ->orderBy('total', 'desc')
                    ->get();

                $headers = ['Customer', 'Sales Count', 'Subtotal', 'Tax', 'Total', 'Profit'];
                $title = "Summary Customers Report";
                break;

            case 'specific_customer':
                $query = DB::table('phppos_sales')
                    ->select('sale_id', 'created_at', 'subtotal', 'tax', 'total', 'profit', 'payment_type')
                    ->where('deleted', 0);
                $data = $applySalesFilters($query)
                    ->orderBy('created_at', 'desc')
                    ->get();

                $headers = ['Sale ID', 'Date', 'Subtotal', 'Tax', 'Total', 'Profit', 'Payment'];
                $title = "Specific Customer Detailed Report";
                break;

            case 'new_customers':
                $data = DB::table('phppos_customers')
                    ->join('phppos_people', 'phppos_customers.person_id', '=', 'phppos_people.person_id')
                    ->select('phppos_people.first_name', 'phppos_people.last_name', 'phppos_people.email', 'phppos_people.phone_number', 'phppos_customers.created_at')
                    ->whereBetween('phppos_customers.created_at', [$startDateTime, $endDateTime])
                    ->get();
                $headers = ['First Name', 'Last Name', 'Email', 'Phone', 'Created At'];
                $title = "New Customers Report";
                break;

            case 'customers_series':
                $select = match($groupBy) {
                    'week' => 'YEARWEEK(created_at) as group_key, MIN(DATE(created_at)) as sale_date',
                    'month' => 'DATE_FORMAT(created_at, "%Y-%m") as group_key, MIN(DATE(created_at)) as sale_date',
                    'year' => 'YEAR(created_at) as group_key, MIN(DATE(created_at)) as sale_date',
                    default => 'DATE(created_at) as group_key, DATE(created_at) as sale_date',
                };
                
                $query = DB::table('phppos_sales')
                    ->selectRaw($select . ', SUM(total) as total')
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
                    'Total Purchases' => $rawData->sum('total'),
                    'Peak Period' => $rawData->sortByDesc('total')->first()?->sale_date ?? 'N/A',
                ];
                $title = "Customer Series Report (" . ucfirst($groupBy) . ")";
                $chartType = 'line';
                return view('reports.graphical', compact('chartData', 'summary', 'title', 'startDate', 'endDate', 'report', 'chartType'));

            case 'summary_customers_zip':
                $query = DB::table('phppos_sales')
                    ->join('phppos_people', 'phppos_sales.customer_id', '=', 'phppos_people.person_id')
                    ->selectRaw('phppos_people.zip, COUNT(*) as sales_count, SUM(subtotal) as subtotal, SUM(tax) as tax, SUM(total) as total, SUM(profit) as profit')
                    ->where('phppos_sales.deleted', 0)
                    ->where('phppos_people.zip', '!=', '');
                
                $data = $applySalesFilters($query)
                    ->groupBy('phppos_people.zip')
                    ->orderBy('total', 'desc')
                    ->get();

                $headers = ['Zip Code', 'Sales Count', 'Subtotal', 'Tax', 'Total', 'Profit'];
                $title = "Summary Customers Zip Report";
                break;

            case 'graphical_customers_zip':
                $query = DB::table('phppos_sales')
                    ->join('phppos_people', 'phppos_sales.customer_id', '=', 'phppos_people.person_id')
                    ->selectRaw('phppos_people.zip, SUM(total) as total')
                    ->where('phppos_sales.deleted', 0)
                    ->where('phppos_people.zip', '!=', '');

                $rawData = $applySalesFilters($query)
                    ->groupBy('phppos_people.zip')
                    ->orderBy('total', 'desc')
                    ->limit(10)
                    ->get();

                $chartData = [
                    'labels' => $rawData->pluck('zip')->toArray(),
                    'values' => $rawData->pluck('total')->toArray(),
                ];
                $summary = [
                    'Total Sales' => $rawData->sum('total'),
                    'Top Zip Code' => $rawData->first() ? $rawData->first()->zip : 'N/A',
                ];
                $title = "Graphical Zip Code Report (Top 10)";
                $chartType = 'pie';
                return view('reports.graphical', compact('chartData', 'summary', 'title', 'startDate', 'endDate', 'report', 'chartType'));

            case 'summary_non_taxable_customers':
                $query = DB::table('phppos_sales')
                    ->leftJoin('phppos_people', 'phppos_sales.customer_id', '=', 'phppos_people.person_id')
                    ->selectRaw('COALESCE(CONCAT(phppos_people.first_name, " ", phppos_people.last_name), "Walk-in") as customer, COUNT(*) as sales_count, SUM(subtotal) as subtotal, SUM(total) as total')
                    ->where('phppos_sales.deleted', 0)
                    ->where('phppos_sales.tax', 0);
                
                $data = $applySalesFilters($query)
                    ->groupBy('phppos_sales.customer_id', 'phppos_people.first_name', 'phppos_people.last_name')
                    ->orderBy('total', 'desc')
                    ->get();

                $headers = ['Customer', 'Sales Count', 'Subtotal', 'Total'];
                $title = "Summary Non-Taxable Customers Report";
                break;

            case 'detailed_deliveries':
                $query = DB::table('phppos_sales_deliveries')
                    ->leftJoin('phppos_sales', 'phppos_sales.sale_id', '=', 'phppos_sales_deliveries.sale_id')
                    ->leftJoin('phppos_locations', 'phppos_sales_deliveries.location_id', '=', 'phppos_locations.location_id')
                    ->leftJoin('phppos_registers', 'phppos_sales.register_id', '=', 'phppos_registers.register_id')
                    ->leftJoin('phppos_people as employee', 'phppos_sales.employee_id', '=', 'employee.person_id')
                    ->leftJoin('phppos_people as sold_by_employee', 'phppos_sales.sold_by_employee_id', '=', 'sold_by_employee.person_id')
                    ->leftJoin('phppos_people as customer', 'phppos_sales.customer_id', '=', 'customer.person_id')
                    ->leftJoin('phppos_people as delivery_employee', 'phppos_sales_deliveries.delivery_employee_person_id', '=', 'delivery_employee.person_id')
                    ->selectRaw('
                        phppos_sales.sale_id,
                        phppos_locations.name as location_name,
                        phppos_sales.created_at as sale_time,
                        phppos_sales_deliveries.status,
                        CONCAT(delivery_employee.first_name, " ", delivery_employee.last_name) as delivery_employee_name,
                        phppos_registers.name as register_name,
                        (SELECT SUM(quantity_purchased) FROM phppos_sales_items WHERE sale_id = phppos_sales.sale_id) as items_purchased,
                        CONCAT(employee.first_name, " ", employee.last_name) as employee_name,
                        CONCAT(sold_by_employee.first_name, " ", sold_by_employee.last_name) as sold_by_name,
                        COALESCE(CONCAT(customer.first_name, " ", customer.last_name), "Walk-in") as customer_name,
                        phppos_sales.subtotal,
                        phppos_sales.total,
                        phppos_sales.tax,
                        phppos_sales.profit,
                        (phppos_sales.subtotal - phppos_sales.profit) as cogs,
                        phppos_sales_deliveries.comment
                    ')
                    ->where('phppos_sales_deliveries.deleted', 0);

                $data = $applySalesFilters($query)
                    ->orderBy('phppos_sales.created_at', 'desc')
                    ->get()
                    ->map(function($row) {
                        return [
                            'Sale ID' => $row->sale_id,
                            'Location' => $row->location_name,
                            'Date' => $row->sale_time,
                            'Status' => $row->status,
                            'Delivery Employee' => $row->delivery_employee_name,
                            'Register' => $row->register_name,
                            'Items Purchased' => number_format($row->items_purchased, 2),
                            'Sold By' => $row->sold_by_name ?: $row->employee_name,
                            'Sold To' => $row->customer_name,
                            'Subtotal' => number_format($row->subtotal, 2),
                            'Total' => number_format($row->total, 2),
                            'Tax' => number_format($row->tax, 2),
                            'Profit' => number_format($row->profit, 2),
                            'COGS' => number_format($row->cogs, 2),
                            'Comments' => $row->comment,
                        ];
                    });

                $headers = ['Sale ID', 'Location', 'Date', 'Status', 'Delivery Employee', 'Register', 'Items Purchased', 'Sold By', 'Sold To', 'Subtotal', 'Total', 'Tax', 'Profit', 'COGS', 'Comments'];
                $title = "Detailed Deliveries Report";
                break;

            case 'graphical_summary_employees':
                $query = DB::table('phppos_sales')
                    ->join('phppos_employees', 'phppos_sales.employee_id', '=', 'phppos_employees.person_id')
                    ->join('phppos_people', 'phppos_employees.person_id', '=', 'phppos_people.person_id')
                    ->selectRaw('CONCAT(phppos_people.first_name, " ", phppos_people.last_name) as employee, SUM(total) as total')
                    ->where('phppos_sales.deleted', 0);

                $rawData = $applySalesFilters($query)
                    ->groupBy('employee', 'phppos_employees.person_id')
                    ->orderBy('total', 'desc')
                    ->limit(10)
                    ->get();

                $chartData = [
                    'labels' => $rawData->pluck('employee')->toArray(),
                    'values' => $rawData->pluck('total')->toArray(),
                ];
                $summary = [
                    'Total Sales' => $rawData->sum('total'),
                    'Top Performer' => $rawData->first() ? $rawData->first()->employee : 'N/A',
                ];
                $title = "Graphical Employee Summary (Top 10)";
                $chartType = 'bar';
                return view('reports.graphical', compact('chartData', 'summary', 'title', 'startDate', 'endDate', 'report', 'chartType'));

            case 'specific_employee':
                $query = DB::table('phppos_sales')
                    ->leftJoin('phppos_people as customer', 'phppos_sales.customer_id', '=', 'customer.person_id')
                    ->leftJoin('phppos_people as employee', 'phppos_sales.employee_id', '=', 'employee.person_id')
                    ->selectRaw('phppos_sales.sale_id, phppos_sales.created_at, COALESCE(CONCAT(customer.first_name, " ", customer.last_name), "Walk-in") as customer_name, CONCAT(employee.first_name, " ", employee.last_name) as employee_name, subtotal, total, tax, profit, payment_type, comment')
                    ->where('phppos_sales.deleted', 0);
                
                $data = $applySalesFilters($query)
                    ->orderBy('phppos_sales.created_at', 'desc')
                    ->get();

                $headers = ['Sale ID', 'Date', 'Employee', 'Customer', 'Subtotal', 'Total', 'Tax', 'Profit', 'Payment Type', 'Comment'];
                
                $employeeName = "All Employees";
                if ($request->filled('employee_id') && $request->input('employee_id') !== 'all') {
                    $employeeName = DB::table('phppos_people')->where('person_id', $request->input('employee_id'))->selectRaw('CONCAT(first_name, " ", last_name) as name')->value('name');
                }
                
                $title = "Employee Detailed Report: " . $employeeName;
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
                $query = DB::table('phppos_items')
                    ->leftJoin('phppos_location_items', function($join) use ($locationId) {
                        $join->on('phppos_items.item_id', '=', 'phppos_location_items.item_id');
                        if ($locationId !== 'all') {
                            $join->where('phppos_location_items.location_id', '=', $locationId);
                        }
                    })
                    ->leftJoin('phppos_categories', 'phppos_items.category_id', '=', 'phppos_categories.id')
                    ->selectRaw('phppos_items.item_id, phppos_items.name, phppos_categories.name as category, COALESCE(SUM(phppos_location_items.quantity), 0) as quantity, phppos_items.cost_price, phppos_items.unit_price')
                    ->where('phppos_items.deleted', 0);
                
                $data = $query->groupBy('phppos_items.item_id', 'phppos_items.name', 'phppos_categories.name', 'phppos_items.cost_price', 'phppos_items.unit_price')
                    ->get();

                $headers = ['Item ID', 'Item Name', 'Category', 'Quantity', 'Cost Price', 'Selling Price'];
                $title = "Inventory Summary Report";
                break;

            case 'inventory_low':
                $query = DB::table('phppos_items')
                    ->leftJoin('phppos_location_items', function($join) use ($locationId) {
                        $join->on('phppos_items.item_id', '=', 'phppos_location_items.item_id');
                        if ($locationId !== 'all') {
                            $join->where('phppos_location_items.location_id', '=', $locationId);
                        }
                    })
                    ->leftJoin('phppos_categories', 'phppos_items.category_id', '=', 'phppos_categories.id')
                    ->selectRaw('phppos_items.item_id, phppos_items.name, phppos_categories.name as category, COALESCE(SUM(phppos_location_items.quantity), 0) as quantity, COALESCE(phppos_location_items.reorder_level, phppos_items.reorder_level) as effective_reorder_level')
                    ->where('phppos_items.deleted', 0)
                    ->where('phppos_items.is_service', 0);
                
                $data = $query->groupBy('phppos_items.item_id', 'phppos_items.name', 'phppos_categories.name', 'phppos_location_items.reorder_level', 'phppos_items.reorder_level')
                    ->havingRaw('quantity <= effective_reorder_level')
                    ->get();

                $headers = ['Item ID', 'Item Name', 'Category', 'Quantity', 'Reorder Level'];
                $title = "Low Inventory Report";
                break;

            case 'inventory_at_past_date':
                $pastDate = $request->input('date', date('Y-m-d'));
                
                $subQuery = DB::table('phppos_inventory')
                    ->select('trans_items', 'location_id', DB::raw('MAX(trans_id) as max_id'))
                    ->where('trans_date', '<=', $pastDate . ' 23:59:59')
                    ->groupBy('trans_items', 'location_id');

                $query = DB::table('phppos_items')
                    ->joinSub($subQuery, 'latest_inv', function ($join) {
                        $join->on('phppos_items.item_id', '=', 'latest_inv.trans_items');
                    })
                    ->join('phppos_inventory', 'latest_inv.max_id', '=', 'phppos_inventory.trans_id')
                    ->leftJoin('phppos_categories', 'phppos_items.category_id', '=', 'phppos_categories.id')
                    ->selectRaw('phppos_items.item_id, phppos_items.name, phppos_categories.name as category, phppos_inventory.trans_current_quantity as quantity, phppos_items.cost_price, phppos_items.unit_price')
                    ->where('phppos_items.deleted', 0);

                if ($locationId !== 'all') {
                    $query->where('phppos_inventory.location_id', $locationId);
                }

                $data = $query->get();

                $headers = ['Item ID', 'Item Name', 'Category', 'Quantity', 'Cost Price', 'Selling Price'];
                $title = "Inventory at Past Date Report (" . $pastDate . ")";
                break;

            case 'detailed_inventory':
                $query = DB::table('phppos_inventory')
                    ->join('phppos_items', 'phppos_inventory.trans_items', '=', 'phppos_items.item_id')
                    ->leftJoin('phppos_employees', 'phppos_inventory.trans_user', '=', 'phppos_employees.person_id')
                    ->leftJoin('phppos_people as employee_person', 'phppos_employees.person_id', '=', 'employee_person.person_id')
                    ->leftJoin('phppos_locations', 'phppos_inventory.location_id', '=', 'phppos_locations.location_id')
                    ->selectRaw('phppos_inventory.trans_date, phppos_items.name as item_name, phppos_inventory.trans_inventory as change_amount, phppos_inventory.trans_current_quantity as result_quantity, CONCAT(employee_person.first_name, " ", employee_person.last_name) as employee_name, phppos_locations.name as location_name, phppos_inventory.trans_comment as comment')
                    ->whereBetween('phppos_inventory.trans_date', [$startDateTime, $endDateTime]);
                
                if ($locationId !== 'all') {
                    $query->where('phppos_inventory.location_id', $locationId);
                }

                $data = $query->orderBy('phppos_inventory.trans_date', 'desc')->get();

                $headers = ['Date', 'Item', 'In/Out', 'Resulting Qty', 'Employee', 'Location', 'Comment'];
                $title = "Detailed Inventory Report";
                break;

            case 'detailed_receivings':
                $query = DB::table('phppos_receivings')
                    ->leftJoin('phppos_suppliers', 'phppos_receivings.supplier_id', '=', 'phppos_suppliers.person_id')
                    ->leftJoin('phppos_employees', 'phppos_receivings.employee_id', '=', 'phppos_employees.person_id')
                    ->leftJoin('phppos_people as employee_person', 'phppos_employees.person_id', '=', 'employee_person.person_id')
                    ->leftJoin('phppos_locations', 'phppos_receivings.location_id', '=', 'phppos_locations.location_id')
                    ->selectRaw('phppos_receivings.receiving_id, phppos_receivings.receiving_time, phppos_suppliers.company_name as supplier, CONCAT(employee_person.first_name, " ", employee_person.last_name) as employee, phppos_receivings.total, phppos_receivings.payment_type, phppos_locations.name as location')
                    ->whereBetween('phppos_receivings.receiving_time', [$startDateTime, $endDateTime])
                    ->where('phppos_receivings.deleted', 0);

                if ($locationId !== 'all') {
                    $query->where('phppos_receivings.location_id', $locationId);
                }

                $data = $query->orderBy('phppos_receivings.receiving_time', 'desc')->get();

                $headers = ['ID', 'Date', 'Supplier', 'Employee', 'Total', 'Payment', 'Location'];
                $title = "Detailed Receivings Report";
                break;

            case 'detailed_transfers':
                $query = DB::table('phppos_receivings')
                    ->join('phppos_locations as from_loc', 'phppos_receivings.location_id', '=', 'from_loc.location_id')
                    ->join('phppos_locations as to_loc', 'phppos_receivings.transfer_to_location_id', '=', 'to_loc.location_id')
                    ->leftJoin('phppos_employees', 'phppos_receivings.employee_id', '=', 'phppos_employees.person_id')
                    ->leftJoin('phppos_people as employee_person', 'phppos_employees.person_id', '=', 'employee_person.person_id')
                    ->selectRaw('phppos_receivings.receiving_id, phppos_receivings.receiving_time, from_loc.name as from_location, to_loc.name as to_location, CONCAT(employee_person.first_name, " ", employee_person.last_name) as employee, phppos_receivings.total_quantity_purchased as quantity')
                    ->whereNotNull('phppos_receivings.transfer_to_location_id')
                    ->whereBetween('phppos_receivings.receiving_time', [$startDateTime, $endDateTime])
                    ->where('phppos_receivings.deleted', 0);

                if ($locationId !== 'all') {
                    $query->where(function($q) use ($locationId) {
                        $q->where('phppos_receivings.location_id', $locationId)
                          ->orWhere('phppos_receivings.transfer_to_location_id', $locationId);
                    });
                }

                $data = $query->orderBy('phppos_receivings.receiving_time', 'desc')->get();

                $headers = ['ID', 'Date', 'From', 'To', 'Employee', 'Total Items'];
                $title = "Detailed Transfers Report";
                break;

            case 'summary_count_report':
                $query = DB::table('phppos_inventory_counts')
                    ->leftJoin('phppos_employees', 'phppos_inventory_counts.employee_id', '=', 'phppos_employees.person_id')
                    ->leftJoin('phppos_people as employee_person', 'phppos_employees.person_id', '=', 'employee_person.person_id')
                    ->leftJoin('phppos_locations', 'phppos_inventory_counts.location_id', '=', 'phppos_locations.location_id')
                    ->leftJoin('phppos_inventory_counts_items', 'phppos_inventory_counts.id', '=', 'phppos_inventory_counts_items.inventory_counts_id')
                    ->selectRaw('phppos_inventory_counts.id, phppos_inventory_counts.count_date, CONCAT(employee_person.first_name, " ", employee_person.last_name) as employee, phppos_locations.name as location, phppos_inventory_counts.status, COUNT(phppos_inventory_counts_items.id) as items_counted, SUM(phppos_inventory_counts_items.count - phppos_inventory_counts_items.actual_quantity) as difference')
                    ->whereBetween('phppos_inventory_counts.count_date', [$startDateTime, $endDateTime]);

                if ($locationId !== 'all') {
                    $query->where('phppos_inventory_counts.location_id', $locationId);
                }

                $data = $query->groupBy('phppos_inventory_counts.id', 'phppos_inventory_counts.count_date', 'employee_person.first_name', 'employee_person.last_name', 'phppos_locations.name', 'phppos_inventory_counts.status')->get();

                $headers = ['ID', 'Date', 'Employee', 'Location', 'Status', 'Items Counted', 'Difference'];
                $title = "Summary Count Report";
                break;

            case 'detailed_count_report':
                $query = DB::table('phppos_inventory_counts_items')
                    ->join('phppos_inventory_counts', 'phppos_inventory_counts_items.inventory_counts_id', '=', 'phppos_inventory_counts.id')
                    ->join('phppos_items', 'phppos_inventory_counts_items.item_id', '=', 'phppos_items.item_id')
                    ->leftJoin('phppos_employees', 'phppos_inventory_counts.employee_id', '=', 'phppos_employees.person_id')
                    ->leftJoin('phppos_people as employee_person', 'phppos_employees.person_id', '=', 'employee_person.person_id')
                    ->selectRaw('phppos_inventory_counts.count_date, phppos_items.name as item_name, phppos_inventory_counts_items.actual_quantity as system_quantity, phppos_inventory_counts_items.count as counted_quantity, (phppos_inventory_counts_items.count - phppos_inventory_counts_items.actual_quantity) as difference, CONCAT(employee_person.first_name, " ", employee_person.last_name) as employee')
                    ->whereBetween('phppos_inventory_counts.count_date', [$startDateTime, $endDateTime]);

                if ($locationId !== 'all') {
                    $query->where('phppos_inventory_counts.location_id', $locationId);
                }

                $data = $query->orderBy('phppos_inventory_counts.count_date', 'desc')->get();

                $headers = ['Date', 'Item', 'System Qty', 'Counted Qty', 'Difference', 'Employee'];
                $title = "Detailed Count Report";
                break;

            case 'expiring_inventory':
                $query = DB::table('phppos_receivings_items')
                    ->join('phppos_items', 'phppos_receivings_items.item_id', '=', 'phppos_items.item_id')
                    ->join('phppos_receivings', 'phppos_receivings_items.receiving_id', '=', 'phppos_receivings.receiving_id')
                    ->leftJoin('phppos_locations', 'phppos_receivings.location_id', '=', 'phppos_locations.location_id')
                    ->selectRaw('phppos_receivings_items.expire_date, phppos_items.name as item_name, phppos_receivings_items.quantity_purchased as quantity, phppos_locations.name as location')
                    ->whereNotNull('phppos_receivings_items.expire_date')
                    ->whereBetween('phppos_receivings_items.expire_date', [$startDateTime, $endDateTime])
                    ->where('phppos_receivings.deleted', 0);

                if ($locationId !== 'all') {
                    $query->where('phppos_receivings.location_id', $locationId);
                }

                $data = $query->orderBy('phppos_receivings_items.expire_date', 'asc')->get();

                $headers = ['Expiration Date', 'Item', 'Quantity', 'Location'];
                $title = "Expiring Inventory Report";
                break;

            case 'detailed_damaged_items':
                $query = DB::table('phppos_damaged_items_log')
                    ->join('phppos_items', 'phppos_damaged_items_log.item_id', '=', 'phppos_items.item_id')
                    ->leftJoin('phppos_locations', 'phppos_damaged_items_log.location_id', '=', 'phppos_locations.location_id')
                    ->selectRaw('phppos_damaged_items_log.damaged_date, phppos_items.name as item_name, phppos_damaged_items_log.damaged_qty as quantity, phppos_damaged_items_log.damaged_reason as reason, phppos_damaged_items_log.damaged_reason_comment as comment, phppos_locations.name as location')
                    ->whereBetween('phppos_damaged_items_log.damaged_date', [$startDateTime, $endDateTime]);

                if ($locationId !== 'all') {
                    $query->where('phppos_damaged_items_log.location_id', $locationId);
                }

                $data = $query->orderBy('phppos_damaged_items_log.damaged_date', 'desc')->get();

                $headers = ['Date', 'Item', 'Quantity', 'Reason', 'Comment', 'Location'];
                $title = "Detailed Damaged Items Report";
                break;

            case 'summary_expenses':
                $query = DB::table('phppos_expenses')
                    ->selectRaw('expense_type as category, SUM(expense_amount) as total, COUNT(*) as count')
                    ->where('deleted', 0)
                    ->whereBetween('expense_date', [$startDateTime, $endDateTime]);
                
                if ($locationId !== 'all') $query->where('location_id', $locationId);

                $data = $query->groupBy('expense_type')
                    ->orderBy('total', 'desc')
                    ->get();

                $headers = ['Category', 'Count', 'Total Expenses'];
                $title = "Summary Expenses Report";
                break;

            case 'detailed_expenses':
                $query = DB::table('phppos_expenses')
                    ->leftJoin('phppos_people as employee', 'phppos_expenses.employee_id', '=', 'employee.person_id')
                    ->leftJoin('phppos_locations', 'phppos_expenses.location_id', '=', 'phppos_locations.location_id')
                    ->selectRaw('phppos_expenses.id, expense_date, phppos_locations.name as location, CONCAT(employee.first_name, " ", employee.last_name) as employee_name, expense_type, expense_amount, expense_tax, expense_note')
                    ->where('phppos_expenses.deleted', 0)
                    ->whereBetween('expense_date', [$startDateTime, $endDateTime]);
                
                if ($locationId !== 'all') $query->where('phppos_expenses.location_id', $locationId);

                $data = $query->orderBy('expense_date', 'desc')->get();

                $headers = ['ID', 'Date', 'Location', 'Employee', 'Category', 'Amount', 'Tax', 'Note'];
                $title = "Detailed Expenses Report";
                break;

            case 'summary_giftcards':
                $data = DB::table('phppos_giftcards')
                    ->leftJoin('phppos_people', 'phppos_giftcards.customer_id', '=', 'phppos_people.person_id')
                    ->selectRaw('giftcard_number, COALESCE(CONCAT(first_name, " ", last_name), "N/A") as customer, value')
                    ->where('phppos_giftcards.deleted', 0)
                    ->orderBy('value', 'desc')
                    ->get();

                $headers = ['Gift Card Number', 'Customer', 'Value'];
                $title = "Summary Giftcards Report";
                break;

            case 'detailed_giftcards':
                $query = DB::table('phppos_giftcards_log')
                    ->join('phppos_giftcards', 'phppos_giftcards_log.giftcard_id', '=', 'phppos_giftcards.giftcard_id')
                    ->selectRaw('phppos_giftcards_log.id, log_date, giftcard_number, transaction_amount, log_message')
                    ->whereBetween('log_date', [$startDateTime, $endDateTime]);
                
                $data = $query->orderBy('log_date', 'desc')->get();

                $headers = ['ID', 'Date', 'Gift Card Number', 'Amount', 'Description'];
                $title = "Detailed Giftcards Report";
                break;

            case 'giftcard_audit':
                $query = DB::table('phppos_giftcards_log')
                    ->join('phppos_giftcards', 'phppos_giftcards_log.giftcard_id', '=', 'phppos_giftcards.giftcard_id')
                    ->selectRaw('log_date, giftcard_number, phppos_giftcards.description, log_message')
                    ->whereBetween('log_date', [$startDateTime, $endDateTime]);
                
                if ($request->filled('giftcard_number')) {
                    $query->where('phppos_giftcards.giftcard_number', $request->input('giftcard_number'));
                }

                $data = $query->orderBy('log_date', 'desc')->get();

                $headers = ['Date', 'Gift Card Number', 'Description', 'Audit Log'];
                $title = "Gift Card Audit Report";
                break;

            case 'summary_giftcard_sales':
                $query = DB::table('phppos_sales_items')
                    ->join('phppos_items', 'phppos_sales_items.item_id', '=', 'phppos_items.item_id')
                    ->join('phppos_sales', 'phppos_sales_items.sale_id', '=', 'phppos_sales.sale_id')
                    ->leftJoin('phppos_people as customer', 'phppos_sales.customer_id', '=', 'customer.person_id')
                    ->selectRaw('phppos_sales.created_at, phppos_sales_items.description as giftcard_number, COALESCE(CONCAT(customer.first_name, " ", customer.last_name), "Walk-in") as customer_name, phppos_sales_items.item_unit_price as amount')
                    ->where('phppos_sales.deleted', 0)
                    ->where(function($q) {
                        $q->where('phppos_items.name', 'like', '%Giftcard%')
                          ->orWhere('phppos_items.name', 'like', '%Gift Card%');
                    })
                    ->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);
                
                $data = $query->orderBy('phppos_sales.created_at', 'desc')->get();

                $headers = ['Date', 'Gift Card Number', 'Customer', 'Amount'];
                $title = "Summary Gift Card Sales Report";
                break;

            case 'customer_invoices':
                $query = DB::table('phppos_sales')
                    ->leftJoin('phppos_people as customer', 'phppos_sales.customer_id', '=', 'customer.person_id')
                    ->selectRaw('phppos_sales.sale_id, phppos_sales.created_at, COALESCE(CONCAT(customer.first_name, " ", customer.last_name), phppos_sales.customer_name, "Walk-in") as customer_name, phppos_sales.subtotal, phppos_sales.total, phppos_sales.tax, phppos_sales.payment_type')
                    ->where('phppos_sales.deleted', 0)
                    ->whereNotNull('phppos_sales.customer_id');
                $data = $applySalesFilters($query)
                    ->orderBy('phppos_sales.created_at', 'desc')
                    ->get();

                $headers = ['Invoice ID', 'Date', 'Customer', 'Subtotal', 'Tax', 'Total', 'Payment'];
                $title = "Customer Invoices Report";
                break;

            case 'supplier_invoices':
                $query = DB::table('phppos_receivings')
                    ->leftJoin('phppos_suppliers', 'phppos_receivings.supplier_id', '=', 'phppos_suppliers.person_id')
                    ->selectRaw('phppos_receivings.receiving_id, phppos_receivings.receiving_time, phppos_suppliers.company_name as supplier, phppos_receivings.subtotal, phppos_receivings.total, phppos_receivings.tax, phppos_receivings.payment_type')
                    ->where('phppos_receivings.deleted', 0)
                    ->whereNotNull('phppos_receivings.supplier_id');
                
                $query->whereBetween('phppos_receivings.receiving_time', [$startDateTime, $endDateTime]);
                if ($locationId !== 'all') $query->where('phppos_receivings.location_id', $locationId);

                $data = $query->orderBy('phppos_receivings.receiving_time', 'desc')->get();

                $headers = ['Invoice ID', 'Date', 'Supplier', 'Subtotal', 'Tax', 'Total', 'Payment'];
                $title = "Supplier Invoices Report";
                break;

            case 'graphical_summary_items':
                $query = DB::table('phppos_sales_items')
                    ->join('phppos_sales', 'phppos_sales_items.sale_id', '=', 'phppos_sales.sale_id')
                    ->join('phppos_items', 'phppos_sales_items.item_id', '=', 'phppos_items.item_id')
                    ->selectRaw('phppos_items.name as item, SUM(phppos_sales_items.line_total) as total')
                    ->where('phppos_sales.deleted', 0);

                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                if ($saleType !== 'all') $query->where('phppos_sales.sale_type', $saleType === 'sales' ? 'sale' : 'return');
                if ($paymentType !== 'all') $query->where('phppos_sales.payment_type', $paymentType);
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $rawData = $query->groupBy('phppos_items.item_id', 'phppos_items.name')
                    ->orderBy('total', 'desc')
                    ->limit(10)
                    ->get();

                $chartData = [
                    'labels' => $rawData->pluck('item')->toArray(),
                    'values' => $rawData->pluck('total')->toArray(),
                ];
                $summary = [
                    'Total Sales (Top 10)' => $rawData->sum('total'),
                    'Top Item' => $rawData->first() ? $rawData->first()->item : 'N/A',
                ];
                $title = "Graphical Summary Items (Top 10)";
                $chartType = 'bar';
                return view('reports.graphical', compact('chartData', 'summary', 'title', 'startDate', 'endDate', 'report', 'chartType'));

            case 'graphical_summary_item_kits':
                $query = DB::table('phppos_sales_item_kits')
                    ->join('phppos_sales', 'phppos_sales_item_kits.sale_id', '=', 'phppos_sales.sale_id')
                    ->join('phppos_item_kits', 'phppos_sales_item_kits.item_kit_id', '=', 'phppos_item_kits.id')
                    ->selectRaw('phppos_item_kits.name as item_kit, SUM(phppos_sales_item_kits.total) as total')
                    ->where('phppos_sales.deleted', 0);

                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                if ($saleType !== 'all') $query->where('phppos_sales.sale_type', $saleType === 'sales' ? 'sale' : 'return');
                if ($paymentType !== 'all') $query->where('phppos_sales.payment_type', $paymentType);
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $rawData = $query->groupBy('phppos_item_kits.id', 'phppos_item_kits.name')
                    ->orderBy('total', 'desc')
                    ->get();

                $chartData = [
                    'labels' => $rawData->pluck('item_kit')->toArray(),
                    'values' => $rawData->pluck('total')->toArray(),
                ];
                $summary = [
                    'Total Sales' => $rawData->sum('total'),
                    'Top Item Kit' => $rawData->first() ? $rawData->first()->item_kit : 'N/A',
                ];
                $title = "Graphical Summary Item Kits";
                $chartType = 'pie';
                return view('reports.graphical', compact('chartData', 'summary', 'title', 'startDate', 'endDate', 'report', 'chartType'));

            case 'graphical_summary_manufacturers':
                $query = DB::table('phppos_sales_items')
                    ->join('phppos_sales', 'phppos_sales_items.sale_id', '=', 'phppos_sales.sale_id')
                    ->join('phppos_items', 'phppos_sales_items.item_id', '=', 'phppos_items.item_id')
                    ->leftJoin('phppos_manufacturers', 'phppos_items.manufacturer_id', '=', 'phppos_manufacturers.id')
                    ->selectRaw('COALESCE(phppos_manufacturers.name, "Unknown") as manufacturer, SUM(phppos_sales_items.line_total) as total')
                    ->where('phppos_sales.deleted', 0);

                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                if ($saleType !== 'all') $query->where('phppos_sales.sale_type', $saleType === 'sales' ? 'sale' : 'return');
                if ($paymentType !== 'all') $query->where('phppos_sales.payment_type', $paymentType);
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $rawData = $query->groupBy('phppos_items.manufacturer_id', 'phppos_manufacturers.name')
                    ->orderBy('total', 'desc')
                    ->get();

                $chartData = [
                    'labels' => $rawData->pluck('manufacturer')->toArray(),
                    'values' => $rawData->pluck('total')->toArray(),
                ];
                $summary = [
                    'Total Sales' => $rawData->sum('total'),
                    'Top Manufacturer' => $rawData->first() ? $rawData->first()->manufacturer : 'N/A',
                ];
                $title = "Graphical Summary Manufacturers";
                $chartType = 'pie';
                return view('reports.graphical', compact('chartData', 'summary', 'title', 'startDate', 'endDate', 'report', 'chartType'));

            case 'graphical_summary_payments':
                $query = DB::table('phppos_sales_payments')
                    ->join('phppos_sales', 'phppos_sales_payments.sale_id', '=', 'phppos_sales.sale_id')
                    ->selectRaw('phppos_sales_payments.payment_type, SUM(phppos_sales_payments.payment_amount) as total')
                    ->where('phppos_sales.deleted', 0);

                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                if ($saleType !== 'all') $query->where('phppos_sales.sale_type', $saleType === 'sales' ? 'sale' : 'return');
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $rawData = $query->groupBy('phppos_sales_payments.payment_type')
                    ->orderBy('total', 'desc')
                    ->get();

                $chartData = [
                    'labels' => $rawData->pluck('payment_type')->toArray(),
                    'values' => $rawData->pluck('total')->toArray(),
                ];
                $summary = [
                    'Total Payments' => $rawData->sum('total'),
                    'Top Payment Type' => $rawData->first() ? $rawData->first()->payment_type : 'N/A',
                ];
                $title = "Graphical Summary Payments";
                $chartType = 'pie';
                return view('reports.graphical', compact('chartData', 'summary', 'title', 'startDate', 'endDate', 'report', 'chartType'));

            case 'summary_payments':
                $query = DB::table('phppos_sales_payments')
                    ->join('phppos_sales', 'phppos_sales_payments.sale_id', '=', 'phppos_sales.sale_id')
                    ->selectRaw('phppos_sales_payments.payment_type, SUM(phppos_sales_payments.payment_amount) as total')
                    ->where('phppos_sales.deleted', 0);

                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                if ($saleType !== 'all') $query->where('phppos_sales.sale_type', $saleType === 'sales' ? 'sale' : 'return');
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $data = $query->groupBy('phppos_sales_payments.payment_type')
                    ->orderBy('total', 'desc')
                    ->get();

                $headers = ['Payment Type', 'Total'];
                $title = "Summary Payments Report";
                break;

            case 'summary_payments_registers':
                $query = DB::table('phppos_sales_payments')
                    ->join('phppos_sales', 'phppos_sales_payments.sale_id', '=', 'phppos_sales.sale_id')
                    ->leftJoin('phppos_registers', 'phppos_sales.register_id', '=', 'phppos_registers.register_id')
                    ->selectRaw('COALESCE(phppos_registers.name, "Unknown") as register_name, phppos_sales_payments.payment_type, SUM(phppos_sales_payments.payment_amount) as total')
                    ->where('phppos_sales.deleted', 0);

                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                if ($saleType !== 'all') $query->where('phppos_sales.sale_type', $saleType === 'sales' ? 'sale' : 'return');
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $data = $query->groupBy('phppos_sales.register_id', 'phppos_registers.name', 'phppos_sales_payments.payment_type')
                    ->orderBy('register_name', 'asc')
                    ->orderBy('phppos_sales_payments.payment_type', 'asc')
                    ->get();

                $headers = ['Register', 'Payment Type', 'Total'];
                $title = "Summary Payments Registers Report";
                break;

            case 'detailed_payments':
                $query = DB::table('phppos_sales_payments')
                    ->join('phppos_sales', 'phppos_sales_payments.sale_id', '=', 'phppos_sales.sale_id')
                    ->leftJoin('phppos_people as employee', 'phppos_sales.employee_id', '=', 'employee.person_id')
                    ->leftJoin('phppos_people as customer', 'phppos_sales.customer_id', '=', 'customer.person_id')
                    ->selectRaw('phppos_sales_payments.sale_id, phppos_sales.created_at as sale_date, phppos_sales_payments.payment_type, phppos_sales_payments.payment_amount, CONCAT(employee.first_name, " ", employee.last_name) as employee_name, CONCAT(customer.first_name, " ", customer.last_name) as customer_name')
                    ->where('phppos_sales.deleted', 0);

                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                if ($saleType !== 'all') $query->where('phppos_sales.sale_type', $saleType === 'sales' ? 'sale' : 'return');
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $data = $query->orderBy('phppos_sales.created_at', 'desc')
                    ->get();

                $headers = ['Sale ID', 'Date', 'Payment Type', 'Payment Amount', 'Employee', 'Customer'];
                $title = "Detailed Payments Report";
                break;

            case 'summary_manufacturers':
                $query = DB::table('phppos_sales_items')
                    ->join('phppos_sales', 'phppos_sales_items.sale_id', '=', 'phppos_sales.sale_id')
                    ->join('phppos_items', 'phppos_sales_items.item_id', '=', 'phppos_items.item_id')
                    ->leftJoin('phppos_manufacturers', 'phppos_items.manufacturer_id', '=', 'phppos_manufacturers.id')
                    ->selectRaw('COALESCE(phppos_manufacturers.name, "Unknown") as manufacturer, SUM(phppos_sales_items.subtotal) as subtotal, SUM(phppos_sales_items.line_total) as total, SUM(phppos_sales_items.tax) as tax, SUM(phppos_sales_items.profit) as profit')
                    ->where('phppos_sales.deleted', 0);

                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                if ($saleType !== 'all') $query->where('phppos_sales.sale_type', $saleType === 'sales' ? 'sale' : 'return');
                if ($paymentType !== 'all') $query->where('phppos_sales.payment_type', $paymentType);
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $data = $query->groupBy('phppos_items.manufacturer_id', 'phppos_manufacturers.name')
                    ->orderBy('manufacturer', 'asc')
                    ->get();

                $headers = ['Manufacturer', 'Subtotal', 'Total', 'Tax', 'Profit'];
                $title = "Summary Manufacturers Report";
                break;

            case 'summary_price_rules':
                $query = DB::table('phppos_sales_items')
                    ->join('phppos_sales', 'phppos_sales_items.sale_id', '=', 'phppos_sales.sale_id')
                    ->join('phppos_price_rules', 'phppos_sales_items.rule_id', '=', 'phppos_price_rules.id')
                    ->selectRaw('phppos_price_rules.name as price_rule, SUM(phppos_sales_items.subtotal) as subtotal, SUM(phppos_sales_items.line_total) as total, SUM(phppos_sales_items.tax) as tax, SUM(phppos_sales_items.profit) as profit')
                    ->where('phppos_sales.deleted', 0)
                    ->whereNotNull('phppos_sales_items.rule_id');

                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                if ($saleType !== 'all') $query->where('phppos_sales.sale_type', $saleType === 'sales' ? 'sale' : 'return');
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $data = $query->groupBy('phppos_price_rules.id', 'phppos_price_rules.name')
                    ->orderBy('phppos_price_rules.name', 'asc')
                    ->get();

                $headers = ['Price Rule', 'Subtotal', 'Total', 'Tax', 'Profit'];
                $title = "Summary Price Rules Report";
                break;

            case 'summary_item_kits':
                $query = DB::table('phppos_sales_item_kits')
                    ->join('phppos_sales', 'phppos_sales_item_kits.sale_id', '=', 'phppos_sales.sale_id')
                    ->join('phppos_item_kits', 'phppos_sales_item_kits.item_kit_id', '=', 'phppos_item_kits.id')
                    ->selectRaw('phppos_item_kits.name as item_kit, SUM(phppos_sales_item_kits.quantity_purchased) as quantity, SUM(phppos_sales_item_kits.subtotal) as subtotal, SUM(phppos_sales_item_kits.total) as total, SUM(phppos_sales_item_kits.tax) as tax, SUM(phppos_sales_item_kits.profit) as profit')
                    ->where('phppos_sales.deleted', 0);

                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                if ($saleType !== 'all') $query->where('phppos_sales.sale_type', $saleType === 'sales' ? 'sale' : 'return');
                if ($paymentType !== 'all') $query->where('phppos_sales.payment_type', $paymentType);
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $data = $query->groupBy('phppos_item_kits.id', 'phppos_item_kits.name')
                    ->orderBy('phppos_item_kits.name', 'asc')
                    ->get();

                $headers = ['Item Kit Name', 'Quantity', 'Subtotal', 'Total', 'Tax', 'Profit'];
                $title = "Summary Item Kits Report";
                break;

            case 'summary_item_kits_variance':
                $query = DB::table('phppos_sales_item_kits')
                    ->join('phppos_sales', 'phppos_sales_item_kits.sale_id', '=', 'phppos_sales.sale_id')
                    ->join('phppos_item_kits', 'phppos_sales_item_kits.item_kit_id', '=', 'phppos_item_kits.id')
                    ->selectRaw('phppos_item_kits.name as item_kit, SUM(phppos_sales_item_kits.quantity_purchased) as qty_sold, SUM(phppos_item_kits.unit_price * phppos_sales_item_kits.quantity_purchased) as expected_total, SUM(phppos_sales_item_kits.line_total) as actual_total, SUM((phppos_item_kits.unit_price * phppos_sales_item_kits.quantity_purchased) - phppos_sales_item_kits.line_total) as variance')
                    ->where('phppos_sales.deleted', 0);

                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $data = $query->groupBy('phppos_item_kits.id', 'phppos_item_kits.name')
                    ->orderBy('variance', 'desc')
                    ->get();

                $headers = ['Item Kit Name', 'Qty Sold', 'Expected Total', 'Actual Total', 'Variance'];
                $title = "Summary Item Kits Variance Report";
                break;

            case 'enhanced_summary_items':
                $query = DB::table('phppos_sales_items')
                    ->join('phppos_sales', 'phppos_sales_items.sale_id', '=', 'phppos_sales.sale_id')
                    ->join('phppos_items', 'phppos_sales_items.item_id', '=', 'phppos_items.item_id')
                    ->leftJoin('phppos_categories', 'phppos_items.category_id', '=', 'phppos_categories.id')
                    ->selectRaw('phppos_items.item_id, phppos_items.name, phppos_categories.name as category, SUM(phppos_sales_items.quantity_purchased) as qty_sold, SUM(phppos_sales_items.subtotal) as subtotal, SUM(phppos_sales_items.tax) as tax, SUM(phppos_sales_items.line_total) as total, SUM(phppos_sales_items.profit) as profit')
                    ->where('phppos_sales.deleted', 0);

                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                if ($saleType !== 'all') $query->where('phppos_sales.sale_type', $saleType === 'sales' ? 'sale' : 'return');
                if ($paymentType !== 'all') $query->where('phppos_sales.payment_type', $paymentType);
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $data = $query->groupBy('phppos_items.item_id', 'phppos_items.name', 'phppos_categories.name')
                    ->orderBy('total', 'desc')
                    ->get();

                $headers = ['Item ID', 'Item Name', 'Category', 'Qty Sold', 'Subtotal', 'Tax', 'Total', 'Profit'];
                $title = "Enhanced Summary Items Report";
                break;

            case 'top_sellers':
                $query = DB::table('phppos_sales_items')
                    ->join('phppos_sales', 'phppos_sales_items.sale_id', '=', 'phppos_sales.sale_id')
                    ->join('phppos_items', 'phppos_sales_items.item_id', '=', 'phppos_items.item_id')
                    ->selectRaw('phppos_items.name, SUM(phppos_sales_items.quantity_purchased) as qty_sold, SUM(phppos_sales_items.line_total) as total')
                    ->where('phppos_sales.deleted', 0);

                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                if ($saleType !== 'all') $query->where('phppos_sales.sale_type', $saleType === 'sales' ? 'sale' : 'return');
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $data = $query->groupBy('phppos_items.item_id', 'phppos_items.name')
                    ->orderBy('qty_sold', 'desc')
                    ->limit(25)
                    ->get();

                $headers = ['Item Name', 'Qty Sold', 'Total Sales'];
                $title = "Top Sellers Report (Top 25)";
                break;

            case 'worse_sellers':
                $query = DB::table('phppos_sales_items')
                    ->join('phppos_sales', 'phppos_sales_items.sale_id', '=', 'phppos_sales.sale_id')
                    ->join('phppos_items', 'phppos_sales_items.item_id', '=', 'phppos_items.item_id')
                    ->selectRaw('phppos_items.name, SUM(phppos_sales_items.quantity_purchased) as qty_sold, SUM(phppos_sales_items.line_total) as total')
                    ->where('phppos_sales.deleted', 0);

                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                if ($saleType !== 'all') $query->where('phppos_sales.sale_type', $saleType === 'sales' ? 'sale' : 'return');
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $data = $query->groupBy('phppos_items.item_id', 'phppos_items.name')
                    ->orderBy('qty_sold', 'asc')
                    ->limit(25)
                    ->get();

                $headers = ['Item Name', 'Qty Sold', 'Total Sales'];
                $title = "Worse Sellers Report (Bottom 25)";
                break;

            case 'summary_items_variance':
                $query = DB::table('phppos_sales_items')
                    ->join('phppos_sales', 'phppos_sales_items.sale_id', '=', 'phppos_sales.sale_id')
                    ->join('phppos_items', 'phppos_sales_items.item_id', '=', 'phppos_items.item_id')
                    ->selectRaw('phppos_items.name, SUM(phppos_sales_items.quantity_purchased) as qty_sold, SUM(phppos_items.unit_price * phppos_sales_items.quantity_purchased) as expected_total, SUM(phppos_sales_items.line_total) as actual_total, SUM((phppos_items.unit_price * phppos_sales_items.quantity_purchased) - phppos_sales_items.line_total) as variance')
                    ->where('phppos_sales.deleted', 0);

                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $data = $query->groupBy('phppos_items.item_id', 'phppos_items.name')
                    ->orderBy('variance', 'desc')
                    ->get();

                $headers = ['Item Name', 'Qty Sold', 'Expected Total', 'Actual Total', 'Variance'];
                $title = "Summary Items Variance Report";
                break;

            case 'item_price_history':
                $query = DB::table('phppos_items_pricing_history')
                    ->join('phppos_items', 'phppos_items_pricing_history.item_id', '=', 'phppos_items.item_id')
                    ->leftJoin('phppos_people as employee', 'phppos_items_pricing_history.employee_id', '=', 'employee.person_id')
                    ->leftJoin('phppos_locations', 'phppos_items_pricing_history.location_id', '=', 'phppos_locations.location_id')
                    ->selectRaw('phppos_items_pricing_history.on_date as date, CONCAT(employee.first_name, " ", employee.last_name) as employee_name, phppos_items.name as item_name, phppos_locations.name as location_name, phppos_items_pricing_history.cost_price, phppos_items_pricing_history.unit_price')
                    ->whereBetween('phppos_items_pricing_history.on_date', [$startDateTime, $endDateTime]);

                if ($locationId !== 'all') {
                    $query->where(function($q) use ($locationId) {
                        $q->where('phppos_items_pricing_history.location_id', $locationId)
                          ->orWhereNull('phppos_items_pricing_history.location_id');
                    });
                }

                $data = $query->orderBy('phppos_items_pricing_history.on_date', 'desc')->get();

                $headers = ['Date', 'Employee', 'Item Name', 'Location', 'Cost Price', 'Unit Price'];
                $title = "Item Pricing History Report";
                break;

            case 'item_kit_price_history':
                $query = DB::table('phppos_item_kits_pricing_history')
                    ->join('phppos_item_kits', 'phppos_item_kits_pricing_history.item_kit_id', '=', 'phppos_item_kits.id')
                    ->leftJoin('phppos_people as employee', 'phppos_item_kits_pricing_history.employee_id', '=', 'employee.person_id')
                    ->leftJoin('phppos_locations', 'phppos_item_kits_pricing_history.location_id', '=', 'phppos_locations.location_id')
                    ->selectRaw('phppos_item_kits_pricing_history.on_date as date, CONCAT(employee.first_name, " ", employee.last_name) as employee_name, phppos_item_kits.name as item_name, phppos_locations.name as location_name, phppos_item_kits_pricing_history.cost_price, phppos_item_kits_pricing_history.unit_price')
                    ->whereBetween('phppos_item_kits_pricing_history.on_date', [$startDateTime, $endDateTime]);

                if ($locationId !== 'all') {
                    $query->where(function($q) use ($locationId) {
                        $q->where('phppos_item_kits_pricing_history.location_id', $locationId)
                          ->orWhereNull('phppos_item_kits_pricing_history.location_id');
                    });
                }

                $data = $query->orderBy('phppos_item_kits_pricing_history.on_date', 'desc')->get();

                $headers = ['Date', 'Employee', 'Item Kit Name', 'Location', 'Cost Price', 'Unit Price'];
                $title = "Item Kit Pricing History Report";
                break;

            case 'serial_number_history':
                $serialNumber = request('serial_number');

                $receivingsQuery = DB::table('phppos_receivings')
                    ->join('phppos_receivings_items', 'phppos_receivings.receiving_id', '=', 'phppos_receivings_items.receiving_id')
                    ->selectRaw("'receiving' as type, phppos_receivings.receiving_id as id, phppos_receivings.created_at as action_date")
                    ->whereRaw("TRIM(TRAILING '\n' FROM phppos_receivings_items.serialnumber) = ?", [$serialNumber]);

                $salesQuery = DB::table('phppos_sales')
                    ->join('phppos_sales_items', 'phppos_sales.sale_id', '=', 'phppos_sales_items.sale_id')
                    ->selectRaw("'sale' as type, phppos_sales.sale_id as id, phppos_sales.created_at as action_date")
                    ->whereRaw("TRIM(TRAILING '\n' FROM phppos_sales_items.serialnumber) = ?", [$serialNumber]);

                $data = $receivingsQuery->unionAll($salesQuery)->orderBy('action_date', 'asc')->get();

                $headers = ['Date', 'Type', 'ID'];
                $title = "Serial Number History Report";
                break;

            case 'serial_numbers_sold':
                $query = DB::table('phppos_sales_items')
                    ->join('phppos_sales', 'phppos_sales_items.sale_id', '=', 'phppos_sales.sale_id')
                    ->selectRaw('phppos_sales_items.serialnumber as serial_number, count(DISTINCT(phppos_sales_items.sale_id)) as count')
                    ->where('phppos_sales.deleted', 0)
                    ->whereNotNull('phppos_sales_items.serialnumber')
                    ->where('phppos_sales_items.serialnumber', '!=', '');

                if ($locationId !== 'all') $query->where('phppos_sales.location_id', $locationId);
                if ($saleType !== 'all') $query->where('phppos_sales.sale_type', $saleType === 'sales' ? 'sale' : 'return');
                $query->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime]);

                $data = $query->groupBy('phppos_sales_items.serialnumber')
                    ->orderBy('count', 'desc')
                    ->get();

                $headers = ['Serial Number', 'Count'];
                $title = "Serial Numbers Sold Report";
                break;

                        case 'graphical_summary_suppliers':
            case 'summary_suppliers':
                $query = DB::table('phppos_sales_items')
                    ->join('phppos_sales', 'phppos_sales_items.sale_id', '=', 'phppos_sales.sale_id')
                    ->join('phppos_items', 'phppos_sales_items.item_id', '=', 'phppos_items.item_id')
                    ->join('phppos_suppliers', 'phppos_items.supplier_id', '=', 'phppos_suppliers.person_id')
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

            case 'sales_generator':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Sales Generator Report (Under Construction)';
                break;

            case 'summary_appointments':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Summary Appointments Report (Under Construction)';
                break;

            case 'detailed_appointments':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Detailed Appointments Report (Under Construction)';
                break;

            case 'closeout':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Closeout Report (Under Construction)';
                break;

            case 'closeout_condensed':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Closeout Condensed Report (Under Construction)';
                break;

            case 'detailed_profit_and_loss':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Detailed Profit And Loss Report (Under Construction)';
                break;

            case 'transfers':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Transfers Report (Under Construction)';
                break;

            case 'detailed_suspended_receivings':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Detailed Suspended Receivings Report (Under Construction)';
                break;

            case 'deleted_receivings':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Deleted Receivings Report (Under Construction)';
                break;

            case 'summary_taxes_receivings':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Summary Taxes Receivings Report (Under Construction)';
                break;

            case 'graphical_summary_taxes_receivings':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Graphical Summary Taxes Receivings Report (Under Construction)';
                break;

            case 'cheapest_supplier':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Cheapest Supplier Report (Under Construction)';
                break;

            case 'graphical_summary_items_receivings':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Graphical Summary Items Receivings Report (Under Construction)';
                break;

            case 'summary_items_receivings':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Summary Items Receivings Report (Under Construction)';
                break;

            case 'receivings_graphical_summary_payments':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Receivings Graphical Summary Payments Report (Under Construction)';
                break;

            case 'receivings_summary_payments':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Receivings Summary Payments Report (Under Construction)';
                break;

            case 'receivings_detailed_payments':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Receivings Detailed Payments Report (Under Construction)';
                break;

            case 'detailed_register_log':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Detailed Register Log Report (Under Construction)';
                break;

            case 'store_account_statements':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Store Account Statements Report (Under Construction)';
                break;

            case 'summary_store_accounts':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Summary Store Accounts Report (Under Construction)';
                break;

            case 'specific_customer_store_account':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Specific Customer Store Account Report (Under Construction)';
                break;

            case 'store_account_activity':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Store Account Activity Report (Under Construction)';
                break;

            case 'store_account_activity_summary':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Store Account Activity Summary Report (Under Construction)';
                break;

            case 'store_account_outstanding':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Store Account Outstanding Report (Under Construction)';
                break;

            case 'supplier_store_account_statements':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Supplier Store Account Statements Report (Under Construction)';
                break;

            case 'supplier_summary_store_accounts':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Supplier Summary Store Accounts Report (Under Construction)';
                break;

            case 'supplier_specific_store_account':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Supplier Specific Store Account Report (Under Construction)';
                break;

            case 'supplier_store_account_activity':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Supplier Store Account Activity Report (Under Construction)';
                break;

            case 'supplier_store_account_activity_summary':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Supplier Store Account Activity Summary Report (Under Construction)';
                break;

            case 'supplier_store_account_outstanding':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Supplier Store Account Outstanding Report (Under Construction)';
                break;

            case 'specific_supplier_summary':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Specific Supplier Summary Report (Under Construction)';
                break;

            case 'graphical_summary_suppliers_receivings':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Graphical Summary Suppliers Receivings Report (Under Construction)';
                break;

            case 'summary_suppliers_receivings':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Summary Suppliers Receivings Report (Under Construction)';
                break;

            case 'specific_supplier_receivings':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Specific Supplier Receivings Report (Under Construction)';
                break;

            case 'layaway_statements':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Layaway Statements Report (Under Construction)';
                break;

            case 'summary_tiers':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Summary Tiers Report (Under Construction)';
                break;

            case 'time_off':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Time Off Report (Under Construction)';
                break;

            case 'summary_timeclock':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Summary Timeclock Report (Under Construction)';
                break;

            case 'detailed_timeclock':
                $data = collect([]);
                $headers = ['Notice'];
                $title = 'Detailed Timeclock Report (Under Construction)';
                break;

            case 'output_tax': {
                // ── Standard Rated: lines where vat > 0 ──────────────────────────────
                $standardQuery = DB::table('phppos_sales_items as si')
                    ->join('phppos_sales as s', 'si.sale_id', '=', 's.sale_id')
                    ->selectRaw('
                        SUM(si.line_total + si.vat) AS total_incl_vat,
                        SUM(si.vat)                 AS vat_amount
                    ')
                    ->where('s.deleted', 0)
                    ->where('si.vat', '>', 0)
                    ->whereBetween('s.created_at', [$startDateTime, $endDateTime])
                    ->where('s.location_id', $locationId)
                    ->first();

                // ── Zero Rated: lines that have a tax-class snapshot but vat = 0 ──────
                // (A snapshot row exists in phppos_sales_items_taxes → item had a tax class)
                $zeroRatedQuery = DB::table('phppos_sales_items as si')
                    ->join('phppos_sales as s', 'si.sale_id', '=', 's.sale_id')
                    ->whereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('phppos_sales_items_taxes as sit')
                            ->whereColumn('sit.sale_item_id', 'si.id');
                    })
                    ->selectRaw('
                        SUM(si.line_total + si.vat) AS total_incl_vat,
                        SUM(si.vat)                 AS vat_amount
                    ')
                    ->where('s.deleted', 0)
                    ->where('si.vat', '=', 0)
                    ->whereBetween('s.created_at', [$startDateTime, $endDateTime])
                    ->where('s.location_id', $locationId)
                    ->first();

                // ── Exempt: lines with no tax-class snapshot and vat = 0 ─────────────
                $exemptQuery = DB::table('phppos_sales_items as si')
                    ->join('phppos_sales as s', 'si.sale_id', '=', 's.sale_id')
                    ->whereNotExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('phppos_sales_items_taxes as sit')
                            ->whereColumn('sit.sale_item_id', 'si.id');
                    })
                    ->selectRaw('
                        SUM(si.line_total + si.vat) AS total_incl_vat,
                        SUM(si.vat)                 AS vat_amount
                    ')
                    ->where('s.deleted', 0)
                    ->where('si.vat', '=', 0)
                    ->whereBetween('s.created_at', [$startDateTime, $endDateTime])
                    ->where('s.location_id', $locationId)
                    ->first();

                $outputTaxData = [
                    'standard' => [
                        'total_incl_vat' => (float) ($standardQuery->total_incl_vat ?? 0),
                        'vat_amount'     => (float) ($standardQuery->vat_amount ?? 0),
                    ],
                    'zero_rated' => [
                        'total_incl_vat' => (float) ($zeroRatedQuery->total_incl_vat ?? 0),
                        'vat_amount'     => (float) ($zeroRatedQuery->vat_amount ?? 0),
                    ],
                    'exempt' => [
                        'total_incl_vat' => (float) ($exemptQuery->total_incl_vat ?? 0),
                        'vat_amount'     => (float) ($exemptQuery->vat_amount ?? 0),
                    ],
                ];

                // ── Input Tax Queries ───────────────────────────────────────────────
                $location = DB::table('phppos_locations')->where('location_id', $locationId)->first();
                $locationCountry = strtolower(trim($location->country ?? ''));

                $localCountries = array_filter(array_unique([
                    $locationCountry,
                    'saint vincent',
                    'saint vincent and the grenadines',
                    'st. vincent',
                    'st. vincent & the grenadines',
                    'svg'
                ]));

                // Imports query (where supplier country is NOT local)
                $importsQuery = DB::table('phppos_receivings_items as ri')
                    ->join('phppos_receivings as r', 'ri.receiving_id', '=', 'r.receiving_id')
                    ->join('phppos_suppliers as s', 'r.supplier_id', '=', 's.person_id')
                    ->join('phppos_people as p', 's.person_id', '=', 'p.person_id')
                    ->selectRaw('
                        SUM(ri.subtotal) AS total_excl_vat,
                        SUM(ri.vat) AS vat_amount
                    ')
                    ->where('r.deleted', 0)
                    ->whereBetween('r.receiving_time', [$startDateTime, $endDateTime])
                    ->where('r.location_id', $locationId)
                    ->where('p.country', '<>', '')
                    ->whereNotNull('p.country')
                    ->whereNotIn(DB::raw('LOWER(TRIM(p.country))'), $localCountries)
                    ->first();

                // Domestic query (where supplier is null OR country is local/empty)
                $domesticQuery = DB::table('phppos_receivings_items as ri')
                    ->join('phppos_receivings as r', 'ri.receiving_id', '=', 'r.receiving_id')
                    ->leftJoin('phppos_suppliers as s', 'r.supplier_id', '=', 's.person_id')
                    ->leftJoin('phppos_people as p', 's.person_id', '=', 'p.person_id')
                    ->selectRaw('
                        SUM(ri.subtotal) AS total_excl_vat,
                        SUM(ri.vat) AS vat_amount
                    ')
                    ->where('r.deleted', 0)
                    ->whereBetween('r.receiving_time', [$startDateTime, $endDateTime])
                    ->where('r.location_id', $locationId)
                    ->where(function ($query) use ($localCountries) {
                        $query->whereNull('r.supplier_id')
                            ->orWhereNull('p.country')
                            ->orWhere('p.country', '=', '')
                            ->orWhereIn(DB::raw('LOWER(TRIM(p.country))'), $localCountries);
                    })
                    ->first();

                $inputTaxData = [
                    'imports' => [
                        'total_excl_vat' => (float) ($importsQuery->total_excl_vat ?? 0),
                        'vat_amount'     => (float) ($importsQuery->vat_amount ?? 0),
                    ],
                    'domestic' => [
                        'total_excl_vat' => (float) ($domesticQuery->total_excl_vat ?? 0),
                        'vat_amount'     => (float) ($domesticQuery->vat_amount ?? 0),
                    ],
                ];

                $title = 'VAT Report (Output & Input Tax)';

                return view('reports.output_tax', compact(
                    'outputTaxData', 'inputTaxData', 'title', 'startDate', 'endDate', 'report'
                ));
            }

            default:
                return redirect()->back()->with('error', 'Report type not implemented yet.');
        }

        return view('reports.tabular', compact('data', 'headers', 'title', 'startDate', 'endDate', 'report'));
    }

    public function vatIndex(): View
    {
        $locationId = $this->locationContextService->resolveLocationId(null);

        $monthlyOutput = DB::table('phppos_sales_items as si')
            ->join('phppos_sales as s', 'si.sale_id', '=', 's.sale_id')
            ->selectRaw('
                CAST(strftime(\'%Y\', s.created_at) AS INTEGER) as yr,
                CAST(strftime(\'%m\', s.created_at) AS INTEGER) as mo,
                SUM(si.vat) as output_vat
            ')
            ->where('s.deleted', 0)
            ->where('s.location_id', $locationId)
            ->groupBy('yr', 'mo')
            ->orderBy('yr', 'desc')
            ->orderBy('mo', 'desc')
            ->get()
            ->keyBy(fn($r) => $r->yr . '-' . str_pad($r->mo, 2, '0', STR_PAD_LEFT));

        $monthlyInput = DB::table('phppos_receivings_items as ri')
            ->join('phppos_receivings as r', 'ri.receiving_id', '=', 'r.receiving_id')
            ->selectRaw('
                CAST(strftime(\'%Y\', r.receiving_time) AS INTEGER) as yr,
                CAST(strftime(\'%m\', r.receiving_time) AS INTEGER) as mo,
                SUM(ri.vat) as input_vat
            ')
            ->where('r.deleted', 0)
            ->where('r.location_id', $locationId)
            ->groupBy('yr', 'mo')
            ->orderBy('yr', 'desc')
            ->orderBy('mo', 'desc')
            ->get()
            ->keyBy(fn($r) => $r->yr . '-' . str_pad($r->mo, 2, '0', STR_PAD_LEFT));

        $keys = array_unique(array_merge($monthlyOutput->keys()->all(), $monthlyInput->keys()->all()));
        rsort($keys);

        $months = [];
        foreach ($keys as $key) {
            [$yr, $mo] = explode('-', $key);
            $outputVat = (float) ($monthlyOutput->get($key)?->output_vat ?? 0);
            $inputVat  = (float) ($monthlyInput->get($key)?->input_vat ?? 0);
            $months[] = (object) [
                'year'       => (int) $yr,
                'month'      => (int) $mo,
                'label'      => Carbon::create((int) $yr, (int) $mo, 1)->format('F Y'),
                'start_date' => Carbon::create((int) $yr, (int) $mo, 1)->format('Y-m-d'),
                'end_date'   => Carbon::create((int) $yr, (int) $mo, 1)->endOfMonth()->format('Y-m-d'),
                'output_vat' => $outputVat,
                'input_vat'  => $inputVat,
                'net_vat'    => $outputVat - $inputVat,
            ];
        }

        return view('reports.vat_index', compact('months'));
    }

    private function resolveSimpleDateRange(string $simpleKey): array
    {
        $simpleKey = strtoupper(trim($simpleKey));
        $today = Carbon::today();

        $start = match ($simpleKey) {
            'TODAY' => $today->copy(),
            'YESTERDAY' => $today->copy()->subDay(),
            'LAST_7' => $today->copy()->subDays(6),
            'LAST_30' => $today->copy()->subDays(29),
            'THIS_WEEK' => $today->copy()->startOfWeek(),
            'LAST_WEEK' => $today->copy()->subWeek()->startOfWeek(),
            'THIS_MONTH' => $today->copy()->startOfMonth(),
            'LAST_MONTH' => $today->copy()->subMonthNoOverflow()->startOfMonth(),
            'THIS_QUARTER' => $today->copy()->startOfQuarter(),
            'LAST_QUARTER' => $today->copy()->subQuarter()->startOfQuarter(),
            'THIS_YEAR' => $today->copy()->startOfYear(),
            'LAST_YEAR' => $today->copy()->subYear()->startOfYear(),
            'ALL_TIME' => Carbon::create(2000, 1, 1),
            default => $today->copy(),
        };

        $end = match ($simpleKey) {
            'YESTERDAY' => $today->copy()->subDay(),
            'THIS_WEEK' => $today->copy()->endOfWeek(),
            'LAST_WEEK' => $today->copy()->subWeek()->endOfWeek(),
            'THIS_MONTH' => $today->copy()->endOfMonth(),
            'LAST_MONTH' => $today->copy()->subMonthNoOverflow()->endOfMonth(),
            'THIS_QUARTER' => $today->copy()->endOfQuarter(),
            'LAST_QUARTER' => $today->copy()->subQuarter()->endOfQuarter(),
            'THIS_YEAR' => $today->copy()->endOfYear(),
            'LAST_YEAR' => $today->copy()->subYear()->endOfYear(),
            default => $today->copy(),
        };

        return [$start->toDateString(), $end->toDateString()];
    }
}
