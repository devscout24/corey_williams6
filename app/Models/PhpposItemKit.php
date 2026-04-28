<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposItemKit extends Model
{
    protected $table = 'phppos_item_kits';
    protected $guarded = [];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(PhpposItemKitItem::class, 'item_kit_id', 'id');
    }

    public function taxes()
    {
        return $this->hasMany(PhpposItemKitTax::class, 'item_kit_id', 'id');
    }

    public function nestedKits()
    {
        return $this->hasMany(PhpposItemKitItemKit::class, 'item_kit_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(PhpposCategory::class, 'category_id', 'id');
    }

    public function manufacturer()
    {
        return $this->belongsTo(PhpposManufacturer::class, 'manufacturer_id', 'id');
    }

    public function taxClass()
    {
        return $this->belongsTo(PhpposTaxClass::class, 'tax_class_id', 'id');
    }
}
