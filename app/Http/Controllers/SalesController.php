<?php

namespace App\Http\Controllers;

use App\Models\PhpposItem;
use App\Services\SalesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class SalesController extends Controller
{
    public function __construct(private readonly SalesService $salesService)
    {
    }

    public function index(): View
    {
        $locations = DB::table('phppos_locations')->where('deleted', 0)->orderBy('location_id')->get();
        $items = PhpposItem::query()->where('deleted', 0)->orderBy('name')->get();

        $recentSales = DB::table('phppos_sales')
            ->orderByDesc('sale_id')
            ->limit(20)
            ->get();

        return view('sales.index', compact('locations', 'items', 'recentSales'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'location_id' => ['required', 'integer', 'exists:phppos_locations,location_id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:phppos_items,item_id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $saleId = $this->salesService->createSale(
                (int) $data['location_id'],
                (int) auth('employee')->id(),
                $data['lines'],
                $data['customer_name'] ?? null,
                $data['comment'] ?? null,
            );

            return redirect()->route('sales.receipt', ['sale' => $saleId])
                ->with('status', 'Sale #'.$saleId.' completed.');
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['sale' => $e->getMessage()]);
        }
    }

    public function receipt(int $sale): View
    {
        $saleRow = DB::table('phppos_sales as s')
            ->join('phppos_locations as l', 'l.location_id', '=', 's.location_id')
            ->join('phppos_people as p', 'p.person_id', '=', 's.employee_id')
            ->select('s.*', 'l.name as location_name', 'p.first_name', 'p.last_name')
            ->where('s.sale_id', $sale)
            ->firstOrFail();

        $lines = DB::table('phppos_sales_items as si')
            ->join('phppos_items as i', 'i.item_id', '=', 'si.item_id')
            ->select('si.*', 'i.name as item_name', 'i.item_number')
            ->where('si.sale_id', $sale)
            ->get()
            ->map(static function ($row) use ($sale): array {
                $arr = (array) $row;
                $returnedQty = (float) DB::table('phppos_sales_item_returns')
                    ->where('sale_item_id', $arr['id'])
                    ->sum('quantity_returned');

                $arr['returned_qty'] = $returnedQty;
                $arr['returnable_qty'] = max(0, (float) $arr['quantity_purchased'] - $returnedQty);

                return $arr;
            })
            ->values()
            ->all();

        $payments = DB::table('phppos_sales_payments')
            ->where('sale_id', $sale)
            ->get()
            ->map(static fn ($row): array => (array) $row)
            ->values()
            ->all();

        $settings = DB::table('phppos_receipt_settings')->where('id', 1)->first();

        return view('sales.receipt', [
            'sale' => $saleRow,
            'lines' => $lines,
            'payments' => $payments,
            'settings' => $settings,
        ]);
    }

    public function returnItems(Request $request, int $sale): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
            'returns' => ['required', 'array', 'min:1'],
            'returns.*.sale_item_id' => ['required', 'integer', 'exists:phppos_sales_items,id'],
            'returns.*.quantity' => ['required', 'numeric', 'min:0'],
        ]);

        $lines = collect($data['returns'])
            ->map(static fn (array $row): array => [
                'sale_item_id' => (int) $row['sale_item_id'],
                'quantity' => (float) $row['quantity'],
            ])
            ->filter(static fn (array $row): bool => $row['quantity'] > 0)
            ->values()
            ->all();

        if (empty($lines)) {
            return back()->withErrors(['return' => 'Enter at least one quantity greater than 0.']);
        }

        try {
            $this->salesService->returnSaleItems($sale, (int) auth('employee')->id(), $lines, $data['reason'] ?? null);

            return redirect()->route('sales.receipt', ['sale' => $sale])->with('status', 'Return posted successfully.');
        } catch (Throwable $e) {
            return back()->withErrors(['return' => $e->getMessage()]);
        }
    }

    public function settings(): View
    {
        $settings = DB::table('phppos_receipt_settings')->where('id', 1)->first();

        return view('sales.settings', compact('settings'));
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'footer' => ['required', 'string', 'max:255'],
            'paper_size' => ['required', 'string', 'max:20'],
            'show_cashier' => ['nullable', 'boolean'],
            'show_customer' => ['nullable', 'boolean'],
        ]);

        DB::table('phppos_receipt_settings')->updateOrInsert(
            ['id' => 1],
            [
                'title' => $data['title'],
                'footer' => $data['footer'],
                'paper_size' => $data['paper_size'],
                'show_cashier' => (bool) ($data['show_cashier'] ?? false),
                'show_customer' => (bool) ($data['show_customer'] ?? false),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return redirect()->route('sales.settings')->with('status', 'Receipt settings updated.');
    }
}
