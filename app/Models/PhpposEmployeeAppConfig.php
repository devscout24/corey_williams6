<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposEmployeeAppConfig extends Model
{
    protected $table = 'phppos_employees_app_config';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
