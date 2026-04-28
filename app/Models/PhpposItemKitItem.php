<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposItemKitItem extends Model
{
    protected $table = 'phppos_item_kit_items';
    protected $guarded = [];

    public function item()
    {
        return $this->belongsTo(PhpposItem::class, 'item_id', 'item_id');
    }
}
