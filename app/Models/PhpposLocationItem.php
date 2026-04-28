<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhpposLocationItem extends Model
{
    use HasFactory;

    protected $table = 'phppos_location_items';

    public $incrementing = false;

    protected $guarded = [];
}
