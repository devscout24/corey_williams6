<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhpposTag extends Model
{
    use HasFactory;

    protected $table = 'phppos_tags';

    protected $fillable = [
        'name',
        'deleted',
    ];
}
