<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $table = 'locations';

    protected $guarded = [];

    public function phpposLocation(): BelongsTo
    {
        return $this->belongsTo(PhpposLocation::class, 'phppos_location_id', 'location_id');
    }

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(TransferQueue::class, 'location_id');
    }

    public function getUrlAttribute(): string
    {
        $port = $this->port ? ":{$this->port}" : '';

        return "http://{$this->ip}{$port}";
    }
}
