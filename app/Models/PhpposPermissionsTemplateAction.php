<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposPermissionsTemplateAction extends Model
{
    protected $table = 'phppos_permissions_template_actions';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
