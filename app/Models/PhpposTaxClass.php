<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposTaxClass extends Model
{
    protected $table = 'phppos_tax_classes';

    protected $fillable = [
        'name',
        'deleted',
    ];
}
