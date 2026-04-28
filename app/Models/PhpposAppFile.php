<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposAppFile extends Model
{
    protected $table = 'phppos_app_files';

    protected $primaryKey = 'file_id';

    public $timestamps = false;

    protected $guarded = [];
}
