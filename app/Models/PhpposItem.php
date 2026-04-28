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
        'cost_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];
}
