<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhpposTransferItem extends Model
{
    use HasFactory;

    protected $table = 'phppos_transfer_items';

    protected $guarded = [];
}
