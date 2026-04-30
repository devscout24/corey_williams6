<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    protected $table = 'phppos_attributes';
    public $timestamps = false; // Using custom timestamp columns or none managed by Eloquent

    protected $fillable = [
        'item_id',
        'name',
        'deleted',
        'ecommerce_attribute_id',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class, 'attribute_id')->where('deleted', 0);
    }
}
