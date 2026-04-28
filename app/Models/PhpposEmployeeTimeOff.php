<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposEmployeeTimeOff extends Model
{
    protected $table = 'phppos_employees_time_off';

    public $timestamps = false;

    protected $guarded = [];
}
