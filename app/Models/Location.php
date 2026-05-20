<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $table = 'locations';

    protected $guarded = [];

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(TransferQueue::class, 'location_id');
    }
}
