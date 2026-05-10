<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposRegisterLogDenom extends Model
{
    protected $table = 'phppos_register_log_denoms';
    protected $guarded = [];

    public function denomination()
    {
        return $this->belongsTo(PhpposRegisterCurrencyDenomination::class, 'register_currency_denominations_id');
    }

    public function log()
    {
        return $this->belongsTo(PhpposRegisterLog::class, 'register_log_id', 'register_log_id');
    }
}
