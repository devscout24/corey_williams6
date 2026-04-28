<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhpposMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'phppos_messages';

    protected $guarded = [];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(PhpposEmployee::class, 'sender_id', 'person_id');
    }

    public function receivers(): HasMany
    {
        return $this->hasMany(PhpposMessageReceiver::class, 'message_id');
    }
}
