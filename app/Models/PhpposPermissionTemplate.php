<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposPermissionTemplate extends Model
{
    protected $table = 'phppos_permissions_templates';
    protected $primaryKey = 'id';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'deleted' => 'boolean',
    ];

    public function modules()
    {
        return $this->belongsToMany(
            PhpposModule::class,
            'phppos_permissions_template',
            'template_id',
            'module_id',
            'id',
            'module_id'
        );
    }

    public function actions()
    {
        return $this->belongsToMany(
            PhpposModuleAction::class,
            'phppos_permissions_template_actions',
            'template_id',
            'action_id',
            'id',
            'action_id'
        );
    }
}
