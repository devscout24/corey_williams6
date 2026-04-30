<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemKit;
use App\Models\Location;
use App\Models\Manufacturer;
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
        
        $rule_items = $priceRule->items->map(fn($item) => ['id' => $item->item_id, 'name' => $item->name]);
        $rule_item_kits = $priceRule->itemKits->map(fn($kit) => ['id' => $kit->id, 'name' => $kit->name]);
        $rule_cats = $priceRule->categories->map(fn($cat) => ['id' => $cat->id, 'name' => $cat->name]);
        $rule_tags = $priceRule->tags->map(fn($tag) => ['id' => $tag->id, 'name' => $tag->name]);
        $rule_manus = $priceRule->manufacturers->map(fn($manu) => ['id' => $manu->id, 'name' => $manu->name]);

        return view('price_rules.form', compact('priceRule', 'locations', 'tiers', 'rule_items', 'rule_item_kits', 'rule_cats', 'rule_tags', 'rule_manus'));
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
                'name', 'type', 'active', 'items_to_buy', 'items_to_get', 
                'percent_off', 'fixed_off', 'spend_amount', 'num_times_to_apply',
                'coupon_code', 'mix_and_match', 'description', 'show_on_receipt',
                'coupon_spend_amount', 'disable_loyalty_for_rule'
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

            // Sync relationships
            $priceRule->items()->sync($this->explodeInput($request->items));
            $priceRule->itemKits()->sync($this->explodeInput($request->itemkits));
            $priceRule->categories()->sync($this->explodeInput($request->categories));
            $priceRule->tags()->sync($this->explodeInput($request->tags));
            $priceRule->manufacturers()->sync($this->explodeInput($request->manufacturers));
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
        if (!$input) return [];
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
}
