<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposRegisterLogPayment extends Model
{
    protected $table = 'phppos_register_log_payments';
    protected $guarded = [];

    public function log()
    {
        return $this->belongsTo(PhpposRegisterLog::class, 'register_log_id', 'register_log_id');
    }
}
