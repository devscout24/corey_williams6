<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Location extends Model
{
    use HasFactory;

    protected $table = 'locations';

    protected $guarded = [];

    protected $casts = [
        'is_self' => 'boolean',
        'port' => 'integer',
        'last_seen_at' => 'datetime',
        'last_poke_sent_at' => 'datetime',
        'last_poke_received_at' => 'datetime',
        'last_poke_ack_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $location) {
            if ($location->name && ! $location->slug) {
                $location->slug = Str::slug($location->name);
            }
        });

        static::updating(function (self $location) {
            if ($location->isDirty('name') && ! $location->isDirty('slug')) {
                $base = Str::slug($location->name);
                $slug = $base;
                $suffix = 1;
                while (static::query()->where('slug', $slug)->where('id', '!=', $location->id)->exists()) {
                    $slug = $base.'-'.$suffix++;
                }
                $location->slug = $slug;
            }
        });
    }

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
