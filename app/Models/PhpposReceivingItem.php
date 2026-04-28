<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhpposReceivingItem extends Model
{
    protected $table = 'phppos_receivings_items';
    protected $guarded = [];

    public function item(): BelongsTo
    {
        return $this->belongsTo(PhpposItem::class, 'item_id', 'item_id');
    }
}
