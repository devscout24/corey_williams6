<?php

namespace App\Http\Controllers;

use App\Models\PhpposModule;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function index(): View
    {
        $employee = auth('employee')->user();

        $modules = PhpposModule::query()
            ->whereIn('module_id', ['locations', 'contacts', 'items', 'receivings', 'sales', 'reports', 'reconciliation', 'messages', 'employees', 'config'])
            ->with('submodules')
            ->orderBy('sort')
            ->get()
            ->filter(fn ($m) => $employee?->hasModulePermission($m->module_id))
            ->values();

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

        $isSqlite = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite';
        $monthSelector = $isSqlite ? 'strftime("%m", created_at)' : 'MONTH(created_at)';

        $yearlySales = \Illuminate\Support\Facades\DB::table('phppos_sales')
            ->selectRaw("{$monthSelector} as month, SUM(total) as sale_amount")
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
            'total_sales_amount' => \Illuminate\Support\Facades\DB::table('phppos_sales')->sum('total'),
            'total_locations' => \Illuminate\Support\Facades\DB::table('phppos_locations')->where('deleted', 0)->count(),
            'total_employees' => \Illuminate\Support\Facades\DB::table('phppos_employees')->where('deleted', 0)->count(),
        ];

        // Recent Sales
        $recentSales = \Illuminate\Support\Facades\DB::table('phppos_sales')
            ->join('phppos_customers', 'phppos_sales.customer_id', '=', 'phppos_customers.person_id', 'left')
            ->join('phppos_people', 'phppos_customers.person_id', '=', 'phppos_people.person_id', 'left')
            ->select('phppos_sales.*', 'phppos_people.first_name', 'phppos_people.last_name')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('modules.index', compact('modules', 'monthlySales', 'weeklySales', 'yearlySales', 'stats', 'recentSales'));
    }
}
