<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposRegister extends Model
{
    protected $table = 'phppos_registers';

    protected $primaryKey = 'register_id';

    public $timestamps = false;

    protected $guarded = [];
}
