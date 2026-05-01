<?php

namespace App\Http\Controllers;

use App\Models\PhpposModule;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function index(): View
    {
        $modules = PhpposModule::query()
            ->whereIn('module_id', ['locations', 'contacts', 'items', 'receivings', 'sales', 'messages', 'config', 'employees'])
            ->with('submodules')
            ->orderBy('sort')
            ->get();

        // Fetch Sales Information (Graphical)
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfDay();

        $monthlySales = \Illuminate\Support\Facades\DB::table('phppos_sales')
            ->selectRaw('DATE(created_at) as sale_date, SUM(total) as sale_amount')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get();

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        $weeklySales = \Illuminate\Support\Facades\DB::table('phppos_sales')
            ->selectRaw('DATE(created_at) as sale_date, SUM(total) as sale_amount')
            ->whereBetween('created_at', [$weekStart, $weekEnd])
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get();

        // Yearly Sales (Monthly totals for the current year)
        $yearStart = now()->startOfYear();
        $yearEnd = now()->endOfYear();

        $yearlySales = \Illuminate\Support\Facades\DB::table('phppos_sales')
            ->selectRaw('MONTH(created_at) as month, SUM(total) as sale_amount')
            ->whereBetween('created_at', [$yearStart, $yearEnd])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Dashboard Stats
        $stats = [
            'total_sales' => \Illuminate\Support\Facades\DB::table('phppos_sales')->count(),
            'total_customers' => \Illuminate\Support\Facades\DB::table('phppos_customers')->count(),
            'total_items' => \Illuminate\Support\Facades\DB::table('phppos_items')->where('deleted', 0)->count(),
            'total_item_kits' => \Illuminate\Support\Facades\DB::table('phppos_item_kits')->where('deleted', 0)->count(),
        ];

        return view('modules.index', compact('modules', 'monthlySales', 'weeklySales', 'yearlySales', 'stats'));
    }
}
