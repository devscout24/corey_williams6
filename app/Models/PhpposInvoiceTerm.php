<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposInvoiceTerm extends Model
{
    protected $table = 'phppos_invoice_terms';

    protected $fillable = [
        'name',
        'days_due',
        'deleted',
    ];
}
