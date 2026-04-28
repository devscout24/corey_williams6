<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhpposMessageReceiver extends Model
{
    use HasFactory;

    protected $table = 'phppos_message_receiver';

    protected $guarded = [];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(PhpposMessage::class, 'message_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(PhpposEmployee::class, 'receiver_id', 'person_id');
    }
}
