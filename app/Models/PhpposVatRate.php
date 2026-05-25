<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposVatRate extends Model
{
    protected $table = 'phppos_vat_rates';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'percent' => 'float',
            'deleted' => 'boolean',
        ];
    }
}
