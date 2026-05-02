<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposEcommerceLocation extends Model
{
    protected $table = 'phppos_ecommerce_locations';
    protected $primaryKey = 'location_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'location_id',
    ];

    public function location()
    {
        return $this->belongsTo(PhpposLocation::class, 'location_id', 'location_id');
    }
}
