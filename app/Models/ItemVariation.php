<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemVariation extends Model
{
    protected $table = 'phppos_item_variations';
    public $timestamps = false;

    protected $fillable = [
        'item_id',
        'name',
        'item_number',
        'unit_price',
        'cost_price',
        'promo_price',
        'start_date',
        'end_date',
        'reorder_level',
        'replenish_level',
        'deleted',
    ];
}
