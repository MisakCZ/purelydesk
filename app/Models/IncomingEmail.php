<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomingEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'ticket_comment_id',
        'sender_user_id',
        'message_id',
        'raw_hash',
        'sender_email',
        'status',
        'failure_reason',
        'processed_at',
        'failed_at',
        'attachment_notice_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
            'failed_at' => 'datetime',
            'attachment_notice_sent_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(TicketComment::class, 'ticket_comment_id');
    }

    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
