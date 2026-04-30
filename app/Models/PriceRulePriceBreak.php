<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceRulePriceBreak extends Model
{
    protected $table = 'phppos_price_rules_price_breaks';
    public $timestamps = false;

    protected $fillable = [
        'rule_id',
        'item_qty_to_buy',
        'discount_per_unit_fixed',
        'discount_per_unit_percent',
    ];

    public function priceRule(): BelongsTo
    {
        return $this->belongsTo(PriceRule::class, 'rule_id');
    }
}
