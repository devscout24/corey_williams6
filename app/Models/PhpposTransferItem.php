<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhpposTransferItem extends Model
{
    use HasFactory;

    protected $table = 'phppos_transfer_items';

    protected $guarded = [];

    public function item(): BelongsTo
    {
        return $this->belongsTo(PhpposItem::class, 'item_id', 'item_id');
    }

    public function kit(): BelongsTo
    {
        return $this->belongsTo(PhpposItemKit::class, 'item_kit_id', 'id');
    }

    public function displayName(): string
    {
        if ($this->item_kit_id && ! $this->item_id && $this->kit) {
            return '[KIT] ' . $this->kit->name;
        }
        if ($this->item_kit_name && ! $this->item_id) {
            return '[KIT] ' . $this->item_kit_name;
        }
        return $this->item->name ?? $this->item_kit_name ?? 'Unknown Item';
    }
}
