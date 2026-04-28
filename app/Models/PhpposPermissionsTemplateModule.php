<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposPermissionsTemplateModule extends Model
{
    protected $table = 'phppos_permissions_template';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
