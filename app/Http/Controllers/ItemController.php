<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\ItemVariation;
use App\Models\PhpposAppFile;
use App\Models\PhpposCategory;
use App\Models\PhpposImportQueue;
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
    public function __construct(protected AppConfigService $configService) {}

    public function index(Request $request): View
    {
        $employee_id = auth('employee')->id();
        $locationId = auth('employee')->user()?->location_id ?? 1;
        $search = $request->input('search');
        $category = $request->input('category');
        $categories = PhpposCategory::query()->where('deleted', 0)->orderBy('name')->get();

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

        $column_prefs_val = DB::table('phppos_employees_app_config')
            ->where('employee_id', $employee_id)
            ->where('key', 'items_column_prefs')
            ->value('value');

        $column_order_val = DB::table('phppos_employees_app_config')
            ->where('employee_id', $employee_id)
            ->where('key', 'items_column_order')
            ->value('value');

        if ($column_prefs_val) {
            $selected_columns = explode(',', $column_prefs_val);
            $selected_columns = array_values(array_intersect($selected_columns, array_keys($all_columns)));
        } else {
            $selected_columns = $default_columns;
        }

        // Determine full display order from saved column_order, or fall back
        $ordered_all_columns = [];

        if ($column_order_val) {
            $order = explode(',', $column_order_val);
            foreach ($order as $col) {
                if (isset($all_columns[$col]) && ! isset($ordered_all_columns[$col])) {
                    $ordered_all_columns[$col] = $all_columns[$col];
                }
            }
        } else {
            $sourceOrder = $column_prefs_val ? $selected_columns : $default_columns;
            foreach ($sourceOrder as $col) {
                if (isset($all_columns[$col])) {
                    $ordered_all_columns[$col] = $all_columns[$col];
                }
            }
        }

        // Append any remaining columns not in the saved order
        foreach ($all_columns as $col => $info) {
            if (! isset($ordered_all_columns[$col])) {
                $ordered_all_columns[$col] = $info;
            }
        }
        $all_columns = $ordered_all_columns;

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
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('phppos_items.name', 'like', "%{$search}%")
                        ->orWhere('phppos_items.item_number', 'like', "%{$search}%")
                        ->orWhere('phppos_items.product_id', 'like', "%{$search}%")
                        ->orWhere('c.name', 'like', "%{$search}%")
                        ->orWhere('s.company_name', 'like', "%{$search}%");
                });
            })
            ->when($category && $category !== 'all', function ($q) use ($category) {
                $q->where('phppos_items.category_id', $category);
            })
            ->orderBy('phppos_items.item_id', 'desc')
            ->paginate(20);

        $baseDecimalsRaw = $this->configService->get('number_of_decimals');
        $baseDecimals = is_numeric($baseDecimalsRaw) ? (int) $baseDecimalsRaw : 2;
        $baseCurrencySymbol = (string) $this->configService->get('currency_symbol', '$');

        return view('items.index', compact('items', 'all_columns', 'selected_columns', 'categories', 'baseDecimals', 'baseCurrencySymbol'));
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

        $attributes = Attribute::with('values')->where('deleted', 0)->whereNull('item_id')->orderBy('name')->get();

        $baseCurrencyCode = (string) $this->configService->get('currency_code', '');
        $baseCurrencySymbol = (string) $this->configService->get('currency_symbol', '$');
        $baseDecimalsRaw = $this->configService->get('number_of_decimals');
        $baseDecimals = is_numeric($baseDecimalsRaw) ? (int) $baseDecimalsRaw : 2;

        return view('items.form', [
            'item' => null,
            'categories' => $categoryOptions,
            'suppliers' => $suppliers,
            'taxClasses' => $taxClasses,
            'defaultTaxClass' => $defaultTaxClass,
            'tags' => '',
            'additional_item_numbers' => [],
            'secondary_categories' => [],
            'secondary_suppliers' => [],
            'item_files' => [],
            'attributes' => $attributes,
            'variations' => [],
            'baseCurrencyCode' => $baseCurrencyCode,
            'baseCurrencySymbol' => $baseCurrencySymbol,
            'baseDecimals' => $baseDecimals,
            'hideItemImageUpload' => (bool) $this->configService->get('hide_item_image_upload', false),
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

        $attributes = Attribute::with('values')->where('deleted', 0)->whereNull('item_id')->orderBy('name')->get();

        $variations = ItemVariation::with('attributeValues', 'suppliers')
            ->where('item_id', $itemId)
            ->where('deleted', 0)
            ->get()
            ->all();

        $baseCurrencyCode = (string) $this->configService->get('currency_code', '');
        $baseCurrencySymbol = (string) $this->configService->get('currency_symbol', '$');
        $baseDecimalsRaw = $this->configService->get('number_of_decimals');
        $baseDecimals = is_numeric($baseDecimalsRaw) ? (int) $baseDecimalsRaw : 2;

        return view('items.form', [
            'item' => $item,
            'categories' => $categoryOptions,
            'suppliers' => $suppliers,
            'taxClasses' => $taxClasses,
            'defaultTaxClass' => $defaultTaxClass,
            'tags' => $tags,
            'additional_item_numbers' => $additionalItemNumbers,
            'secondary_categories' => $secondaryCategories,
            'secondary_suppliers' => $secondarySuppliers,
            'item_files' => $itemFiles,
            'attributes' => $attributes,
            'variations' => $variations,
            'baseCurrencyCode' => $baseCurrencyCode,
            'baseCurrencySymbol' => $baseCurrencySymbol,
            'baseDecimals' => $baseDecimals,
            'hideItemImageUpload' => (bool) $this->configService->get('hide_item_image_upload', false),
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
                $label = $indent.e($child->name);
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
            if (! empty($payload)) {
                PhpposItem::query()->where('item_id', $itemId)->update($payload);
            }

            if ($request->has('quantity')) {
                PhpposItem::query()->where('item_id', $itemId)->update(['default_quantity' => $data['quantity']]);

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
            'discountable' => ['nullable', 'boolean'],

            // Nested or array inputs
            'tags' => ['nullable', 'string'],
            'additional_item_numbers' => ['nullable', 'array'],
            'secondary_categories' => ['nullable', 'array'],
            'secondary_suppliers' => ['nullable', 'array'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,gif', 'max:4096'],

            // Variation inputs
            'variations' => ['nullable', 'array'],
            'variations.*.name' => ['nullable', 'string', 'max:255'],
            'variations.*.item_number' => ['nullable', 'string', 'max:255'],
            'variations.*.attribute_value_ids' => ['nullable', 'array'],
            'variations.*.attribute_value_ids.*' => ['integer'],
            'variations.*.cost_price' => ['nullable', 'numeric'],
            'variations.*.markup' => ['nullable', 'numeric'],
            'variations.*.markup_type' => ['nullable', 'in:flat,percentage'],
            'variations.*.unit_price' => ['nullable', 'numeric'],
            'variations.*.promo_price' => ['nullable', 'numeric'],
            'variations.*.start_date' => ['nullable', 'date'],
            'variations.*.end_date' => ['nullable', 'date'],
            'variations.*.reorder_level' => ['nullable', 'numeric'],
            'variations.*.supplier_ids' => ['nullable', 'array'],
            'variations.*.supplier_ids.*' => ['integer'],
        ]);

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

            'tax_included' => 1,
            'is_service' => ! empty($data['is_service']) ? 1 : 0,
            'item_inactive' => ! empty($data['item_inactive']) ? 1 : 0,
            'is_barcoded' => ! empty($data['is_barcoded']) ? 1 : 0,
            'is_favorite' => ! empty($data['is_favorite']) ? 1 : 0,
            'is_ecommerce' => ! empty($data['is_ecommerce']) ? 1 : 0,
            'is_ebt_item' => ! empty($data['is_ebt_item']) ? 1 : 0,
            'is_series_package' => ! empty($data['is_series_package']) ? 1 : 0,
            'allow_alt_description' => ! empty($data['allow_alt_description']) ? 1 : 0,
            'is_serialized' => ! empty($data['is_serialized']) ? 1 : 0,
            'disable_loyalty' => ! empty($data['disable_loyalty']) ? 1 : 0,
            'verify_age' => ! empty($data['verify_age']) ? 1 : 0,
            'discountable' => ! empty($data['discountable']) ? 1 : 0,
        ];

        $locationId = auth('employee')->user()?->location_id ?? 1;

        DB::transaction(function () use ($payload, $itemId, $data, $locationId): void {
            if ($itemId) {
                PhpposItem::query()->where('item_id', $itemId)->update($payload);
            } else {
                $item = PhpposItem::query()->create($payload);
                $itemId = $item->item_id;
            }

            if (array_key_exists('default_quantity', $data)) {
                DB::table('phppos_location_items')->updateOrInsert(
                    ['location_id' => $locationId, 'item_id' => $itemId],
                    ['quantity' => $data['default_quantity'] ?? 0, 'updated_at' => now(), 'created_at' => now()]
                );
            }

            if (! empty($data['images'])) {
                foreach ($data['images'] as $file) {
                    if ($file) {
                        $appFile = PhpposAppFile::create([
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
                    if (! $tag) {
                        $tagId = DB::table('phppos_tags')->insertGetId([
                            'name' => $tagName,
                            'created_at' => now(),
                            'updated_at' => now(),
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
                        'updated_at' => now(),
                    ]);
                }
            } else {
                DB::table('phppos_items_tags')->where('item_id', $itemId)->delete();
            }

            // Sync additional item numbers
            DB::table('phppos_additional_item_numbers')->where('item_id', $itemId)->delete();
            if (! empty($data['additional_item_numbers'])) {
                foreach (array_filter($data['additional_item_numbers']) as $addNum) {
                    DB::table('phppos_additional_item_numbers')->insert([
                        'item_id' => $itemId,
                        'item_number' => $addNum,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Sync secondary categories
            DB::table('phppos_items_secondary_categories')->where('item_id', $itemId)->delete();
            if (! empty($data['secondary_categories'])) {
                foreach (array_filter($data['secondary_categories']) as $secCatId) {
                    DB::table('phppos_items_secondary_categories')->insert([
                        'item_id' => $itemId,
                        'category_id' => $secCatId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Sync secondary suppliers
            DB::table('phppos_items_secondary_suppliers')->where('item_id', $itemId)->delete();
            if (! empty($data['secondary_suppliers'])) {
                foreach (array_filter($data['secondary_suppliers']) as $secSupId) {
                    DB::table('phppos_items_secondary_suppliers')->insert([
                        'item_id' => $itemId,
                        'supplier_id' => $secSupId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Sync variations
            $existingVariationIds = ItemVariation::where('item_id', $itemId)->pluck('id')->toArray();
            $submittedVariationIds = [];

            if (! empty($data['variations'])) {
                foreach ($data['variations'] as $varData) {
                    $varPayload = [
                        'item_id' => $itemId,
                        'name' => $varData['name'] ?? '',
                        'item_number' => $varData['item_number'] ?? null,
                        'cost_price' => $varData['cost_price'] ?? 0,
                        'markup' => $varData['markup'] ?? 0,
                        'markup_type' => $varData['markup_type'] ?? 'flat',
                        'unit_price' => $varData['unit_price'] ?? 0,
                        'promo_price' => $varData['promo_price'] ?? null,
                        'start_date' => $varData['start_date'] ?? null,
                        'end_date' => $varData['end_date'] ?? null,
                        'reorder_level' => $varData['reorder_level'] ?? null,
                        'deleted' => 0,
                    ];

                    if (! empty($varData['id'])) {
                        // Update existing variation
                        ItemVariation::where('id', $varData['id'])->update($varPayload);
                        $variationId = $varData['id'];
                    } else {
                        // Create new variation
                        $variation = ItemVariation::create($varPayload);
                        $variationId = $variation->id;
                    }

                    $submittedVariationIds[] = $variationId;

                    // Sync attribute values for this variation
                    DB::table('phppos_item_variation_attribute_values')
                        ->where('item_variation_id', $variationId)
                        ->delete();

                    if (! empty($varData['attribute_value_ids'])) {
                        foreach (array_filter($varData['attribute_value_ids']) as $avId) {
                            DB::table('phppos_item_variation_attribute_values')->insert([
                                'item_variation_id' => $variationId,
                                'attribute_value_id' => $avId,
                            ]);
                        }
                    }

                    // Sync suppliers for this variation
                    DB::table('phppos_item_variation_suppliers')
                        ->where('item_variation_id', $variationId)
                        ->delete();

                    if (! empty($varData['supplier_ids'])) {
                        foreach (array_filter($varData['supplier_ids']) as $supId) {
                            DB::table('phppos_item_variation_suppliers')->insert([
                                'item_variation_id' => $variationId,
                                'supplier_id' => $supId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }

            // Soft-delete variations not in the submitted set
            $variationsToDelete = array_diff($existingVariationIds, $submittedVariationIds);
            if (! empty($variationsToDelete)) {
                ItemVariation::whereIn('id', $variationsToDelete)->update(['deleted' => 1]);
            }
        });

        return redirect()->route('items.index')->with('status', 'Item saved.');
    }

    public function export(Request $request)
    {
        $format = $request->query('format', 'csv');

        $locationId = auth('employee')->user()?->location_id ?? 1;

        $categories = PhpposCategory::query()->where('deleted', 0)->pluck('name', 'id');
        $suppliers = PhpposSupplier::query()->where('deleted', 0)->pluck('company_name', 'person_id');

        $items = PhpposItem::query()
            ->leftJoin('phppos_location_items as li', function ($join) use ($locationId) {
                $join->on('li.item_id', '=', 'phppos_items.item_id')
                    ->where('li.location_id', '=', $locationId);
            })
            ->where('phppos_items.deleted', 0)
            ->select('phppos_items.*', 'li.quantity as location_quantity')
            ->orderBy('phppos_items.item_id')
            ->get();

        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                'item_id' => $item->item_id,
                'name' => $item->name,
                'item_number' => $item->item_number ?? '',
                'product_id' => $item->product_id ?? '',
                'category' => $item->category_id ? ($categories[$item->category_id] ?? '') : '',
                'supplier' => $item->supplier_id ? ($suppliers[$item->supplier_id] ?? '') : '',
                'cost_price' => $item->cost_price,
                'unit_price' => $item->unit_price,
                'quantity' => $item->location_quantity ?? $item->default_quantity ?? 0,
                'reorder_level' => $item->reorder_level ?? '',
                'description' => $item->description ?? '',
                'size' => $item->size ?? '',
                'weight' => $item->weight ?? '',
                'is_service' => $item->is_service ? 1 : 0,
                'item_inactive' => $item->item_inactive ? 1 : 0,
                'is_barcoded' => $item->is_barcoded ? 1 : 0,
            ];
        }

        $columnLabels = [
            'ID', 'Name', 'Item Number', 'Product ID', 'Category', 'Supplier',
            'Cost Price', 'Unit Price', 'Quantity', 'Reorder Level',
            'Description', 'Size', 'Weight', 'Is Service', 'Inactive', 'Is Barcoded',
        ];

        if ($format === 'xls') {
            $html = '<html><head><meta charset="UTF-8"><title>Items Export</title>';
            $html .= '<style>table{border-collapse:collapse;width:100%;font-family:Arial,sans-serif;font-size:11pt;}th,td{border:1px solid #ccc;padding:4px 6px;text-align:left;}th{background:#f0f0f0;font-weight:bold;}</style>';
            $html .= '</head><body><h2>Items</h2>';
            $html .= '<table><thead><tr>';
            foreach ($columnLabels as $label) {
                $html .= '<th>'.htmlspecialchars($label).'</th>';
            }
            $html .= '</tr></thead><tbody>';
            foreach ($rows as $row) {
                $html .= '<tr>';
                $html .= '<td>'.htmlspecialchars((string) $row['item_id']).'</td>';
                $html .= '<td>'.htmlspecialchars($row['name']).'</td>';
                $html .= '<td>'.htmlspecialchars($row['item_number']).'</td>';
                $html .= '<td>'.htmlspecialchars($row['product_id']).'</td>';
                $html .= '<td>'.htmlspecialchars($row['category']).'</td>';
                $html .= '<td>'.htmlspecialchars($row['supplier']).'</td>';
                $html .= '<td>'.htmlspecialchars((string) $row['cost_price']).'</td>';
                $html .= '<td>'.htmlspecialchars((string) $row['unit_price']).'</td>';
                $html .= '<td>'.htmlspecialchars((string) $row['quantity']).'</td>';
                $html .= '<td>'.htmlspecialchars((string) $row['reorder_level']).'</td>';
                $html .= '<td>'.htmlspecialchars($row['description']).'</td>';
                $html .= '<td>'.htmlspecialchars($row['size']).'</td>';
                $html .= '<td>'.htmlspecialchars((string) $row['weight']).'</td>';
                $html .= '<td>'.$row['is_service'].'</td>';
                $html .= '<td>'.$row['item_inactive'].'</td>';
                $html .= '<td>'.$row['is_barcoded'].'</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table></body></html>';

            return response($html, 200, [
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="items-export.xls"',
            ]);
        }

        $callback = function () use ($columnLabels, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columnLabels);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['item_id'],
                    $row['name'],
                    $row['item_number'],
                    $row['product_id'],
                    $row['category'],
                    $row['supplier'],
                    $row['cost_price'],
                    $row['unit_price'],
                    $row['quantity'],
                    $row['reorder_level'],
                    $row['description'],
                    $row['size'],
                    $row['weight'],
                    $row['is_service'],
                    $row['item_inactive'],
                    $row['is_barcoded'],
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="items-export.csv"',
        ]);
    }

    public function importForm(): View
    {
        return view('items.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'import_file' => ['required', 'file', 'mimes:csv,txt,xls,html'],
        ]);

        $file = $request->file('import_file');
        $ext = strtolower($file->getClientOriginalExtension());

        $rows = ($ext === 'xls' || $ext === 'html')
            ? $this->parseXls($file->getRealPath())
            : $this->parseCsv($file->getRealPath());

        if (empty($rows)) {
            return back()->withErrors(['import_file' => 'The file is empty or has no valid data rows.']);
        }

        $locationId = auth('employee')->user()?->location_id ?? 1;
        $employeeId = auth('employee')->user()?->id ?? 1;

        $categories = PhpposCategory::query()->where('deleted', 0)->get()->keyBy(fn ($c) => strtolower($c->name));
        $suppliers = PhpposSupplier::query()->where('deleted', 0)->get()->keyBy(fn ($s) => strtolower($s->company_name));

        $existingItems = PhpposItem::query()->where('deleted', 0)->get();
        $byNumber = $existingItems->filter(fn ($i) => $i->item_number)->keyBy(fn ($i) => strtolower($i->item_number));
        $byProduct = $existingItems->filter(fn ($i) => $i->product_id)->keyBy(fn ($i) => strtolower($i->product_id));

        $created = 0;
        $queued = 0;
        $skipped = 0;
        $batch = uniqid('imp_', true);

        foreach ($rows as $row) {
            $name = trim($row['name'] ?? '');
            if ($name === '') {
                $skipped++;

                continue;
            }

            $itemNumber = trim($row['item_number'] ?? '');
            $productId = trim($row['product_id'] ?? '');

            $existing = null;
            if ($productId !== '' && isset($byProduct[strtolower($productId)])) {
                $existing = $byProduct[strtolower($productId)];
            } elseif ($itemNumber !== '' && isset($byNumber[strtolower($itemNumber)])) {
                $existing = $byNumber[strtolower($itemNumber)];
            }

            if ($existing) {
                PhpposImportQueue::query()->create([
                    'import_batch' => $batch,
                    'item_id' => $existing->item_id,
                    'item_number' => $itemNumber ?: $existing->item_number,
                    'product_id' => $productId ?: $existing->product_id,
                    'name' => $name,
                    'category' => trim($row['category'] ?? ''),
                    'supplier' => trim($row['supplier'] ?? ''),
                    'existing_cost_price' => $existing->cost_price,
                    'existing_unit_price' => $existing->unit_price,
                    'existing_quantity' => DB::table('phppos_location_items')
                        ->where('location_id', $locationId)
                        ->where('item_id', $existing->item_id)
                        ->value('quantity') ?? 0,
                    'incoming_cost_price' => (float) ($row['cost_price'] ?? 0),
                    'incoming_unit_price' => (float) ($row['unit_price'] ?? 0),
                    'incoming_quantity' => (float) ($row['quantity'] ?? 0),
                    'status' => 'pending',
                    'employee_id' => $employeeId,
                ]);

                $queued++;
            } else {
                $categoryId = null;
                $catName = trim($row['category'] ?? '');
                if ($catName !== '' && isset($categories[strtolower($catName)])) {
                    $categoryId = $categories[strtolower($catName)]->id;
                }

                $supplierId = null;
                $supName = trim($row['supplier'] ?? '');
                if ($supName !== '' && isset($suppliers[strtolower($supName)])) {
                    $supplierId = $suppliers[strtolower($supName)]->person_id;
                }

                $payload = [
                    'name' => $name,
                    'item_number' => $itemNumber ?: null,
                    'product_id' => $productId ?: null,
                    'category_id' => $categoryId,
                    'supplier_id' => $supplierId,
                    'cost_price' => (float) ($row['cost_price'] ?? 0),
                    'unit_price' => (float) ($row['unit_price'] ?? 0),
                    'default_quantity' => $row['quantity'] ?? null,
                    'reorder_level' => $row['reorder_level'] ?? null,
                    'description' => $row['description'] ?? null,
                    'size' => $row['size'] ?? null,
                    'weight' => $row['weight'] ?? null,
                    'is_service' => (int) ($row['is_service'] ?? 0),
                    'item_inactive' => (int) ($row['item_inactive'] ?? 0),
                    'is_barcoded' => (int) ($row['is_barcoded'] ?? 1),
                ];

                $item = PhpposItem::query()->create($payload);

                if ($payload['default_quantity'] !== null) {
                    DB::table('phppos_location_items')->insert([
                        'location_id' => $locationId,
                        'item_id' => $item->item_id,
                        'quantity' => $payload['default_quantity'] ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $byNumber[strtolower($item->item_number)] = $item;
                $byProduct[strtolower($item->product_id)] = $item;

                $created++;
            }
        }

        if ($queued > 0) {
            return redirect()->route('items.import.review', ['batch' => $batch])
                ->with('import_created', $created);
        }

        $message = "Import complete. Created: {$created}, Skipped: {$skipped}.";

        return back()->with('status', $message);
    }

    public function importReview(Request $request, string $batch): View
    {
        $employeeId = auth('employee')->user()?->id ?? 1;

        if ($batch === 'latest') {
            $latest = PhpposImportQueue::query()
                ->where('employee_id', $employeeId)
                ->where('status', 'pending')
                ->latest()
                ->value('import_batch');

            if ($latest) {
                $batch = $latest;
            } else {
                return view('items.import-review', [
                    'items' => collect(),
                    'batch' => '',
                    'totalPending' => 0,
                    'created' => 0,
                ]);
            }
        }

        $items = PhpposImportQueue::query()
            ->where('import_batch', $batch)
            ->where('employee_id', $employeeId)
            ->where('status', 'pending')
            ->orderBy('id')
            ->get();

        $totalPending = $items->count();
        $created = session('import_created', 0);

        return view('items.import-review', compact('items', 'batch', 'totalPending', 'created'));
    }

    public function importAccept(Request $request): RedirectResponse
    {
        $request->validate([
            'batch' => ['required', 'string'],
            'queue_ids' => ['required', 'array'],
            'queue_ids.*' => ['integer', 'exists:phppos_import_queue,id'],
        ]);

        $employeeId = auth('employee')->user()?->id ?? 1;
        $locationId = auth('employee')->user()?->location_id ?? 1;
        $batch = $request->input('batch');
        $queueIds = $request->input('queue_ids');

        $items = PhpposImportQueue::query()
            ->where('import_batch', $batch)
            ->where('employee_id', $employeeId)
            ->whereIn('id', $queueIds)
            ->where('status', 'pending')
            ->get();

        $accepted = 0;
        foreach ($items as $item) {
            PhpposItem::query()->where('item_id', $item->item_id)->update([
                'cost_price' => $item->incoming_cost_price,
                'unit_price' => $item->incoming_unit_price,
            ]);

            DB::table('phppos_location_items')->updateOrInsert(
                ['location_id' => $locationId, 'item_id' => $item->item_id],
                ['quantity' => $item->incoming_quantity, 'updated_at' => now(), 'created_at' => now()]
            );

            $item->update(['status' => 'accepted']);
            $accepted++;
        }

        $remaining = PhpposImportQueue::query()
            ->where('import_batch', $batch)
            ->where('employee_id', $employeeId)
            ->where('status', 'pending')
            ->count();

        if ($remaining === 0) {
            return redirect()->route('items.index')
                ->with('status', "Import complete. Accepted {$accepted} items.");
        }

        return redirect()->route('items.import.review', ['batch' => $batch])
            ->with('status', "Accepted {$accepted} items. {$remaining} remaining.");
    }

    public function importAcceptAll(Request $request): RedirectResponse
    {
        $request->validate([
            'batch' => ['required', 'string'],
        ]);

        $employeeId = auth('employee')->user()?->id ?? 1;
        $locationId = auth('employee')->user()?->location_id ?? 1;
        $batch = $request->input('batch');

        $items = PhpposImportQueue::query()
            ->where('import_batch', $batch)
            ->where('employee_id', $employeeId)
            ->where('status', 'pending')
            ->get();

        $accepted = 0;
        foreach ($items as $item) {
            PhpposItem::query()->where('item_id', $item->item_id)->update([
                'cost_price' => $item->incoming_cost_price,
                'unit_price' => $item->incoming_unit_price,
            ]);

            DB::table('phppos_location_items')->updateOrInsert(
                ['location_id' => $locationId, 'item_id' => $item->item_id],
                ['quantity' => $item->incoming_quantity, 'updated_at' => now(), 'created_at' => now()]
            );

            $item->update(['status' => 'accepted']);
            $accepted++;
        }

        return redirect()->route('items.index')
            ->with('status', "Import complete. Accepted all {$accepted} items.");
    }

    public function importSkip(Request $request): RedirectResponse
    {
        $request->validate([
            'batch' => ['required', 'string'],
            'queue_ids' => ['required', 'array'],
            'queue_ids.*' => ['integer', 'exists:phppos_import_queue,id'],
        ]);

        $employeeId = auth('employee')->user()?->id ?? 1;
        $batch = $request->input('batch');

        PhpposImportQueue::query()
            ->where('import_batch', $batch)
            ->where('employee_id', $employeeId)
            ->whereIn('id', $request->input('queue_ids'))
            ->where('status', 'pending')
            ->update(['status' => 'skipped']);

        $remaining = PhpposImportQueue::query()
            ->where('import_batch', $batch)
            ->where('employee_id', $employeeId)
            ->where('status', 'pending')
            ->count();

        if ($remaining === 0) {
            return redirect()->route('items.index')
                ->with('status', 'Import complete. All items skipped.');
        }

        return redirect()->route('items.import.review', ['batch' => $batch])
            ->with('status', "Skipped items. {$remaining} remaining.");
    }

    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return [];
        }

        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);

            return [];
        }

        $headers = array_map(fn ($h) => str_replace(' ', '_', strtolower(trim($h))), $headers);

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === count($headers)) {
                $rows[] = array_combine($headers, $data);
            }
        }
        fclose($handle);

        return $rows;
    }

    private function parseXls(string $path): array
    {
        $content = file_get_contents($path);
        if (! $content) {
            return [];
        }

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($content);
        libxml_clear_errors();

        $tables = $dom->getElementsByTagName('table');
        if ($tables->length === 0) {
            return [];
        }

        $table = $tables->item(0);
        $rows = [];

        $trElements = $table->getElementsByTagName('tr');
        if ($trElements->length < 2) {
            return [];
        }

        $headerCells = $trElements->item(0)->getElementsByTagName('td');
        if ($headerCells->length === 0) {
            $headerCells = $trElements->item(0)->getElementsByTagName('th');
        }

        $headers = [];
        for ($i = 0; $i < $headerCells->length; $i++) {
            $headers[] = str_replace(' ', '_', strtolower(trim($headerCells->item($i)->textContent)));
        }

        for ($i = 1; $i < $trElements->length; $i++) {
            $cells = $trElements->item($i)->getElementsByTagName('td');
            if ($cells->length === count($headers)) {
                $row = [];
                for ($j = 0; $j < $cells->length; $j++) {
                    $row[$headers[$j]] = trim($cells->item($j)->textContent);
                }
                $rows[] = $row;
            }
        }

        return $rows;
    }
}
