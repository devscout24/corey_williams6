<?php

namespace App\Http\Controllers;

use App\Models\PhpposCategory;
use App\Models\PhpposItem;
use App\Models\PhpposSupplier;
use App\Models\PhpposTaxClass;
use App\Services\AppConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ItemController extends Controller
{
    
    public function index(): View
    {
        $employee_id = auth('employee')->id();
        $locationId  = auth('employee')->user()?->location_id ?? 1;

        $column_prefs_val = DB::table('phppos_employees_app_config')
            ->where('employee_id', $employee_id)
            ->where('key', 'items_column_prefs')
            ->value('value');

        $all_columns = [
            'item_id' => ['label' => 'Item Id', 'sort' => true],
            'item_number' => ['label' => 'UPC/EAN/ISBN', 'sort' => true],
            'name' => ['label' => 'Name', 'sort' => true],
            'category' => ['label' => 'Category', 'sort' => true],
            'cost_price' => ['label' => 'Cost Price', 'sort' => true],
            'unit_price' => ['label' => 'Selling Price', 'sort' => true],
            'quantity' => ['label' => 'Quantity', 'sort' => true],
            'reorder_level' => ['label' => 'Threshold', 'sort' => true],
        ];

        $default_columns = ['name', 'category', 'cost_price', 'unit_price', 'quantity', 'reorder_level', 'item_number'];

        if ($column_prefs_val) {
            $selected_columns = explode(',', $column_prefs_val);
            // Ensure we only have valid columns and maintain the order from $selected_columns
            $selected_columns = array_values(array_intersect($selected_columns, array_keys($all_columns)));
            
            // Reconstruct all_columns so the dropdown shows SELECTED items in their saved order FIRST,
            // then any unselected items afterwards.
            $ordered_all_columns = [];
            foreach ($selected_columns as $col) {
                if (isset($all_columns[$col])) {
                    $ordered_all_columns[$col] = $all_columns[$col];
                }
            }
            foreach ($all_columns as $col => $info) {
                if (!isset($ordered_all_columns[$col])) {
                    $ordered_all_columns[$col] = $info;
                }
            }
            $all_columns = $ordered_all_columns;
        } else {
            $selected_columns = $default_columns;
            
            // Even if no prefs, let's ensure $all_columns order matches $default_columns for consistency
            $ordered_all_columns = [];
            foreach ($default_columns as $col) {
                if (isset($all_columns[$col])) {
                    $ordered_all_columns[$col] = $all_columns[$col];
                }
            }
            foreach ($all_columns as $col => $info) {
                if (!isset($ordered_all_columns[$col])) {
                    $ordered_all_columns[$col] = $info;
                }
            }
            $all_columns = $ordered_all_columns;
        }

        $thumbs = DB::table('phppos_item_files')
            ->select('item_id', DB::raw('MIN(file_id) as file_id'))
            ->groupBy('item_id');

        $items = PhpposItem::query()
            ->leftJoin('phppos_categories as c', 'c.id', '=', 'phppos_items.category_id')
            ->leftJoin('phppos_suppliers as s', 's.person_id', '=', 'phppos_items.supplier_id')
            ->leftJoinSub($thumbs, 'item_files', function ($join) {
                $join->on('item_files.item_id', '=', 'phppos_items.item_id');
            })
            ->leftJoin('phppos_location_items as li', function ($join) use ($locationId) {
                $join->on('li.item_id', '=', 'phppos_items.item_id')
                    ->where('li.location_id', '=', $locationId);
            })
            ->select(
                'phppos_items.*',
                'c.name as category_name',
                's.company_name as supplier_name',
                'li.quantity as location_quantity',
                'item_files.file_id as image_file_id'
            )
            ->where('phppos_items.deleted', 0)
            ->orderBy('phppos_items.item_id', 'desc')
            ->paginate(20);

        return view('items.index', compact('items', 'all_columns', 'selected_columns'));
    }

    public function create(): View
    {
        $categories = PhpposCategory::query()->where('deleted', 0)->orderBy('name')->get();
        $suppliers = PhpposSupplier::query()->where('deleted', 0)->orderBy('company_name')->get();
        $categoryOptions = $this->buildCategoryOptions($categories);
        $taxClasses = PhpposTaxClass::query()->where('deleted', 0)->orderBy('name')->get();
        $defaultTaxClassId = (string) app(AppConfigService::class)->get('tax_class_id', '');
        $defaultTaxClass = $defaultTaxClassId !== ''
            ? $taxClasses->firstWhere('id', (int) $defaultTaxClassId)
            : null;

        return view('items.form', [
            'item' => null,
            'categories' => $categoryOptions,
            'suppliers' => $suppliers,
            'taxClasses' => $taxClasses,
            'defaultTaxClass' => $defaultTaxClass,
            'tags' => '',
            'additional_item_numbers' => [],
            'serial_numbers' => [],
            'secondary_categories' => [],
            'secondary_suppliers' => [],
            'item_files' => [],
        ]);
    }

    public function edit(int $itemId): View
    {
        $item = PhpposItem::query()->where('item_id', $itemId)->firstOrFail();
        $categories = PhpposCategory::query()->where('deleted', 0)->orderBy('name')->get();
        $suppliers = PhpposSupplier::query()->where('deleted', 0)->orderBy('company_name')->get();
        $categoryOptions = $this->buildCategoryOptions($categories);
        $taxClasses = PhpposTaxClass::query()->where('deleted', 0)->orderBy('name')->get();
        $defaultTaxClassId = (string) app(AppConfigService::class)->get('tax_class_id', '');
        $defaultTaxClass = $defaultTaxClassId !== ''
            ? $taxClasses->firstWhere('id', (int) $defaultTaxClassId)
            : null;

        $tags = DB::table('phppos_items_tags')
            ->join('phppos_tags', 'phppos_items_tags.tag_id', '=', 'phppos_tags.id')
            ->where('phppos_items_tags.item_id', $itemId)
            ->pluck('phppos_tags.name')
            ->implode(', ');

        $additionalItemNumbers = DB::table('phppos_additional_item_numbers')
            ->where('item_id', $itemId)
            ->pluck('item_number')
            ->all();

        $serialNumbers = DB::table('phppos_items_serial_numbers')
            ->where('item_id', $itemId)
            ->get()
            ->all();

        $secondaryCategories = DB::table('phppos_items_secondary_categories')
            ->where('item_id', $itemId)
            ->get()
            ->all();

        $secondarySuppliers = DB::table('phppos_items_secondary_suppliers')
            ->where('item_id', $itemId)
            ->get()
            ->all();

        $itemFiles = DB::table('phppos_item_files')
            ->join('phppos_app_files', 'phppos_app_files.file_id', '=', 'phppos_item_files.file_id')
            ->where('phppos_item_files.item_id', $itemId)
            ->select('phppos_app_files.file_id', 'phppos_app_files.file_name')
            ->get()
            ->all();

        return view('items.form', [
            'item' => $item,
            'categories' => $categoryOptions,
            'suppliers' => $suppliers,
            'taxClasses' => $taxClasses,
            'defaultTaxClass' => $defaultTaxClass,
            'tags' => $tags,
            'additional_item_numbers' => $additionalItemNumbers,
            'serial_numbers' => $serialNumbers,
            'secondary_categories' => $secondaryCategories,
            'secondary_suppliers' => $secondarySuppliers,
            'item_files' => $itemFiles,
        ]);
    }

    private function buildCategoryOptions($categories): array
    {
        $grouped = $categories->groupBy('parent_id');
        $options = [];

        $walk = function ($parentId, int $depth) use (&$walk, $grouped, &$options): void {
            $children = $grouped->get($parentId, collect())->sortBy('name');
            foreach ($children as $child) {
                $indent = str_repeat('&nbsp;', $depth * 4);
                $label = $indent . e($child->name);
                $options[] = (object) [
                    'id' => $child->id,
                    'name' => $child->name,
                    'label' => $label,
                    'depth' => $depth,
                ];
                $walk($child->id, $depth + 1);
            }
        };

        $walk(null, 0);

        return $options;
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->saveItem($request, null);
    }

    public function update(Request $request, int $itemId): RedirectResponse
    {
        return $this->saveItem($request, $itemId);
    }

    public function destroy(int $itemId): RedirectResponse
    {
        PhpposItem::query()->where('item_id', $itemId)->update(['deleted' => 1]);

        return redirect()->route('items.index')->with('status', 'Item archived.');
    }

    public function quickUpdate(Request $request, int $itemId): JsonResponse
    {
        $data = $request->validate([
            'cost_price' => ['nullable', 'numeric'],
            'unit_price' => ['nullable', 'numeric'],
            'quantity' => ['nullable', 'numeric'],
            'reorder_level' => ['nullable', 'numeric'],
        ]);

        $locationId = auth('employee')->user()?->location_id ?? 1;
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

        DB::transaction(function () use ($itemId, $payload, $request, $data, $locationId): void {
            if (!empty($payload)) {
                PhpposItem::query()->where('item_id', $itemId)->update($payload);
            }

            if ($request->has('quantity')) {
                DB::table('phppos_location_items')->updateOrInsert(
                    ['location_id' => $locationId, 'item_id' => $itemId],
                    ['quantity' => $data['quantity'] ?? 0, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        });

        return response()->json(['status' => 'ok']);
    }

    private function saveItem(Request $request, ?int $itemId): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'barcode_name' => ['nullable', 'string', 'max:255'],
            'item_number' => ['nullable', 'string', 'max:255'],
            'product_id' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer'],
            'supplier_id' => ['nullable', 'integer'],
            'manufacturer_id' => ['nullable', 'integer'],
            'tax_class_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:5000'],
            'long_description' => ['nullable', 'string'],
            'info_popup' => ['nullable', 'string', 'max:5000'],
            'size' => ['nullable', 'string', 'max:255'],
            'weight' => ['nullable', 'numeric'],
            'weight_unit' => ['nullable', 'string', 'max:50'],
            'length' => ['nullable', 'numeric'],
            'width' => ['nullable', 'numeric'],
            'height' => ['nullable', 'numeric'],
            'default_quantity' => ['nullable', 'numeric'],
            'reorder_level' => ['nullable', 'numeric'],
            'cost_price' => ['nullable', 'numeric'],
            'markup' => ['nullable', 'numeric'],
            'markup_type' => ['nullable', 'in:flat,percentage'],
            'unit_price' => ['nullable', 'numeric'],
            'tax_included' => ['nullable', 'boolean'],
            'is_service' => ['nullable', 'boolean'],
            'item_inactive' => ['nullable', 'boolean'],
            'is_barcoded' => ['nullable', 'boolean'],
            'is_favorite' => ['nullable', 'boolean'],
            'is_ecommerce' => ['nullable', 'boolean'],
            'is_ebt_item' => ['nullable', 'boolean'],
            'is_series_package' => ['nullable', 'boolean'],
            'series_quantity' => ['nullable', 'integer'],
            'series_days_to_use_within' => ['nullable', 'integer'],
            'allow_alt_description' => ['nullable', 'boolean'],
            'is_serialized' => ['nullable', 'boolean'],
            'disable_loyalty' => ['nullable', 'boolean'],
            'loyalty_multiplier' => ['nullable', 'numeric'],
            'verify_age' => ['nullable', 'boolean'],
            'required_age' => ['nullable', 'integer'],
            'ecommerce_shipping_class_id' => ['nullable', 'integer'],

            // Nested or array inputs
            'tags' => ['nullable', 'string'],
            'additional_item_numbers' => ['nullable', 'array'],
            'serial_numbers' => ['nullable', 'array'],
            'serial_cost_prices' => ['nullable', 'array'],
            'serial_unit_prices' => ['nullable', 'array'],
            'secondary_categories' => ['nullable', 'array'],
            'secondary_suppliers' => ['nullable', 'array'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,gif', 'max:4096'],
        ]);

        // Add custom fields validation
        for ($i = 1; $i <= 10; $i++) {
            $data["custom_field_{$i}_value"] = $request->input("custom_field_{$i}_value");
        }

        $payload = [
            'name' => $data['name'],
            'barcode_name' => $data['barcode_name'] ?? null,
            'item_number' => $data['item_number'] ?? null,
            'product_id' => $data['product_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'manufacturer_id' => $data['manufacturer_id'] ?? null,
            'tax_class_id' => $data['tax_class_id'] ?? null,
            'description' => $data['description'] ?? null,
            'long_description' => $data['long_description'] ?? null,
            'info_popup' => $data['info_popup'] ?? null,
            'size' => $data['size'] ?? null,
            'weight' => $data['weight'] ?? null,
            'weight_unit' => $data['weight_unit'] ?? null,
            'length' => $data['length'] ?? null,
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'default_quantity' => $data['default_quantity'] ?? null,
            'reorder_level' => $data['reorder_level'] ?? null,
            'cost_price' => $data['cost_price'] ?? 0,
            'markup' => $data['markup'] ?? 0,
            'markup_type' => $data['markup_type'] ?? 'flat',
            'unit_price' => $data['unit_price'] ?? 0,
            'series_quantity' => $data['series_quantity'] ?? null,
            'series_days_to_use_within' => $data['series_days_to_use_within'] ?? null,
            'loyalty_multiplier' => $data['loyalty_multiplier'] ?? null,
            'required_age' => $data['required_age'] ?? null,
            'ecommerce_shipping_class_id' => $data['ecommerce_shipping_class_id'] ?? null,

            // 'tax_included' => !empty($data['tax_included']) ? 1 : 0,
            'tax_included' => 1, // Default to true as per recent change, can be toggled in form
            'is_service' => !empty($data['is_service']) ? 1 : 0,
            'item_inactive' => !empty($data['item_inactive']) ? 1 : 0,
            'is_barcoded' => !empty($data['is_barcoded']) ? 1 : 0,
            'is_favorite' => !empty($data['is_favorite']) ? 1 : 0,
            'is_ecommerce' => !empty($data['is_ecommerce']) ? 1 : 0,
            'is_ebt_item' => !empty($data['is_ebt_item']) ? 1 : 0,
            'is_series_package' => !empty($data['is_series_package']) ? 1 : 0,
            'allow_alt_description' => !empty($data['allow_alt_description']) ? 1 : 0,
            'is_serialized' => !empty($data['is_serialized']) ? 1 : 0,
            'disable_loyalty' => !empty($data['disable_loyalty']) ? 1 : 0,
            'verify_age' => !empty($data['verify_age']) ? 1 : 0,
        ];

        DB::transaction(function () use ($payload, $itemId, $data): void {
            if ($itemId) {
                PhpposItem::query()->where('item_id', $itemId)->update($payload);
            } else {
                $item = PhpposItem::query()->create($payload);
                $itemId = $item->item_id;
            }

            if (!empty($data['images'])) {
                foreach ($data['images'] as $file) {
                    if ($file) {
                        $appFile = \App\Models\PhpposAppFile::create([
                            'file_name' => $file->getClientOriginalName(),
                            'file_data' => file_get_contents($file->getRealPath()),
                            'timestamp' => now(),
                        ]);

                        DB::table('phppos_item_files')->insert([
                            'item_id' => $itemId,
                            'file_id' => $appFile->file_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            // Sync tags
            if (isset($data['tags'])) {
                $tagNames = array_filter(array_map('trim', explode(',', $data['tags'])));
                $tagIds = [];
                foreach ($tagNames as $tagName) {
                    $tag = DB::table('phppos_tags')->where('name', $tagName)->first();
                    if (!$tag) {
                        $tagId = DB::table('phppos_tags')->insertGetId([
                            'name' => $tagName,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    } else {
                        $tagId = $tag->id;
                    }
                    $tagIds[] = $tagId;
                }

                DB::table('phppos_items_tags')->where('item_id', $itemId)->delete();
                foreach (array_unique($tagIds) as $tId) {
                    DB::table('phppos_items_tags')->insert([
                        'item_id' => $itemId,
                        'tag_id' => $tId,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            } else {
                DB::table('phppos_items_tags')->where('item_id', $itemId)->delete();
            }

            // Sync additional item numbers
            DB::table('phppos_additional_item_numbers')->where('item_id', $itemId)->delete();
            if (!empty($data['additional_item_numbers'])) {
                foreach (array_filter($data['additional_item_numbers']) as $addNum) {
                    DB::table('phppos_additional_item_numbers')->insert([
                        'item_id' => $itemId,
                        'item_number' => $addNum,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            // Sync serial numbers
            DB::table('phppos_items_serial_numbers')->where('item_id', $itemId)->delete();
            if (!empty($data['serial_numbers'])) {
                foreach ($data['serial_numbers'] as $index => $serialNum) {
                    if (empty($serialNum))
                        continue;
                    DB::table('phppos_items_serial_numbers')->insert([
                        'item_id' => $itemId,
                        'serial_number' => $serialNum,
                        'cost_price' => $data['serial_cost_prices'][$index] ?? null,
                        'unit_price' => $data['serial_unit_prices'][$index] ?? null,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            // Sync secondary categories
            DB::table('phppos_items_secondary_categories')->where('item_id', $itemId)->delete();
            if (!empty($data['secondary_categories'])) {
                foreach (array_filter($data['secondary_categories']) as $secCatId) {
                    DB::table('phppos_items_secondary_categories')->insert([
                        'item_id' => $itemId,
                        'category_id' => $secCatId,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            // Sync secondary suppliers
            DB::table('phppos_items_secondary_suppliers')->where('item_id', $itemId)->delete();
            if (!empty($data['secondary_suppliers'])) {
                foreach (array_filter($data['secondary_suppliers']) as $secSupId) {
                    DB::table('phppos_items_secondary_suppliers')->insert([
                        'item_id' => $itemId,
                        'supplier_id' => $secSupId,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        });

        return redirect()->route('items.index')->with('status', 'Item saved.');
    }


}
