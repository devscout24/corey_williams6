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

        // Logic for summary_sales
        if ($report === 'summary_sales') {
            $data = DB::table('phppos_sales')
                ->selectRaw('DATE(created_at) as sale_date, SUM(subtotal) as subtotal, SUM(total) as total, COUNT(*) as items_purchased')
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->groupBy('sale_date')
                ->orderBy('sale_date', 'desc')
                ->get();

            $headers = ['Date', 'Items Purchased', 'Subtotal', 'Total'];
            $title = "Summary Sales Report";
            
            return view('reports.tabular', compact('data', 'headers', 'title', 'startDate', 'endDate', 'report'));
        }

        return redirect()->back()->with('error', 'Report type not implemented yet.');
    }
}
