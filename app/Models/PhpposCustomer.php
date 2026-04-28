<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhpposCustomer extends Model
{
    protected $table = 'phppos_customers';
    protected $primaryKey = 'person_id';
    public $incrementing = false;
    protected $guarded = [];

    public function person(): BelongsTo
    {
        return $this->belongsTo(PhpposPerson::class, 'person_id', 'person_id');
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(PhpposPriceTier::class, 'tier_id', 'id');
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(PhpposCustomerTax::class, 'customer_id', 'person_id');
    }

    public function storeAccounts(): HasMany
    {
        return $this->hasMany(PhpposStoreAccount::class, 'customer_id', 'person_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(PhpposLocation::class, 'location_id', 'location_id');
    }

    public function taxClass(): BelongsTo
    {
        return $this->belongsTo(PhpposTaxClass::class, 'tax_class_id', 'id');
    }
}
