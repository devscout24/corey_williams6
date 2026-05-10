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
        // For now, we'll just handle summary_sales as a starting point
        $title = str_replace('_', ' ', ucfirst($report));
        return view('reports.generate', compact('report', 'title'));
    }

    public function store(Request $request, string $report)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime = $endDate . ' 23:59:59';

        switch ($report) {
            case 'summary_sales':
                $data = DB::table('phppos_sales')
                    ->selectRaw('DATE(created_at) as sale_date, SUM(subtotal) as subtotal, SUM(total) as total, COUNT(*) as sales_count')
                    ->whereBetween('created_at', [$startDateTime, $endDateTime])
                    ->groupBy('sale_date')
                    ->orderBy('sale_date', 'desc')
                    ->get();

                $headers = ['Date', 'Sales Count', 'Subtotal', 'Total'];
                $title = "Summary Sales Report";
                break;

            case 'summary_items':
                $data = DB::table('phppos_sales_items')
                    ->join('phppos_items', 'phppos_sales_items.item_id', '=', 'phppos_items.item_id')
                    ->selectRaw('phppos_items.name, SUM(phppos_sales_items.quantity_purchased) as qty_sold, SUM(phppos_sales_items.line_total) as total')
                    ->whereBetween('phppos_sales_items.created_at', [$startDateTime, $endDateTime])
                    ->groupBy('phppos_items.item_id', 'phppos_items.name')
                    ->orderBy('total', 'desc')
                    ->get();

                $headers = ['Item Name', 'Quantity Sold', 'Total Sales'];
                $title = "Summary Items Report";
                break;

            case 'summary_customers':
                $data = DB::table('phppos_sales')
                    ->selectRaw('COALESCE(customer_name, "Walk-in") as customer, COUNT(*) as sales_count, SUM(total) as total')
                    ->whereBetween('created_at', [$startDateTime, $endDateTime])
                    ->groupBy('customer_id', 'customer_name')
                    ->orderBy('total', 'desc')
                    ->get();

                $headers = ['Customer', 'Sales Count', 'Total Sales'];
                $title = "Summary Customers Report";
                break;

            case 'summary_employees':
                $data = DB::table('phppos_sales')
                    ->join('phppos_employees', 'phppos_sales.employee_id', '=', 'phppos_employees.person_id')
                    ->selectRaw('phppos_employees.username as employee, COUNT(*) as sales_count, SUM(total) as total')
                    ->whereBetween('phppos_sales.created_at', [$startDateTime, $endDateTime])
                    ->groupBy('phppos_employees.person_id', 'phppos_employees.username')
                    ->orderBy('total', 'desc')
                    ->get();

                $headers = ['Employee', 'Sales Count', 'Total Sales'];
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
