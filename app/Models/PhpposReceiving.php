<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhpposReceiving extends Model
{
    protected $table = 'phppos_receivings';
    protected $primaryKey = 'receiving_id';
    protected $guarded = [];

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

    public function transferToLocation(): BelongsTo
    {
        return $this->belongsTo(PhpposLocation::class, 'transfer_to_location_id', 'location_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PhpposReceivingItem::class, 'receiving_id', 'receiving_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PhpposReceivingPayment::class, 'receiving_id', 'receiving_id');
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(PhpposReceivingItemTax::class, 'receiving_id', 'receiving_id');
    }
}
