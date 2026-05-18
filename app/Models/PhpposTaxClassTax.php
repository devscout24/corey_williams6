<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposTaxClassTax extends Model
{
    protected $table = 'phppos_tax_classes_taxes';

    public $timestamps = false;

    protected $fillable = [
        'tax_class_id',
        'name',
        'percent',
        'cumulative',
        'order',
    ];
}
