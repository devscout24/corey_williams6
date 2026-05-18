<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposTaxClass extends Model
{
    protected $table = 'phppos_tax_classes';

    protected $fillable = [
        'name',
        'order',
        'deleted',
    ];

    public function taxes()
    {
        return $this->hasMany(PhpposTaxClassTax::class, 'tax_class_id');
    }
}
