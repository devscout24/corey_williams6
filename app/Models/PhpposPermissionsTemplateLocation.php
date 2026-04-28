<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposPermissionsTemplateLocation extends Model
{
    protected $table = 'phppos_permissions_template_locations';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
