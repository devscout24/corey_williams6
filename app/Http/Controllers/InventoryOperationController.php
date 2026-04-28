<?php

namespace App\Http\Controllers;

use App\Models\PhpposItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Throwable;
use App\Services\InventoryFlowService;

class InventoryOperationController extends Controller
{
    public function __construct(private readonly InventoryFlowService $inventoryFlowService)
    {
    }

    public function index(): View
    {
        $locations = DB::table('phppos_locations')
            ->where('deleted', 0)
            ->orderBy('location_id')
            ->get();

        $items = PhpposItem::query()
            ->where('deleted', 0)
            ->orderBy('name')
            ->get();

        $recentMovements = DB::table('phppos_inventory_movements as m')
            ->leftJoin('phppos_items as i', 'i.item_id', '=', 'm.item_id')
            ->leftJoin('phppos_locations as fl', 'fl.location_id', '=', 'm.from_location_id')
            ->leftJoin('phppos_locations as tl', 'tl.location_id', '=', 'm.to_location_id')
            ->select('m.*', 'i.name as item_name', 'fl.name as from_location_name', 'tl.name as to_location_name')
            ->orderByDesc('m.id')
            ->limit(30)
            ->get();

        $recentTransfers = DB::table('phppos_transfers')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view('inventory.operations', compact('locations', 'items', 'recentMovements', 'recentTransfers'));
    }

    public function storeReceiving(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'location_id' => ['required', 'integer', 'exists:phppos_locations,location_id'],
            'item_id' => ['required', 'integer', 'exists:phppos_items,item_id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->inventoryFlowService->receive(
                (int) $data['location_id'],
                (int) $data['item_id'],
                (float) $data['quantity'],
                auth('employee')->id(),
                $data['notes'] ?? null,
            );

            return redirect()->route('inventory.operations')->with('status', 'Receiving posted successfully.');
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['receiving' => $e->getMessage()]);
        }
    }

    public function storeReturn(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'location_id' => ['required', 'integer', 'exists:phppos_locations,location_id'],
            'item_id' => ['required', 'integer', 'exists:phppos_items,item_id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->inventoryFlowService->returnFromInventory(
                (int) $data['location_id'],
                (int) $data['item_id'],
                (float) $data['quantity'],
                auth('employee')->id(),
                $data['notes'] ?? null,
            );

            return redirect()->route('inventory.operations')->with('status', 'Return posted successfully. Inventory reduced.');
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['return' => $e->getMessage()]);
        }
    }

    public function storeTransferOut(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_location_id' => ['required', 'integer', 'exists:phppos_locations,location_id'],
            'to_location_id' => ['required', 'integer', 'exists:phppos_locations,location_id', 'different:from_location_id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:phppos_items,item_id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $lines = collect($data['lines'])
            ->map(static fn ($line): array => [
                'item_id' => (int) $line['item_id'],
                'quantity' => (float) $line['quantity'],
            ])
            ->filter(static fn ($line): bool => $line['quantity'] > 0)
            ->values()
            ->all();

        if (empty($lines)) {
            return back()->withInput()->withErrors(['transfer' => 'At least one transfer line with quantity > 0 is required.']);
        }

        try {
            $result = $this->inventoryFlowService->transferOutAndAutoIn(
                (int) $data['from_location_id'],
                (int) $data['to_location_id'],
                $lines,
                auth('employee')->id(),
                $data['notes'] ?? null,
            );

            return redirect()->route('inventory.operations')
                ->with('status', 'Transfer out #'.$result['transfer_out_id'].' closed. Auto transfer in #'.$result['transfer_in_id'].' created and closed.');
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['transfer' => $e->getMessage()]);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['transfer' => 'Transfer failed: '.$e->getMessage()]);
        }
    }
}
