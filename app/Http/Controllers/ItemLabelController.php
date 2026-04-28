<?php

namespace App\Http\Controllers;

use App\Models\PhpposAppFile;
use App\Models\PhpposItem;
use App\Models\PhpposItemKit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

use App\Services\AppConfigService;

class ItemLabelController extends Controller
{
    public function __construct(private readonly AppConfigService $configService)
    {
    }
    public function index(): View
    {
        $items = PhpposItem::query()
            ->where('deleted', 0)
            ->orderBy('name')
            ->limit(200)
            ->get();

        return view('labels.index', [
            'items' => $items,
            'sheetBackground' => $this->configService->get('label_sheet_background'),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->get('q', ''));
        if ($term === '') {
            return response()->json([]);
        }

        $items = PhpposItem::query()
            ->where('deleted', 0)
            ->where('name', 'like', '%'.$term.'%')
            ->orderBy('name')
            ->limit(10)
            ->get(['item_id', 'name', 'unit_price', 'item_number']);

        $kits = PhpposItemKit::query()
            ->where('deleted', 0)
            ->where('name', 'like', '%'.$term.'%')
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'unit_price', 'item_kit_number']);

        $results = [];
        foreach ($items as $item) {
            $results[] = [
                'type' => 'item',
                'id' => $item->item_id,
                'label' => $item->name,
                'price' => number_format((float) $item->unit_price, 2),
                'code' => $item->item_number,
            ];
        }

        foreach ($kits as $kit) {
            $results[] = [
                'type' => 'kit',
                'id' => $kit->id,
                'label' => $kit->name.' (Kit)',
                'price' => number_format((float) $kit->unit_price, 2),
                'code' => $kit->item_kit_number,
            ];
        }

        return response()->json($results);
    }

    public function print(Request $request): View
    {
        $validated = $request->validate([
            'mode' => ['required', 'in:barcode,sheet'],
            'logo_width_mm' => ['nullable', 'numeric', 'min:5', 'max:200'],
            'logo_height_mm' => ['nullable', 'numeric', 'min:5', 'max:200'],
            'sheet_background' => ['nullable', 'image', 'max:4096'],
            'sheet_opacity' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['nullable', 'in:item,kit'],
            'items.*.item_id' => ['nullable', 'integer', 'exists:phppos_items,item_id'],
            'items.*.item_kit_id' => ['nullable', 'integer', 'exists:phppos_item_kits,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $sheetBackground = $this->configService->get('label_sheet_background');
        if ($request->hasFile('sheet_background')) {
            $file = $request->file('sheet_background');
            $fileData = file_get_contents($file->getRealPath());

            $appFile = PhpposAppFile::create([
                'file_name' => $file->getClientOriginalName(),
                'file_data' => $fileData,
            ]);

            $sheetBackground = $appFile->file_id;
            $this->configService->save('label_sheet_background', (string) $sheetBackground);
        }

        $entries = collect($validated['items'])->filter(function (array $entry): bool {
            return ! empty($entry['item_id']) || ! empty($entry['item_kit_id']);
        })->values();

        if ($entries->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Select at least one item or kit.',
            ]);
        }

        $itemIds = $entries->pluck('item_id')->filter()->all();
        $kitIds = $entries->pluck('item_kit_id')->filter()->all();

        $items = PhpposItem::query()->whereIn('item_id', $itemIds)->get()->keyBy('item_id');
        $kits = PhpposItemKit::query()->whereIn('id', $kitIds)->get()->keyBy('id');

        $labels = [];
        foreach ($entries as $entry) {
            if (! empty($entry['item_id'])) {
                $item = $items->get($entry['item_id']);
                if (! $item) {
                    continue;
                }
                for ($i = 0; $i < $entry['quantity']; $i++) {
                    $labels[] = [
                        'item_id' => $item->item_id,
                        'name' => $item->name,
                        'price' => number_format((float) $item->unit_price, 2),
                        'barcode_value' => $item->item_number ?: 'ITEM-'.$item->item_id,
                    ];
                }
                continue;
            }

            if (! empty($entry['item_kit_id'])) {
                $kit = $kits->get($entry['item_kit_id']);
                if (! $kit) {
                    continue;
                }
                for ($i = 0; $i < $entry['quantity']; $i++) {
                    $labels[] = [
                        'item_id' => $kit->id,
                        'name' => $kit->name,
                        'price' => number_format((float) $kit->unit_price, 2),
                        'barcode_value' => $kit->item_kit_number ?: 'KIT-'.$kit->id,
                    ];
                }
            }
        }

        DB::table('phppos_item_label_jobs')->insert([
            'mode' => $validated['mode'],
            'employee_person_id' => auth('employee')->id(),
            'logo_width_mm' => $validated['logo_width_mm'] ?? null,
            'logo_height_mm' => $validated['logo_height_mm'] ?? null,
            'payload' => json_encode([
                'items' => $validated['items'],
                'labels_count' => count($labels),
            ], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return view('labels.print', [
            'mode' => $validated['mode'],
            'logoWidthMm' => $validated['logo_width_mm'] ?? null,
            'logoHeightMm' => $validated['logo_height_mm'] ?? null,
            'sheetOpacity' => $validated['sheet_opacity'] ?? null,
            'labels' => $labels,
            'companyLogo' => $this->configService->get('company_logo'),
            'barcodeBackground' => $this->configService->get('barcode_background'),
            'sheetBackground' => $sheetBackground,
            'showCompanyOnBarcode' => (bool) $this->configService->get('show_barcode_company_name'),
            'hideBarcodeOnLabels' => (bool) $this->configService->get('hide_barcode_on_barcode_labels'),
            'barcodeType' => (string) ($this->configService->get('barcode_type') ?: 'Code128'),
            'barcodeWidth' => (float) ($this->configService->get('barcode_width') ?: 1.5),
            'barcodeHeight' => (float) ($this->configService->get('barcode_height') ?: 36),
            'barcodeFontSize' => (int) ($this->configService->get('barcode_font_size') ?: 12),
            'companyName' => (string) ($this->configService->get('company') ?: ''),
        ]);
    }
}
