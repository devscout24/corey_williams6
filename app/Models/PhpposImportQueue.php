<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposImportQueue extends Model
{
    protected $table = 'phppos_import_queue';

    protected $guarded = [];

    protected $casts = [
        'existing_cost_price' => 'float',
        'existing_unit_price' => 'float',
        'existing_quantity' => 'float',
        'incoming_cost_price' => 'float',
        'incoming_unit_price' => 'float',
        'incoming_quantity' => 'float',
    ];
}
