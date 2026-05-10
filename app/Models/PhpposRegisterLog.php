<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposRegisterLog extends Model
{
    protected $table = 'phppos_register_log';
    protected $primaryKey = 'register_log_id';
    protected $guarded = [];

    public function employeeOpen()
    {
        return $this->belongsTo(PhpposEmployee::class, 'employee_id_open', 'person_id');
    }

    public function employeeClose()
    {
        return $this->belongsTo(PhpposEmployee::class, 'employee_id_close', 'person_id');
    }

    public function register()
    {
        return $this->belongsTo(PhpposRegister::class, 'register_id', 'register_id');
    }

    public function denoms()
    {
        return $this->hasMany(PhpposRegisterLogDenom::class, 'register_log_id', 'register_log_id');
    }

    public function payments()
    {
        return $this->hasMany(PhpposRegisterLogPayment::class, 'register_log_id', 'register_log_id');
    }
}
