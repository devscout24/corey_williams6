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

    protected function casts(): array
    {
        return [
            'receiving_time' => 'datetime',
        ];
    }

    /**
     * Document type aligned with `mode`: receive, return, or transfer.
     */
    public static function documentTypeFromMode(?string $mode): string
    {
        $mode = $mode ?? 'receive';

        return in_array($mode, ['receive', 'return', 'transfer'], true) ? $mode : 'receive';
    }

    public static function internalCodePrefixFromMode(?string $mode): string
    {
        return ($mode ?? 'receive') === 'return' ? 'RTV' : 'RCV';
    }

    public static function formatInternalCode(?string $mode, int $receivingId): string
    {
        return self::internalCodePrefixFromMode($mode).'-'.str_pad((string) $receivingId, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Persist `type` and `internal_code` from `mode` + primary key (call right after insert).
     */
    public function syncDocumentIdentity(): void
    {
        $mode = $this->mode ?? 'receive';
        $this->type = self::documentTypeFromMode($mode);
        $this->internal_code = self::formatInternalCode($mode, (int) $this->receiving_id);
        $this->saveQuietly();
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
