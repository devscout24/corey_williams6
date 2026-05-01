<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemKit;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\PhpposItem;
use App\Models\PhpposLocation;
use App\Models\PriceRule;
use App\Models\PriceRulePriceBreak;
use App\Models\PriceTier;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PriceRuleController extends Controller
{
    public function index()
    {
        $priceRules = PriceRule::where('deleted', 0)->paginate(20);
        return view('price_rules.index', compact('priceRules'));
    }

    public function create()
    {
        $locations = PhpposLocation::where('deleted', 0)->get();
        $tiers = PriceTier::where('deleted', 0)->get();
        return view('price_rules.form', compact('locations', 'tiers'));
    }

    public function edit($id)
    {
        $priceRule = PriceRule::with(['items', 'itemKits', 'categories', 'tags', 'manufacturers', 'locations', 'priceBreaks'])->findOrFail($id);
        $locations = PhpposLocation::where('deleted', 0)->get();
        $tiers = PriceTier::where('deleted', 0)->get();

        $rule_items = $priceRule->items->map(fn($item) => ['id' => 'item_' . $item->item_id, 'name' => $item->name]);
        $rule_item_kits = $priceRule->itemKits->map(fn($kit) => ['id' => 'kit_' . $kit->id, 'name' => $kit->name]);
        $rule_cats = $priceRule->categories->map(fn($cat) => ['id' => $cat->id, 'name' => $cat->name]);
        $rule_tags = $priceRule->tags->map(fn($tag) => ['id' => $tag->id, 'name' => $tag->name]);

        return view('price_rules.form', compact('priceRule', 'locations', 'tiers', 'rule_items', 'rule_item_kits', 'rule_cats', 'rule_tags'));
    }

    public function store(Request $request)
    {
        return $this->save($request);
    }

    public function update(Request $request, $id)
    {
        return $this->save($request, $id);
    }

    private function save(Request $request, $id = null)
    {
        DB::transaction(function () use ($request, $id) {
            $data = $request->only([
                'name',
                'type',
                'active',
                'items_to_buy',
                'items_to_get',
                'percent_off',
                'fixed_off',
                'spend_amount',
                'num_times_to_apply',
                'coupon_code',
                'mix_and_match',
                'description',
                'show_on_receipt',
                'coupon_spend_amount',
                'disable_loyalty_for_rule'
            ]);

            $data['start_date'] = $request->start_date ? date('Y-m-d 00:00:00', strtotime($request->start_date)) : null;
            $data['end_date'] = $request->end_date ? date('Y-m-d 23:59:59', strtotime($request->end_date)) : null;

            if (!$id) {
                $data['added_on'] = now();
            }

            if ($id) {
                $priceRule = PriceRule::findOrFail($id);
                $priceRule->update($data);
            } else {
                $priceRule = PriceRule::create($data);
            }

            // Parse prefixed IDs from the frontend
            $rawItemsInput = $this->explodeInput($request->items);
            $parsedItems = [];
            $parsedKits = [];
            
            foreach ($rawItemsInput as $val) {
                if (str_starts_with($val, 'kit_')) {
                    $parsedKits[] = str_replace('kit_', '', $val);
                } elseif (str_starts_with($val, 'item_')) {
                    $parsedItems[] = str_replace('item_', '', $val);
                } else {
                    $parsedItems[] = $val;
                }
            }

            $rawKitsInput = $this->explodeInput($request->itemkits);
            foreach ($rawKitsInput as $val) {
                if (str_starts_with($val, 'kit_')) {
                    $parsedKits[] = str_replace('kit_', '', $val);
                } elseif (str_starts_with($val, 'item_')) {
                    // Shouldn't happen, but just in case
                    $parsedItems[] = str_replace('item_', '', $val);
                } else {
                    $parsedKits[] = $val;
                }
            }

            // Sync relationships
            $priceRule->items()->sync($parsedItems);
            $priceRule->itemKits()->sync($parsedKits);
            $priceRule->categories()->sync($this->explodeInput($request->categories));
            $priceRule->tags()->sync($this->explodeInput($request->tags));
            $priceRule->locations()->sync($request->locations ?? []);

            DB::table('phppos_price_rules_tiers_exclude')->where('price_rule_id', $priceRule->id)->delete();
            if ($request->excluded_tiers) {
                foreach ($request->excluded_tiers as $tierId) {
                    DB::table('phppos_price_rules_tiers_exclude')->insert([
                        'price_rule_id' => $priceRule->id,
                        'tier_id' => $tierId
                    ]);
                }
            }

            $priceRule->priceBreaks()->delete();
            if ($request->qty_to_buy) {
                foreach ($request->qty_to_buy as $key => $qty) {
                    if ($qty !== null && $qty !== '') {
                        $priceRule->priceBreaks()->create([
                            'item_qty_to_buy' => $qty,
                            'discount_per_unit_fixed' => $request->flat_unit_discount[$key] ?? null,
                            'discount_per_unit_percent' => $request->percent_unit_discount[$key] ?? null,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('price-rules.index')->with('status', 'Price Rule saved successfully.');
    }

    private function explodeInput($input)
    {
        if (!$input)
            return [];
        if (is_string($input)) {
            return array_filter(explode(',', $input));
        }
        if (is_array($input)) {
            return array_filter(explode(',', $input[0] ?? ''));
        }
        return [];
    }

    public function destroy($id)
    {
        $priceRule = PriceRule::findOrFail($id);
        $priceRule->update(['deleted' => 1]);
        return redirect()->route('price-rules.index')->with('status', 'Price Rule deleted successfully.');
    }

    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        if ($request->has('ids')) {
            $ids = explode(',', $request->get('ids'));
            $itemIds = [];
            $kitIds = [];
            foreach ($ids as $idStr) {
                if (str_starts_with($idStr, 'kit_')) {
                    $kitIds[] = str_replace('kit_', '', $idStr);
                } elseif (str_starts_with($idStr, 'item_')) {
                    $itemIds[] = str_replace('item_', '', $idStr);
                } else {
                    $itemIds[] = $idStr;
                }
            }

            $items = PhpposItem::whereIn('item_id', $itemIds)->get(['item_id', 'name'])
                ->map(fn($item) => ['id' => 'item_' . $item->item_id, 'name' => $item->name]);
            
            $kits = \App\Models\PhpposItemKit::whereIn('id', $kitIds)->get(['id', 'name'])
                ->map(fn($kit) => ['id' => 'kit_' . $kit->id, 'name' => $kit->name]);

            return response()->json($items->concat($kits));
        }

        $query = $request->get('query', '');

        $items = PhpposItem::query()
            ->where('deleted', 0)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('item_number', 'like', "%{$query}%")
                  ->orWhere('product_id', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['item_id', 'name', 'item_number'])
            ->map(function ($item) {
                return [
                    'item_id' => 'item_' . $item->item_id,
                    'name' => $item->name,
                    'item_number' => $item->item_number,
                    'type' => 'Item',
                ];
            });

        $itemKits = \App\Models\PhpposItemKit::query()
            ->where('deleted', 0)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('item_kit_number', 'like', "%{$query}%")
                  ->orWhere('product_id', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'item_kit_number'])
            ->map(function ($kit) {
                return [
                    'item_id' => 'kit_' . $kit->id,
                    'name' => $kit->name,
                    'item_number' => $kit->item_kit_number,
                    'type' => 'Item Kit',
                ];
            });

        $results = $items->concat($itemKits)->take(10);

        return response()->json([
            'data' => [
                'results' => $results->values()
            ]
        ]);
    }

    public function searchItemKits(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = $request->get('query', '');
        $ids = $request->get('ids');

        if ($ids) {
            $idArray = explode(',', $ids);
            // Remove 'kit_' prefix if present
            $cleanIds = array_map(function($id) {
                return str_replace('kit_', '', $id);
            }, $idArray);

            $kits = \App\Models\PhpposItemKit::whereIn('id', $cleanIds)
                ->where('deleted', 0)
                ->get(['id', 'name'])
                ->map(fn($kit) => ['id' => 'kit_' . $kit->id, 'name' => $kit->name]);
            return response()->json($kits);
        }

        $kits = \App\Models\PhpposItemKit::query()
            ->where('deleted', 0)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('item_kit_number', 'like', "%{$query}%")
                  ->orWhere('product_id', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'name'])
            ->map(fn($kit) => ['id' => 'kit_' . $kit->id, 'name' => $kit->name]);

        return response()->json([
            'data' => [
                'results' => $kits
            ]
        ]);
    }

    public function searchCategories(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = $request->get('query', '');
        $ids = $request->get('ids');

        if ($ids) {
            $idArray = explode(',', $ids);
            $categories = \App\Models\PhpposCategory::whereIn('id', $idArray)
                ->where('deleted', 0)
                ->get(['id', 'name'])
                ->map(fn($cat) => ['id' => $cat->id, 'name' => $cat->name]);
            return response()->json($categories);
        }

        $categories = \App\Models\PhpposCategory::query()
            ->where('deleted', 0)
            ->where('name', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'name'])
            ->map(fn($cat) => ['id' => $cat->id, 'name' => $cat->name]);

        return response()->json([
            'data' => [
                'results' => $categories
            ]
        ]);
    }

    public function searchTags(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = $request->get('query', '');
        $ids = $request->get('ids');

        if ($ids) {
            $idArray = explode(',', $ids);
            $tags = \App\Models\PhpposTag::whereIn('id', $idArray)
                ->where('deleted', 0)
                ->get(['id', 'name'])
                ->map(fn($tag) => ['id' => $tag->id, 'name' => $tag->name]);
            return response()->json($tags);
        }

        $tags = \App\Models\PhpposTag::query()
            ->where('deleted', 0)
            ->where('name', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'name'])
            ->map(fn($tag) => ['id' => $tag->id, 'name' => $tag->name]);

        return response()->json([
            'data' => [
                'results' => $tags
            ]
        ]);
    }
}
