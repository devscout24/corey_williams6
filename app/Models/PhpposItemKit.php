<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposItemKit extends Model
{
    protected $table = 'phppos_item_kits';
    protected $guarded = [];

    protected $casts = [
        'cost_price' => 'float',
        'unit_price' => 'float',
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

    public function supplier()
    {
        return $this->belongsTo(PhpposSupplier::class, 'supplier_id', 'person_id');
    }

    public function secondaryCategories()
    {
        return $this->belongsToMany(PhpposCategory::class, 'phppos_item_kits_secondary_categories', 'item_kit_id', 'category_id')->withTimestamps();
    }

    public function secondarySuppliers()
    {
        return $this->belongsToMany(PhpposSupplier::class, 'phppos_item_kits_secondary_suppliers', 'item_kit_id', 'supplier_id')->withTimestamps();
    }

    public function taxClass()
    {
        return $this->belongsTo(PhpposTaxClass::class, 'tax_class_id', 'id');
    }
}
