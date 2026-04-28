<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposSupplierTax extends Model
{
    protected $table = 'phppos_suppliers_taxes';

    protected $fillable = [
        'supplier_id',
        'name',
        'percent',
        'cumulative',
    ];

    public function supplier()
    {
        return $this->belongsTo(PhpposSupplier::class, 'supplier_id', 'person_id');
    }
}
