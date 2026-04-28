<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposEmployeeTimeClock extends Model
{
    protected $table = 'phppos_employees_time_clock';

    public $timestamps = false;

    protected $guarded = [];
}
