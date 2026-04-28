<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposManufacturer extends Model
{
    protected $table = 'phppos_manufacturers';
    protected $primaryKey = 'id';

    protected $guarded = [];

    public $timestamps = true;

    public function items()
    {
        return $this->hasMany(PhpposItem::class, 'manufacturer_id');
    }
}
