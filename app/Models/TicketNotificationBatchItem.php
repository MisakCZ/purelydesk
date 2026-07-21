<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketNotificationBatchItem extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'batch_id',
        'event',
        'actor_id',
        'ticket_activity_id',
        'context',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(TicketNotificationBatch::class, 'batch_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(TicketActivity::class, 'ticket_activity_id');
    }
}
