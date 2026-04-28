<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposItemKitItemKit extends Model
{
    protected $table = 'phppos_item_kit_item_kits';
    protected $guarded = [];

    public function nestedKit()
    {
        return $this->belongsTo(PhpposItemKit::class, 'item_kit_item_kit', 'id');
    }
}
