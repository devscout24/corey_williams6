<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhpposModuleSubmodule extends Model
{
    use HasFactory;

    protected $table = 'phppos_module_submodules';

    protected $guarded = [];
}
