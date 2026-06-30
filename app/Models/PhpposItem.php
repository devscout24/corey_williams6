<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhpposItem extends Model
{
    use HasFactory;

    protected $table = 'phppos_items';

    protected $primaryKey = 'item_id';

    protected $guarded = [];

    protected $casts = [
        'cost_price' => 'float',
        'unit_price' => 'float',
    ];

    public function secondaryCategories()
    {
        return $this->belongsToMany(PhpposCategory::class, 'phppos_items_secondary_categories', 'item_id', 'category_id')->withTimestamps();
    }

    public function secondarySuppliers()
    {
        return $this->belongsToMany(PhpposSupplier::class, 'phppos_items_secondary_suppliers', 'item_id', 'supplier_id')->withTimestamps();
    }

    public function taxClass()
    {
        return $this->belongsTo(PhpposTaxClass::class, 'tax_class_id', 'id');
    }
}
