<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhpposTransfer extends Model
{
    use HasFactory;

    protected $table = 'phppos_transfers';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::created(function (self $transfer) {
            $transfer->syncDocumentIdentity();
        });
    }

    public function syncDocumentIdentity(): void
    {
        $prefix = $this->transfer_type === 'out' ? 'TRN-OUT' : 'TRN-IN';
        $this->forceFill([
            'internal_code' => $prefix . '-' . str_pad((string) $this->id, 8, '0', STR_PAD_LEFT),
        ])->saveQuietly();
    }

    public function items(): HasMany
    {
        return $this->hasMany(PhpposTransferItem::class, 'transfer_id');
    }
}
