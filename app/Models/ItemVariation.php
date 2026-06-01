<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ItemVariation extends Model
{
    protected $table = 'phppos_item_variations';
    public $timestamps = false;

    protected $fillable = [
        'item_id',
        'name',
        'item_number',
        'cost_price',
        'markup',
        'markup_type',
        'unit_price',
        'promo_price',
        'start_date',
        'end_date',
        'reorder_level',
        'replenish_level',
        'deleted',
    ];

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'phppos_item_variation_attribute_values',
            'item_variation_id',
            'attribute_value_id'
        );
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(
            PhpposSupplier::class,
            'phppos_item_variation_suppliers',
            'item_variation_id',
            'supplier_id',
        );
    }
}
