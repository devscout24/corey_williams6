<?php

namespace App\Http\Controllers;

use App\Models\PhpposCategory;
use App\Models\PhpposItem;
use App\Models\PhpposItemKit;
use App\Models\PhpposItemKitItem;
use App\Models\PhpposItemKitItemKit;
use App\Models\PhpposItemKitTax;
use App\Models\PhpposTaxClass;
use App\Models\PhpposAppFile;
use App\Models\PhpposTag;
use App\Models\PhpposSupplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ItemKitController extends Controller
{
    public function index(): View
    {
        $kits = PhpposItemKit::query()
            ->with(['category', 'supplier'])
            ->where('deleted', 0)
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('item_kits.index', compact('kits'));
    }

    public function create(): View
    {
        return $this->form(null);
    }

    public function edit(int $kitId): View
    {
        return $this->form($kitId);
    }

    private function form(?int $kitId): View
    {
        $kit = $kitId ? PhpposItemKit::with(['items', 'taxes', 'nestedKits', 'supplier'])->findOrFail($kitId) : null;
        
        $categories = PhpposCategory::query()->where('deleted', 0)->orderBy('name')->get();
        $suppliers = PhpposSupplier::query()->where('deleted', 0)->orderBy('company_name')->get();
        $taxClasses = PhpposTaxClass::query()->where('deleted', 0)->orderBy('name')->get();
        $secondarySuppliers = $kitId
            ? DB::table('phppos_item_kits_secondary_suppliers')->where('item_kit_id', $kitId)->get()->all()
            : [];
        $secondaryCategories = $kitId
            ? DB::table('phppos_item_kits_secondary_categories')->where('item_kit_id', $kitId)->get()->all()
            : [];
        
        // Tags
        $tags = $kitId ? DB::table('phppos_item_kits_tags as ikt')
            ->join('phppos_tags as t', 't.id', '=', 'ikt.tag_id')
            ->where('ikt.item_kit_id', $kitId)
            ->pluck('t.name')
            ->toArray() : [];

        // All items and kits for the selection dropdown/autocomplete
        // In a real app, this should be an autocomplete endpoint, but keeping parity for now
        $allItems = PhpposItem::query()->where('deleted', 0)->orderBy('name')->get();
        $allKits = PhpposItemKit::query()->where('deleted', 0)->where('id', '!=', $kitId)->orderBy('name')->get();

        return view('item_kits.form', [
            'kit' => $kit,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'taxClasses' => $taxClasses,
            'tags' => implode(',', $tags),
            'allItems' => $allItems,
            'allKits' => $allKits,
            'kitItems' => $kit ? $kit->items : [],
            'nestedKits' => $kit ? $kit->nestedKits : [],
            'secondary_suppliers' => $secondarySuppliers,
            'secondary_categories' => $secondaryCategories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->saveKit($request, null);
    }

    public function update(Request $request, int $kitId): RedirectResponse
    {
        return $this->saveKit($request, $kitId);
    }

    public function destroy(int $kitId): RedirectResponse
    {
        PhpposItemKit::query()->where('id', $kitId)->update(['deleted' => 1]);
        return redirect()->route('item-kits.index')->with('status', 'Item kit archived.');
    }

    public function quickUpdate(Request $request, int $kitId): JsonResponse
    {
        $data = $request->validate([
            'cost_price' => ['nullable', 'numeric'],
            'unit_price' => ['nullable', 'numeric'],
            'quantity' => ['nullable', 'numeric'],
            'reorder_level' => ['nullable', 'numeric'],
        ]);

        $payload = [];

        if ($request->has('cost_price')) {
            $payload['cost_price'] = $data['cost_price'];
        }
        if ($request->has('unit_price')) {
            $payload['unit_price'] = $data['unit_price'];
        }
        if ($request->has('reorder_level')) {
            $payload['reorder_level'] = $data['reorder_level'];
        }
        if ($request->has('quantity')) {
            $payload['default_quantity'] = $data['quantity'];
        }

        if (!empty($payload)) {
            PhpposItemKit::query()->where('id', $kitId)->update($payload);
        }

        return response()->json(['status' => 'ok']);
    }

    private function saveKit(Request $request, ?int $kitId): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'item_kit_number' => ['nullable', 'string', 'max:255'],
            'product_id' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer'],
            'supplier_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'info_popup' => ['nullable', 'string'],
            'unit_price' => ['nullable', 'numeric'],
            'cost_price' => ['nullable', 'numeric'],
            'tax_included' => ['nullable', 'boolean'],
            'override_default_tax' => ['nullable', 'boolean'],
            'tax_class_id' => ['nullable', 'integer'],
            'is_ebt_item' => ['nullable', 'boolean'],
            'verify_age' => ['nullable', 'boolean'],
            'required_age' => ['nullable', 'integer'],
            'commission_percent' => ['nullable', 'numeric'],
            'commission_percent_type' => ['nullable', 'string'],
            'commission_fixed' => ['nullable', 'numeric'],
            'change_cost_price' => ['nullable', 'boolean'],
            'disable_loyalty' => ['nullable', 'boolean'],
            'max_discount_percent' => ['nullable', 'numeric'],
            'max_edit_price' => ['nullable', 'numeric'],
            'min_edit_price' => ['nullable', 'numeric'],
            'default_quantity' => ['nullable', 'numeric'],
            'reorder_level' => ['nullable', 'numeric'],
            'dynamic_pricing' => ['nullable', 'boolean'],
            'is_favorite' => ['nullable', 'boolean'],
            'loyalty_multiplier' => ['nullable', 'numeric'],
            'tags' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'secondary_suppliers' => ['nullable', 'array'],
            'secondary_categories' => ['nullable', 'array'],
            
            // Items and Nested Kits
            'kit_items' => ['nullable', 'array'],
            'kit_items.*.item_id' => ['nullable', 'integer'],
            'kit_items.*.quantity' => ['nullable', 'numeric'],
            
            'nested_kits' => ['nullable', 'array'],
            'nested_kits.*.item_kit_id' => ['nullable', 'integer'],
            'nested_kits.*.quantity' => ['nullable', 'numeric'],

            // Taxes
            'tax_names' => ['nullable', 'array'],
            'tax_percents' => ['nullable', 'array'],
            'tax_cumulatives' => ['nullable', 'array'],
        ]);

        // Custom fields
        for ($i = 1; $i <= 10; $i++) {
            $data["custom_field_{$i}_value"] = $request->input("custom_field_{$i}_value");
        }

        DB::transaction(function () use ($data, $kitId, $request): void {
            $payload = [
                'name' => $data['name'],
                'item_kit_number' => $data['item_kit_number'] ?? null,
                'product_id' => $data['product_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'description' => $data['description'] ?? null,
                'info_popup' => $data['info_popup'] ?? null,
                'unit_price' => $data['unit_price'] ?? 0,
                'cost_price' => $data['cost_price'] ?? 0,
                // 'tax_included' => !empty($data['tax_included']) ? 1 : 0,
                'tax_included' => 1,
                'override_default_tax' => !empty($data['override_default_tax']) ? 1 : 0,
                'tax_class_id' => $data['tax_class_id'] ?? null,
                'is_ebt_item' => !empty($data['is_ebt_item']) ? 1 : 0,
                'verify_age' => !empty($data['verify_age']) ? 1 : 0,
                'required_age' => !empty($data['verify_age']) ? ($data['required_age'] ?? null) : null,
                'commission_percent' => $data['commission_percent'] ?? null,
                'commission_percent_type' => $data['commission_percent_type'] ?? 'profit',
                'commission_fixed' => $data['commission_fixed'] ?? null,
                'change_cost_price' => !empty($data['change_cost_price']) ? 1 : 0,
                'disable_loyalty' => !empty($data['disable_loyalty']) ? 1 : 0,
                'max_discount_percent' => $data['max_discount_percent'] ?? null,
                'max_edit_price' => $data['max_edit_price'] ?? null,
                'min_edit_price' => $data['min_edit_price'] ?? null,
                'default_quantity' => $data['default_quantity'] ?? null,
                'reorder_level' => $data['reorder_level'] ?? null,
                'dynamic_pricing' => !empty($data['dynamic_pricing']) ? 1 : 0,
                'is_favorite' => !empty($data['is_favorite']) ? 1 : 0,
                'loyalty_multiplier' => $data['loyalty_multiplier'] ?? null,
                'deleted' => 0,
            ];

            for ($i = 1; $i <= 10; $i++) {
                $payload["custom_field_{$i}_value"] = $data["custom_field_{$i}_value"] ?? null;
            }

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $appFile = PhpposAppFile::create([
                    'file_name' => $file->getClientOriginalName(),
                    'file_data' => file_get_contents($file->getRealPath()),
                    'timestamp' => now(),
                ]);
                $payload['main_image_id'] = $appFile->file_id;
            }

            if ($kitId) {
                PhpposItemKit::query()->where('id', $kitId)->update($payload);
            } else {
                $kit = PhpposItemKit::query()->create($payload);
                $kitId = (int) $kit->id;
            }

            DB::table('phppos_item_kits_secondary_suppliers')->where('item_kit_id', $kitId)->delete();
            if (!empty($data['secondary_suppliers'])) {
                foreach (array_filter($data['secondary_suppliers']) as $secSupId) {
                    DB::table('phppos_item_kits_secondary_suppliers')->insert([
                        'item_kit_id' => $kitId,
                        'supplier_id' => $secSupId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::table('phppos_item_kits_secondary_categories')->where('item_kit_id', $kitId)->delete();
            if (!empty($data['secondary_categories'])) {
                foreach (array_filter($data['secondary_categories']) as $secCatId) {
                    DB::table('phppos_item_kits_secondary_categories')->insert([
                        'item_kit_id' => $kitId,
                        'category_id' => $secCatId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Sync Items
            PhpposItemKitItem::where('item_kit_id', $kitId)->delete();
            if (!empty($data['kit_items'])) {
                foreach ($data['kit_items'] as $item) {
                    if (!empty($item['item_id']) && !empty($item['quantity'])) {
                        PhpposItemKitItem::create([
                            'item_kit_id' => $kitId,
                            'item_id' => $item['item_id'],
                            'quantity' => $item['quantity'],
                        ]);
                    }
                }
            }

            // Sync Nested Kits
            PhpposItemKitItemKit::where('item_kit_id', $kitId)->delete();
            if (!empty($data['nested_kits'])) {
                foreach ($data['nested_kits'] as $nested) {
                    if (!empty($nested['item_kit_id']) && !empty($nested['quantity'])) {
                        PhpposItemKitItemKit::create([
                            'item_kit_id' => $kitId,
                            'item_kit_item_kit' => $nested['item_kit_id'],
                            'quantity' => $nested['quantity'],
                        ]);
                    }
                }
            }

            // Sync Taxes
            PhpposItemKitTax::where('item_kit_id', $kitId)->delete();
            if (!empty($data['tax_percents'])) {
                foreach ($data['tax_percents'] as $index => $percent) {
                    if (is_numeric($percent)) {
                        PhpposItemKitTax::create([
                            'item_kit_id' => $kitId,
                            'name' => $data['tax_names'][$index] ?? '',
                            'percent' => $percent,
                            'cumulative' => isset($data['tax_cumulatives'][$index]) ? 1 : 0,
                        ]);
                    }
                }
            }

            // Sync Tags
            DB::table('phppos_item_kits_tags')->where('item_kit_id', $kitId)->delete();
            if (!empty($data['tags'])) {
                $tagNames = array_map('trim', explode(',', $data['tags']));
                foreach ($tagNames as $tagName) {
                    if ($tagName) {
                        $tag = PhpposTag::firstOrCreate(['name' => $tagName]);
                        DB::table('phppos_item_kits_tags')->insert([
                            'item_kit_id' => $kitId,
                            'tag_id' => $tag->id,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('item-kits.index')->with('status', 'Item kit saved.');
    }
}
