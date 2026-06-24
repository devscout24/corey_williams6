<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhpposReceivingItem extends Model
{
    protected $table = 'phppos_receivings_items';
    protected $guarded = [];

    public function item(): BelongsTo
    {
        return $this->belongsTo(PhpposItem::class, 'item_id', 'item_id');
    }

    public function kit(): BelongsTo
    {
        return $this->belongsTo(PhpposItemKit::class, 'item_kit_id', 'id');
    }

    /**
     * Returns the display name for this line — item name, kit name, description, or fallback.
     */
    public function displayName(): string
    {
        if ($this->item_kit_id && ! $this->item_id && $this->kit) {
            return '[KIT] ' . $this->kit->name;
        }
        return $this->item->name ?? $this->description ?? 'Unknown Item';
    }
}
