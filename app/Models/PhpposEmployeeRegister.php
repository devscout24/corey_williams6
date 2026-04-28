<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposEmployeeRegister extends Model
{
    protected $table = 'phppos_employee_registers';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
