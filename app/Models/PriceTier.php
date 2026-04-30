<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceTier extends Model
{
    protected $table = 'phppos_price_tiers';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'deleted',
    ];
}
