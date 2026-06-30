<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhpposOrder extends Model
{
    protected $table = 'phppos_orders';
    protected $primaryKey = 'order_id';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'order_time' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(PhpposSupplier::class, 'supplier_id', 'person_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(PhpposEmployee::class, 'employee_id', 'person_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(PhpposLocation::class, 'location_id', 'location_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PhpposOrderItem::class, 'order_id', 'order_id');
    }
}
