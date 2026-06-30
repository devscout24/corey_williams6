<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhpposOrderItem extends Model
{
    protected $table = 'phppos_order_items';
    protected $guarded = [];

    public function order(): BelongsTo
    {
        return $this->belongsTo(PhpposOrder::class, 'order_id', 'order_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(PhpposItem::class, 'item_id', 'item_id');
    }

    public function kit(): BelongsTo
    {
        return $this->belongsTo(PhpposItemKit::class, 'item_kit_id', 'id');
    }
}
