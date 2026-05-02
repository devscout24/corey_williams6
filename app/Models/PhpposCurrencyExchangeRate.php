<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposCurrencyExchangeRate extends Model
{
    protected $table = 'phppos_currency_exchange_rates';

    protected $fillable = [
        'currency_symbol',
        'currency_code_to',
        'exchange_rate',
        'currency_symbol_location',
        'number_of_decimals',
        'thousands_separator',
        'decimal_point',
    ];
}
