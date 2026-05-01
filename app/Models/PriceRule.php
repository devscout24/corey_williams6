<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceRule extends Model
{
    protected $table = 'phppos_price_rules';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'added_on',
        'active',
        'deleted',
        'type',
        'items_to_buy',
        'items_to_get',
        'percent_off',
        'fixed_off',
        'spend_amount',
        'num_times_to_apply',
        'coupon_code',
        'coupon_spend_amount',
        'mix_and_match',
        'disable_loyalty_for_rule',
        'show_on_receipt',
        'description',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'added_on' => 'datetime',
        'active' => 'boolean',
        'deleted' => 'boolean',
        'mix_and_match' => 'boolean',
        'disable_loyalty_for_rule' => 'boolean',
        'show_on_receipt' => 'boolean',
    ];

    public function priceBreaks(): HasMany
    {
        return $this->hasMany(PriceRulePriceBreak::class, 'rule_id');
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(PhpposItem::class, 'phppos_price_rules_items', 'rule_id', 'item_id');
    }

    public function itemKits(): BelongsToMany
    {
        return $this->belongsToMany(PhpposItemKit::class, 'phppos_price_rules_item_kits', 'rule_id', 'item_kit_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(PhpposCategory::class, 'phppos_price_rules_categories', 'rule_id', 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(PhpposTag::class, 'phppos_price_rules_tags', 'rule_id', 'tag_id');
    }

    public function manufacturers(): BelongsToMany
    {
        return $this->belongsToMany(PhpposManufacturer::class, 'phppos_price_rules_manufacturers', 'rule_id', 'manufacturer_id');
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(PhpposLocation::class, 'phppos_price_rules_locations', 'rule_id', 'location_id');
    }
}
