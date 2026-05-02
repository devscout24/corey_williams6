<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposApiKey extends Model
{
    protected $table = 'phppos_api_keys';

    protected $fillable = [
        'key',
        'level',
        'ignore_limits',
        'is_private_key',
        'ip_addresses',
        'key_ending',
        'date_created',
    ];

    protected $casts = [
        'ignore_limits' => 'boolean',
        'is_private_key' => 'boolean',
    ];
}
