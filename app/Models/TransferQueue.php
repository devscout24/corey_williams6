<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferQueue extends Model
{
    use HasFactory;

    protected $table = 'transfer_queue';

    protected $guarded = [];

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
