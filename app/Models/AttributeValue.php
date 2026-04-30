<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeValue extends Model
{
    protected $table = 'phppos_attribute_values';
    public $timestamps = false;

    protected $fillable = [
        'attribute_id',
        'name',
        'deleted',
        'ecommerce_attribute_term_id',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }
}
