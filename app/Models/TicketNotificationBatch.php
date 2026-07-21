<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketNotificationBatch extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SUPPRESSED = 'suppressed';

    protected $fillable = [
        'ticket_id',
        'recipient_id',
        'first_event_at',
        'last_event_at',
        'send_after',
        'action_grace_until',
        'status',
        'active_marker',
        'sent_at',
        'failed_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'first_event_at' => 'datetime',
            'last_event_at' => 'datetime',
            'send_after' => 'datetime',
            'action_grace_until' => 'datetime',
            'active_marker' => 'boolean',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TicketNotificationBatchItem::class, 'batch_id');
    }
}
